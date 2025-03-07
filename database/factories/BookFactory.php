<?php

namespace Database\Factories;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'isbn' => fake()->unique()->isbn13(),
            'category_id' => Category::factory(),
            'publisher_id' => Publisher::factory(),
            'publish_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'pages' => fake()->numberBetween(50, 1000),
            'description' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(BookStatus::cases())->value,
            'price' => fake()->randomFloat(2, 10, 200),
            'is_featured' => fake()->boolean(20),
            'language' => fake()->randomElement(['en', 'id', 'es', 'fr'])
        ];
    }

    public function published(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BookStatus::PUBLISHED->value
            ];
        });
    }

    public function draft(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BookStatus::DRAFT->value
            ];
        });
    }

    public function featured(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_featured' => true
            ];
        });
    }

    public function outOfStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BookStatus::OUT_OF_STOCK->value
            ];
        });
    }
}
