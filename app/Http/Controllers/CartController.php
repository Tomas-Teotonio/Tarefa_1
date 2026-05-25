<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $items = CartItem::with(['book.publisher', 'book.authors'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->line_total = (float) $item->book->price * $item->quantity;
                return $item;
            });

        $total = $items->sum('line_total');

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function store(Book $book)
    {
        $user = Auth::user();

        if (!$user->isCitizen()) {
            abort(403);
        }

        $item = CartItem::firstOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ],
            [
                'quantity' => 1,
                'abandoned_email_sent_at' => null,
            ]
        );

        if (!$item->wasRecentlyCreated) {
            $item->increment('quantity');
            $item->update([
                'abandoned_email_sent_at' => null,
            ]);
        }

        return back()->with('success', 'Livro adicionado ao carrinho.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $user = Auth::user();

        if ($cartItem->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $cartItem->update([
            'quantity' => $data['quantity'],
            'abandoned_email_sent_at' => null,
        ]);

        return back()->with('success', 'Carrinho atualizado.');
    }

    public function destroy(CartItem $cartItem)
    {
        $user = Auth::user();

        if ($cartItem->user_id !== $user->id) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Livro removido do carrinho.');
    }

    public function clear()
    {
        CartItem::where('user_id', Auth::id())->delete();

        return back()->with('success', 'Carrinho limpo.');
    }
}