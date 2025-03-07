<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

class MidtransService implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $serverKey;
    protected $clientKey;
    protected $isProduction;

    public function __construct()
    {
        $this->isProduction = config('services.midtrans.is_production', false);
        $this->serverKey = config('services.midtrans.server_key');
        $this->clientKey = config('services.midtrans.client_key');
        $this->baseUrl = $this->isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    public function createPayment(array $data): array
    {
        $endpoint = "{$this->baseUrl}/v2/charge";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($endpoint, [
                'payment_type' => $data['payment_type'] ?? 'bank_transfer',
                'transaction_details' => [
                    'order_id' => $data['order_id'],
                    'gross_amount' => $data['amount']
                ],
                'customer_details' => [
                    'first_name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone'] ?? null
                ],
                'item_details' => $data['items']
            ]);

        return $response->json();
    }

    public function getPaymentStatus(string $paymentId): string
    {
        $endpoint = "{$this->baseUrl}/v2/{$paymentId}/status";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->get($endpoint);

        return $response->json()['transaction_status'];
    }

    public function cancelPayment(string $paymentId): bool
    {
        $endpoint = "{$this->baseUrl}/v2/{$paymentId}/cancel";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($endpoint);

        return $response->successful();
    }

    public function refundPayment(string $paymentId, float $amount): array
    {
        $endpoint = "{$this->baseUrl}/v2/{$paymentId}/refund";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($endpoint, [
                'amount' => $amount,
                'reason' => 'Customer request'
            ]);

        return $response->json();
    }

    public function createSubscription(array $data): array
    {
        $endpoint = "{$this->baseUrl}/v1/subscriptions";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($endpoint, [
                'name' => $data['name'],
                'amount' => $data['amount'],
                'currency' => 'IDR',
                'payment_type' => $data['payment_type'] ?? 'credit_card',
                'token' => $data['token'],
                'schedule' => [
                    'interval' => $data['interval'] ?? 1,
                    'interval_unit' => $data['interval_unit'] ?? 'month',
                    'start_time' => $data['start_time']
                ]
            ]);

        return $response->json();
    }

    public function cancelSubscription(string $subscriptionId): bool
    {
        $endpoint = "{$this->baseUrl}/v1/subscriptions/{$subscriptionId}/disable";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($endpoint);

        return $response->successful();
    }

    public function handleWebhook(array $payload): void
    {
        // Verify signature
        $signature = hash(
            'sha512',
            $payload['order_id'] .
                $payload['status_code'] .
                $payload['gross_amount'] .
                $this->serverKey
        );

        if ($signature !== $payload['signature_key']) {
            throw new \Exception('Invalid signature');
        }

        // Process webhook based on transaction status
        switch ($payload['transaction_status']) {
            case 'capture':
            case 'settlement':
                // Payment success
                event(new PaymentSuccessful($payload));
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
                // Payment failed
                event(new PaymentFailed($payload));
                break;

            case 'pending':
                // Payment pending
                event(new PaymentPending($payload));
                break;
        }
    }
}
