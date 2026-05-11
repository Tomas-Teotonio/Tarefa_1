<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\BookRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleBooksController;
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

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/google-books/search', [GoogleBooksController::class, 'search'])
        ->name('google.books.search');

    Route::post('/google-books/import', [GoogleBooksController::class, 'import'])
        ->name('google.books.import');

    Route::get('/google-books', function () {
        return Inertia::render('Books/SearchGoogle');
    });

    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');

    Route::get('/books/create', [BookController::class, 'create'])
        ->middleware('admin')
        ->name('books.create');

    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');

    Route::get('/requests', [BookRequestController::class, 'index'])->name('requests.index');

    Route::post('/books/{book}/request', [BookRequestController::class, 'store'])
        ->name('books.request');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::middleware('admin')->group(function () {

        Route::post('/books', [BookController::class, 'store'])->name('books.store');

        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');

        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');

        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

        Route::resource('authors', AuthorController::class)->except(['index', 'show']);
        Route::resource('publishers', PublisherController::class)->except(['index', 'show']);

        Route::post('/requests/{request}/return', [BookRequestController::class, 'return'])
            ->name('requests.return');

        Route::prefix('gestor')->group(function () {

            Route::get('/books', [BookController::class, 'adminIndex'])
                ->name('admin.books.index');

            Route::get('/books/create', [BookController::class, 'create'])
                ->name('admin.books.create');

            Route::get('/books/{book}/edit', [BookController::class, 'edit'])
                ->name('admin.books.edit');

            Route::get('/users', [UserController::class, 'index'])
                ->name('admin.users.index');

            Route::get('/users/create', [UserController::class, 'create'])
                ->name('admin.users.create');

            Route::post('/users', [UserController::class, 'store'])
                ->name('admin.users.store');

        });
    });

});