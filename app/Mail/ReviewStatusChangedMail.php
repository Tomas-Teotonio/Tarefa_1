<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReviewStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Review $review;

    public function __construct(Review $review)
    {
        $this->review = $review->loadMissing(['user', 'book']);
    }

    public function build()
    {
        return $this
            ->subject('Estado da tua review atualizado')
            ->markdown('emails.reviews.status-changed', [
                'review' => $this->review,
            ]);
    }
}