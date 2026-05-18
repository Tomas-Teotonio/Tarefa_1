<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReviewCreatedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Review $review;

    public function __construct(Review $review)
    {
        $this->review = $review->loadMissing(['user', 'book', 'request']);
    }

    public function build()
    {
        return $this
            ->subject('Nova review pendente')
            ->markdown('emails.reviews.created-admin', [
                'review' => $this->review,
            ]);
    }
}