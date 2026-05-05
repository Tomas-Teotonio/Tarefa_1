<?php

namespace App\Http\Controllers;

use App\Models\Request as BookRequest;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $requests = $user->isAdmin()
            ? BookRequest::with(['book', 'user'])->latest()->get()
            : $user->requests()->with('book')->latest()->get();

        return inertia('Requests/Index', [
            'requests' => $requests,
        ]);
    }

    public function store($bookId)
    {
        $user = Auth::user();
        $book = Book::findOrFail($bookId);

        if (!$book->isAvailable()) {
            return back()->with('error', 'Livro não disponível.');
        }

        if ($user->requests()->where('status', 'active')->count() >= 3) {
            return back()->with('error', 'Máximo de 3 livros atingido.');
        }

        $last = BookRequest::latest()->first();
        $number = $last ? $last->id + 1 : 1;

        BookRequest::create([
            'number' => 'REQ-' . str_pad($number, 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'book_id' => $book->id,
            'request_date' => now(),
            'expected_return_date' => now()->addDays(5),
        ]);

        return back()->with('success', 'Requisição feita com sucesso!');
    }
}