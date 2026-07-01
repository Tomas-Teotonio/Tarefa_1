<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\BookRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleBooksController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookAvailabilityAlertController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AiChatController;
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

    Route::post('/books/{book}/availability-alert', [BookAvailabilityAlertController::class, 'store'])
        ->name('books.availability-alert.store');

    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');

    Route::get('/requests', [BookRequestController::class, 'index'])->name('requests.index');

    Route::get('/requests/{loanRequest}', [BookRequestController::class, 'show'])
        ->name('requests.show');

    Route::post('/requests/{loanRequest}/reviews', [ReviewController::class, 'store'])
        ->name('requests.reviews.store');

    Route::post('/books/{book}/request', [BookRequestController::class, 'store'])
        ->name('books.request');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

    Route::post('/books/{book}/cart', [CartController::class, 'store'])
        ->name('cart.store');

    Route::put('/cart/{cartItem}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])
        ->name('cart.destroy');

    Route::delete('/cart', [CartController::class, 'clear'])
        ->name('cart.clear');

    Route::get('/checkout/address', [CheckoutController::class, 'address'])
        ->name('checkout.address');

    Route::post('/checkout/address', [CheckoutController::class, 'storeAddress'])
        ->name('checkout.address.store');

    Route::get('/checkout/payment/{order}', [CheckoutController::class, 'payment'])
        ->name('checkout.payment');

    Route::post('/checkout/payment/{order}', [CheckoutController::class, 'startPayment'])
        ->name('checkout.payment.start');

    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])
        ->name('checkout.cancel');


    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');

    Route::middleware('admin')->group(function () {
        Route::get('/chat/rooms/create', [ChatController::class, 'createRoom'])
            ->name('chat.rooms.create');

        Route::post('/chat/rooms', [ChatController::class, 'storeRoom'])
            ->name('chat.rooms.store');
    });

    Route::get('/chat/direct/{user}', [ChatController::class, 'direct'])
        ->name('chat.direct');

    Route::get('/chat/direct/{user}/messages', [ChatController::class, 'directMessages'])
        ->name('chat.direct.messages.index');

    Route::post('/chat/direct/{user}/messages', [ChatController::class, 'storeDirectMessage'])
        ->name('chat.direct.messages.store');

    Route::get('/chat/rooms/{room}', [ChatController::class, 'room'])
        ->name('chat.room');

    Route::get('/chat/rooms/{room}/messages', [ChatController::class, 'roomMessages'])
        ->name('chat.room.messages.index');

    Route::post('/chat/rooms/{room}/messages', [ChatController::class, 'storeRoomMessage'])
        ->name('chat.room.messages.store');

    Route::get('/ai-chat', [AiChatController::class, 'index'])
        ->name('ai-chat.index');

    Route::post('/ai-chat/stream', [AiChatController::class, 'stream'])
        ->name('ai-chat.stream');

    Route::post('/ai-chat', [AiChatController::class, 'store'])
        ->name('ai-chat.store');

    Route::post('/ai-chat/{conversation}/messages', [AiChatController::class, 'storeMessage'])
        ->name('ai-chat.messages.store');

    Route::delete('/ai-chat/{conversation}', [AiChatController::class, 'destroy'])
        ->name('ai-chat.destroy');

    Route::get('/ai-chat/{conversation}', [AiChatController::class, 'show'])
        ->name('ai-chat.show');

    Route::put('/ai-chat/{conversation}/settings', [AiChatController::class, 'updateSettings'])
        ->name('ai-chat.settings.update');

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

            Route::get('/reviews', [ReviewController::class, 'index'])
                ->name('admin.reviews.index');

            Route::get('/reviews/{review}', [ReviewController::class, 'show'])
                ->name('admin.reviews.show');

            Route::put('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])
                ->name('admin.reviews.updateStatus');

            Route::get('/orders', [AdminOrderController::class, 'index'])
                ->name('admin.orders.index');

            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
                ->name('admin.orders.show');
            
            Route::get('/logs', [ActivityLogController::class, 'index'])
                ->name('admin.logs.index');

        });
    });

});