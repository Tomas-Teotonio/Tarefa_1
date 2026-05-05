<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'number',
        'user_id',
        'book_id',
        'request_date',
        'expected_return_date',
        'actual_return_date',
        'days_used',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    // 🔗 RELAÇÕES
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}