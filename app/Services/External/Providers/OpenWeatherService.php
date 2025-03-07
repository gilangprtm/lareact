<?php

namespace App\Services\External\Providers;

use App\Services\External\Contracts\WeatherServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenWeatherService implements WeatherServiceInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.openweathermap.org/data/2.5';
        $this->apiKey = config('services.openweather.api_key');
        $this->cacheTtl = 1800; // 30 minutes
    }

    public function getCurrentWeather(string $city): array
    {
        $cacheKey = "weather.current.{$city}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($city) {
            $response = Http::get("{$this->baseUrl}/weather", [
                'q' => $city,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            return $response->json();
        });
    }

    public function getForecast(string $city, int $days = 5): array
    {
        $cacheKey = "weather.forecast.{$city}.{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($city, $days) {
            $response = Http::get("{$this->baseUrl}/forecast", [
                'q' => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => $days * 8 // API returns data every 3 hours
            ]);

            return $response->json();
        });
    }

    public function getHistoricalData(string $city, string $date): array
    {
        $cacheKey = "weather.history.{$city}.{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl * 48, function () use ($city, $date) {
            $timestamp = strtotime($date);
            $response = Http::get("{$this->baseUrl}/onecall/timemachine", [
                'q' => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'dt' => $timestamp
            ]);

            return $response->json();
        });
    }

    public function getAirQuality(string $city): array
    {
        $cacheKey = "weather.air.{$city}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($city) {
            // First get coordinates from city name
            $coords = $this->getCityCoordinates($city);

            $response = Http::get("{$this->baseUrl}/air_pollution", [
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
                'appid' => $this->apiKey
            ]);

            return $response->json();
        });
    }

    public function getAlerts(string $city): array
    {
        $cacheKey = "weather.alerts.{$city}";

        return Cache::remember($cacheKey, 300, function () use ($city) { // 5 minutes cache for alerts
            $coords = $this->getCityCoordinates($city);

            $response = Http::get("{$this->baseUrl}/onecall", [
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
                'appid' => $this->apiKey,
                'exclude' => 'current,minutely,hourly,daily'
            ]);

            return $response->json()['alerts'] ?? [];
        });
    }

    protected function getCityCoordinates(string $city): array
    {
        $cacheKey = "weather.coords.{$city}";

        return Cache::remember($cacheKey, $this->cacheTtl * 24 * 7, function () use ($city) {
            $response = Http::get('http://api.openweathermap.org/geo/1.0/direct', [
                'q' => $city,
                'limit' => 1,
                'appid' => $this->apiKey
            ]);

            $data = $response->json()[0];
            return [
                'lat' => $data['lat'],
                'lon' => $data['lon']
            ];
        });
    }
}
