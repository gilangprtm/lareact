<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create root categories
        $categories = Category::factory()
            ->count(5)
            ->active()
            ->create();

        // Create child categories
        foreach ($categories as $category) {
            Category::factory()
                ->count(3)
                ->active()
                ->create(['parent_id' => $category->id]);
        }

        // Create publishers
        $publishers = Publisher::factory()
            ->count(10)
            ->active()
            ->create();

        // Create books for each publisher
        foreach ($publishers as $publisher) {
            // Get random categories
            $randomCategories = Category::inRandomOrder()->limit(3)->get();

            foreach ($randomCategories as $category) {
                Book::factory()
                    ->count(5)
                    ->published()
                    ->create([
                        'publisher_id' => $publisher->id,
                        'category_id' => $category->id
                    ]);
            }

            // Create some featured books
            Book::factory()
                ->count(2)
                ->published()
                ->featured()
                ->create([
                    'publisher_id' => $publisher->id,
                    'category_id' => $randomCategories->random()->id
                ]);

            // Create some draft books
            Book::factory()
                ->count(2)
                ->draft()
                ->create([
                    'publisher_id' => $publisher->id,
                    'category_id' => $randomCategories->random()->id
                ]);
        }
    }
}
