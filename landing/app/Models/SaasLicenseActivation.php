<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasLicenseActivation extends Model
{
    protected $fillable = [
        'saas_license_id',
        'host',
        'ip',
        'user_agent',
        'activated_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(SaasLicense::class, 'saas_license_id');
    }
}
