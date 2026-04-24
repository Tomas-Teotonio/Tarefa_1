<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'encrypted',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}