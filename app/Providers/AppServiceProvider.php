<?php

namespace App\Providers;

use App\Services\DB\Providers\AuthorService;
use App\Services\DB\Providers\BookService;
use App\Services\DB\Providers\CategoryService;
use App\Services\DB\Providers\PublisherService;
use App\Services\DB\Contracts\AuthorServiceInterface;
use App\Services\DB\Contracts\BookServiceInterface;
use App\Services\DB\Contracts\CategoryServiceInterface;
use App\Services\DB\Contracts\PublisherServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookServiceInterface::class, BookService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(AuthorServiceInterface::class, AuthorService::class);
        $this->app->bind(PublisherServiceInterface::class, PublisherService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
