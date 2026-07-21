<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'model_id',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(
            AiMessageComment::class,
            'ai_message_id'
        );
    }
    
}