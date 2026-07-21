<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AiConversationShare;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'model_id',
        'temperature',
        'max_tokens',
        'pinned_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'pinned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(AiMessage::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(AiConversationShare::class);
    }
}