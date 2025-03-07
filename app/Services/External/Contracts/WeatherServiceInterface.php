<?php

namespace App\Services\External\Contracts;

interface WeatherServiceInterface
{
    public function getCurrentWeather(string $city): array;

    public function getForecast(string $city, int $days = 5): array;

    public function getHistoricalData(string $city, string $date): array;

    public function getAirQuality(string $city): array;

    public function getAlerts(string $city): array;
}
