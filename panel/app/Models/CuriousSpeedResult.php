<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuriousSpeedResult extends Model
{
    protected $fillable = [
        'user_id',
        'client_ip',
        'panel_ping_ms',
        'panel_download_mbps',
        'panel_upload_mbps',
        'server_ping_ms',
        'server_download_mbps',
        'server_upload_mbps',
        'delta_ping_ms',
        'delta_download_mbps',
        'delta_upload_mbps',
        'server_label',
        'server_from_cache',
        'server_error',
    ];

    protected function casts(): array
    {
        return [
            'panel_download_mbps' => 'float',
            'panel_upload_mbps' => 'float',
            'server_download_mbps' => 'float',
            'server_upload_mbps' => 'float',
            'delta_ping_ms' => 'float',
            'delta_download_mbps' => 'float',
            'delta_upload_mbps' => 'float',
            'server_from_cache' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'client_ip' => $this->client_ip,
            'created_at' => $this->created_at?->toIso8601String(),
            'panel' => [
                'ping_ms' => $this->panel_ping_ms,
                'download_mbps' => $this->panel_download_mbps,
                'upload_mbps' => $this->panel_upload_mbps,
            ],
            'server' => [
                'ping_ms' => $this->server_ping_ms,
                'download_mbps' => $this->server_download_mbps,
                'upload_mbps' => $this->server_upload_mbps,
                'label' => $this->server_label,
                'from_cache' => $this->server_from_cache,
                'error' => $this->server_error,
            ],
            'delta' => [
                'ping_ms' => $this->delta_ping_ms,
                'download_mbps' => $this->delta_download_mbps,
                'upload_mbps' => $this->delta_upload_mbps,
            ],
        ];
    }
}
