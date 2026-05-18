<?php

namespace App\Mail;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request->loadMissing(['user', 'book']);
    }

    public function build()
    {
        return $this
            ->subject('Nova Requisição de Livro')
            ->markdown('emails.request_created', [
                'request' => $this->request,
            ]);
    }
}