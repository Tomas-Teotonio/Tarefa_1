<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'number',
        'user_id',
        'book_id',
        'request_date',
        'expected_return_date',
        'actual_return_date',
        'days_used',
        'status',
        'user_photo',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'expected_return_date' => 'datetime',
        'actual_return_date' => 'datetime',
    ];

    // 🔗 Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'request_id');
    }
}