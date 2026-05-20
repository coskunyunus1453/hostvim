<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $fillable = [
        'session_id',
        'role',
        'content',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}
