<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainRegistrar extends Model
{
    protected $fillable = [
        'api_name',
        'display_name',
        'credentials',
        'is_enabled',
        'sort_order',
        'last_synced_at',
        'last_sync_status',
        'last_sync_message',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function catalog(): ?array
    {
        return config('domain_registrars.providers.'.$this->api_name);
    }

    public function isConfigured(): bool
    {
        $catalog = $this->catalog();
        if ($catalog === null) {
            return false;
        }

        $credentials = $this->credentials ?? [];
        foreach ($catalog['credential_fields'] ?? [] as $key => $field) {
            if (($field['required'] ?? false) && empty($credentials[$key])) {
                return false;
            }
        }

        return true;
    }
}
