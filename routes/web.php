<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PublisherController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'stats' => [
            'books' => Book::count(),
            'authors' => Author::count(),
            'publishers' => Publisher::count(),
        ],
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $dashboardBooks = Book::with(['publisher:id,name', 'authors:id,name'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'dashboardBooks' => $dashboardBooks,
        ]);
    })->name('dashboard');

    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');
    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');
});