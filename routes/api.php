<?php

use App\Http\Controllers\API\AuthorController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PublisherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('categories/{id}', [CategoryController::class, 'show'])->name('api.categories.show');

    Route::get('publishers', [PublisherController::class, 'index'])->name('api.publishers.index');
    Route::get('publishers/{id}', [PublisherController::class, 'show'])->name('api.publishers.show');

    Route::get('books', [BookController::class, 'index'])->name('api.books.index');
    Route::get('books/{id}', [BookController::class, 'show'])->name('api.books.show');

    Route::get('authors', [AuthorController::class, 'index'])->name('api.authors.index');
    Route::get('authors/{id}', [AuthorController::class, 'show'])->name('api.authors.show');

    Route::post('categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::put('categories/{id}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');

    // Publishers management
    Route::post('publishers', [PublisherController::class, 'store'])->name('api.publishers.store');
    Route::put('publishers/{id}', [PublisherController::class, 'update'])->name('api.publishers.update');
    Route::delete('publishers/{id}', [PublisherController::class, 'destroy'])->name('api.publishers.destroy');

    // Books management
    Route::post('books', [BookController::class, 'store'])->name('api.books.store');
    Route::put('books/{id}', [BookController::class, 'update'])->name('api.books.update');
    Route::delete('books/{id}', [BookController::class, 'destroy'])->name('api.books.destroy');

    // Authors management
    Route::post('authors', [AuthorController::class, 'store'])->name('api.authors.store');
    Route::put('authors/{id}', [AuthorController::class, 'update'])->name('api.authors.update');
    Route::delete('authors/{id}', [AuthorController::class, 'destroy'])->name('api.authors.destroy');
});
