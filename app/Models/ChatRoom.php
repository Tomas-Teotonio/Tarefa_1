<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'avatar',
        'name',
        'reference',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('invited_by')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }
}