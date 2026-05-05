<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RequestReminderMail extends Mailable
{
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function build()
    {
        return $this->subject('Lembrete de devolução')
            ->view('emails.request_reminder');
    }
}