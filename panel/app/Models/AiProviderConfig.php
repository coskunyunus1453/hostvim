<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderConfig extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'model',
        'enabled',
        'is_default',
        'last_test_at',
        'last_test_ok',
        'last_test_message',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'enabled' => 'boolean',
            'is_default' => 'boolean',
            'last_test_at' => 'datetime',
            'last_test_ok' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
