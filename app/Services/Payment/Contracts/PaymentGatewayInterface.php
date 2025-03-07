<?php

namespace App\Services\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function createPayment(array $data): array;

    public function getPaymentStatus(string $paymentId): string;

    public function cancelPayment(string $paymentId): bool;

    public function refundPayment(string $paymentId, float $amount): array;

    public function createSubscription(array $data): array;

    public function cancelSubscription(string $subscriptionId): bool;

    public function handleWebhook(array $payload): void;
}
