<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'isbn',
        'name',
        'publisher_id',
        'bibliography',
        'cover_image',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'bibliography' => 'encrypted',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)->withTimestamps();
    }

    public function requests()
    {
        return $this->hasMany(BookRequest::class);
    }

    public function isAvailable()
    {
        return !$this->requests()
            ->where('status', 'active')
            ->exists();
    }
}