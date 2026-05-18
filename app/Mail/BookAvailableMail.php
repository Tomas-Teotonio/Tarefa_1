<?php

namespace App\Mail;

use App\Models\BookAvailabilityAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public BookAvailabilityAlert $alert;

    public function __construct(BookAvailabilityAlert $alert)
    {
        $this->alert = $alert->loadMissing(['user', 'book']);
    }

    public function build()
    {
        return $this
            ->subject('Livro disponível para requisição')
            ->markdown('emails.books.available', [
                'alert' => $this->alert,
            ]);
    }
}