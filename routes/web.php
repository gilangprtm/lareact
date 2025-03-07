<?php

use App\Http\Controllers\DB\AuthorController;
use App\Http\Controllers\DB\BookController;
use App\Http\Controllers\DB\CategoryController;
use App\Http\Controllers\DB\PublisherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'books' => app(BookController::class)->index()
    ]);
})->name('home');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

// Books
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{id}', [BookController::class, 'show'])->name('books.show');

// Publishers
Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');
Route::get('/publishers/{id}', [PublisherController::class, 'show'])->name('publishers.show');

// Authors
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{id}', [AuthorController::class, 'show'])->name('authors.show');

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Categories Management
    Route::post('/categories', [CategoryController::class, 'create'])->name('categories.store');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'delete'])->name('categories.destroy');

    // Publishers Management
    Route::post('/publishers', [PublisherController::class, 'create'])->name('publishers.store');
    Route::put('/publishers/{id}', [PublisherController::class, 'update'])->name('publishers.update');
    Route::delete('/publishers/{id}', [PublisherController::class, 'delete'])->name('publishers.destroy');

    // Books Management
    Route::post('/books', [BookController::class, 'create'])->name('books.store');
    Route::put('/books/{id}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{id}', [BookController::class, 'delete'])->name('books.destroy');

    // Authors Management
    Route::post('/authors', [AuthorController::class, 'create'])->name('authors.store');
    Route::put('/authors/{id}', [AuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{id}', [AuthorController::class, 'delete'])->name('authors.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
