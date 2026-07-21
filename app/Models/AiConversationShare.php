<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationShare extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'visibility',
        'token',
        'revoked_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isRestricted(): bool
    {
        return $this->visibility === 'restricted';
    }
}