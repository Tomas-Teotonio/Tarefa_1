<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public Collection $items;

    public float $total;

    public function __construct(User $user, Collection $items)
    {
        $this->user = $user;
        $this->items = $items;

        $this->total = $items->sum(function ($item) {
            return (float) $item->book->price * $item->quantity;
        });
    }

    public function build()
    {
        return $this
            ->subject('Ainda precisas de ajuda com o teu carrinho?')
            ->markdown('emails.cart.abandoned', [
                'user' => $this->user,
                'items' => $this->items,
                'total' => $this->total,
            ]);
    }
}