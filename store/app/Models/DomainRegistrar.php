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

    /**
     * Sağlayıcının bu TLD'yi satıp satamayacağı (config supported_tlds).
     * "global" veya boş liste = tüm uzantılar.
     * "TR" / "tr" = yalnızca .tr ailesi (.tr, .com.tr, …).
     */
    public function supportsTld(string $tld): bool
    {
        $catalog = $this->catalog();
        $supported = $catalog['supported_tlds'] ?? ['global'];
        if ($supported === [] || in_array('global', $supported, true)) {
            return true;
        }

        $tld = strtolower(trim($tld));
        if ($tld === '') {
            return false;
        }
        if ($tld[0] !== '.') {
            $tld = '.'.$tld;
        }

        foreach ($supported as $token) {
            $token = strtolower(trim((string) $token));
            if ($token === '' || $token === 'global') {
                continue;
            }
            // Ülke kodu: TR → .tr ve *.tr
            if (strlen($token) === 2 && ! str_contains($token, '.')) {
                if ($tld === '.'.$token || str_ends_with($tld, '.'.$token)) {
                    return true;
                }

                continue;
            }
            $norm = $token[0] === '.' ? $token : '.'.$token;
            if ($tld === $norm || str_ends_with($tld, $norm)) {
                return true;
            }
        }

        return false;
    }
}
