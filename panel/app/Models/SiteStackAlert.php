<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteStackAlert extends Model
{
    protected $fillable = [
        'user_id',
        'domain_id',
        'domain_name',
        'profile',
        'severity',
        'fingerprint',
        'status',
        'issue_codes',
        'issue_count',
        'notified_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_codes' => 'array',
            'issue_count' => 'integer',
            'notified_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
