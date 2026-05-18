<?php

use App\Http\Controllers\GoogleBooksController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/google-books/search', [GoogleBooksController::class, 'search'])
    ->name('api.google.books.search');