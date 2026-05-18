<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookAvailabilityAlert;
use Illuminate\Support\Facades\Auth;

class BookAvailabilityAlertController extends Controller
{
    public function store(Book $book)
    {
        $user = Auth::user();

        if (!$user->isCitizen()) {
            abort(403);
        }

        if ($book->isAvailable()) {
            return back()->with('error', 'Este livro já está disponível para requisição.');
        }

        $alreadyExists = BookAvailabilityAlert::where('book_id', $book->id)
            ->where('user_id', $user->id)
            ->whereNull('notified_at')
            ->exists();

        if ($alreadyExists) {
            return back()->with('error', 'Já pediste para ser avisado quando este livro estiver disponível.');
        }

        BookAvailabilityAlert::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Vais receber um email quando o livro estiver disponível.');
    }
}