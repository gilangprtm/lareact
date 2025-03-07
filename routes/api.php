<?php

use App\Http\Controllers\DB\AuthorController;
use App\Http\Controllers\DB\BookController;
use App\Http\Controllers\DB\CategoryController;
use App\Http\Controllers\DB\PublisherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);

    Route::get('publishers', [PublisherController::class, 'index']);
    Route::get('publishers/{id}', [PublisherController::class, 'show']);

    Route::get('books', [BookController::class, 'index']);
    Route::get('books/{id}', [BookController::class, 'show']);

    Route::get('authors', [AuthorController::class, 'index']);
    Route::get('authors/{id}', [AuthorController::class, 'show']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Categories management
        Route::post('categories', [CategoryController::class, 'create']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'delete']);

        // Publishers management
        Route::post('publishers', [PublisherController::class, 'create']);
        Route::put('publishers/{id}', [PublisherController::class, 'update']);
        Route::delete('publishers/{id}', [PublisherController::class, 'delete']);

        // Books management
        Route::post('books', [BookController::class, 'create']);
        Route::put('books/{id}', [BookController::class, 'update']);
        Route::delete('books/{id}', [BookController::class, 'delete']);

        // Authors management
        Route::post('authors', [AuthorController::class, 'create']);
        Route::put('authors/{id}', [AuthorController::class, 'update']);
        Route::delete('authors/{id}', [AuthorController::class, 'delete']);
    });
});
