<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSubdomain extends Model
{
    protected $fillable = [
        'domain_id',
        'hostname',
        'path_segment',
        'document_root',
        'php_version',
        'server_type',
        'ssl_enabled',
        'ssl_expiry',
    ];

    protected function casts(): array
    {
        return [
            'ssl_enabled' => 'boolean',
            'ssl_expiry' => 'datetime',
        ];
    }

    public function sslCertificate()
    {
        return $this->hasOne(SslCertificate::class, 'site_subdomain_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
}
