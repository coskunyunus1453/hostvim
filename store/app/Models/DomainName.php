<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainName extends Model
{
    protected $fillable = [
        'registrar_api',
        'domain',
        'status',
        'registered_at',
        'expires_at',
        'auto_renew',
        'privacy',
        'locked',
        'ns_provider',
        'nameservers',
        'order_id',
        'customer_email',
        'meta',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'auto_renew' => 'boolean',
            'locked' => 'boolean',
            'nameservers' => 'array',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function privacyEnabled(): bool
    {
        return $this->privacy === 'high';
    }
}
