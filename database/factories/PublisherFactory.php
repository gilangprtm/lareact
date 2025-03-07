<?php

namespace Database\Factories;

use App\Enums\PublisherStatus;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'website' => fake()->url(),
            'logo_path' => null,
            'status' => fake()->randomElement(PublisherStatus::cases())->value
        ];
    }

    public function active(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PublisherStatus::ACTIVE->value
            ];
        });
    }

    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PublisherStatus::INACTIVE->value
            ];
        });
    }

    public function suspended(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PublisherStatus::SUSPENDED->value
            ];
        });
    }
}
