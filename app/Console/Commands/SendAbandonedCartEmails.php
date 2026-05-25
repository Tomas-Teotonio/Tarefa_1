<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartMail;
use App\Models\CartItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmails extends Command
{
    protected $signature = 'cart:send-abandoned-emails';

    protected $description = 'Envia emails a cidadãos com carrinhos abandonados há mais de 1 hora.';

    public function handle(): int
    {
        $limit = now()->subHour();

        $groups = CartItem::with(['user', 'book'])
            ->whereNull('abandoned_email_sent_at')
            ->get()
            ->groupBy('user_id');

        $sent = 0;

        foreach ($groups as $userId => $items) {
            $user = $items->first()->user;

            if (!$user || !$user->isCitizen()) {
                continue;
            }

            $lastCartUpdate = $items->max('updated_at');

            if ($lastCartUpdate && $lastCartUpdate->gt($limit)) {
                continue;
            }

            Mail::to($user->email)->send(new AbandonedCartMail($user, $items));

            CartItem::whereIn('id', $items->pluck('id'))->update([
                'abandoned_email_sent_at' => now(),
            ]);

            $sent++;
        }

        $this->info("Emails de carrinho abandonado enviados: {$sent}");

        return self::SUCCESS;
    }
}