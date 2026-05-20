<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainCloudflareZone extends Model
{
    protected $fillable = [
        'domain_id',
        'cloudflare_connection_id',
        'zone_id',
        'zone_name',
        'ssl_mode',
        'status',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CloudflareConnection::class, 'cloudflare_connection_id');
    }
}
