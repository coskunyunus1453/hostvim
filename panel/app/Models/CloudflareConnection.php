<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareConnection extends Model
{
    protected $fillable = [
        'user_id',
        'api_token',
        'account_id',
        'account_email',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domainZones(): HasMany
    {
        return $this->hasMany(DomainCloudflareZone::class);
    }
}
