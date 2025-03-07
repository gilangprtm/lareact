<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'parent_id' => null,
            'status' => fake()->randomElement(CategoryStatus::cases())->value
        ];
    }

    public function asChild(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Category::factory()
            ];
        });
    }

    public function active(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => CategoryStatus::ACTIVE->value
            ];
        });
    }

    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => CategoryStatus::INACTIVE->value
            ];
        });
    }
}
