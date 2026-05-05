<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestReminderMail;

class SendReminderEmails extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Enviar lembretes de devolução de livros';

    public function handle()
    {
        $tomorrow = now()->addDay()->toDateString();

        $requests = BookRequest::with('user', 'book')
            ->whereDate('expected_return_date', $tomorrow)
            ->where('status', 'active')
            ->get();

        foreach ($requests as $request) {
            Mail::to($request->user->email)
                ->send(new RequestReminderMail($request));
        }

        $this->info('Lembretes enviados com sucesso!');
    }
}