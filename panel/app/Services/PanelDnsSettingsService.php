<?php

namespace App\Services;

use App\Models\PanelSetting;

class PanelDnsSettingsService
{
    public const KEY_NS1 = 'dns.ns1';

    public const KEY_NS2 = 'dns.ns2';

    public const KEY_SERVER_IP = 'dns.server_ip';

    public const KEY_BIND_ENABLED = 'dns.bind_enabled';

    public const KEY_BOOTSTRAP_DEFAULTS = 'dns.bootstrap_defaults';

    /**
     * @return array{
     *     persisted: bool,
     *     ns1: string,
     *     ns2: string,
     *     server_ip: string,
     *     bind_enabled: bool,
     *     bootstrap_defaults: bool,
     *     detected_server_ip: string,
     * }
     */
    public function forApi(): array
    {
        $persisted = PanelSetting::query()
            ->whereIn('key', [
                self::KEY_NS1,
                self::KEY_NS2,
                self::KEY_SERVER_IP,
                self::KEY_BIND_ENABLED,
                self::KEY_BOOTSTRAP_DEFAULTS,
            ])
            ->exists();

        [$ns1, $ns2] = $this->nameServers();

        return [
            'persisted' => $persisted,
            'ns1' => $ns1,
            'ns2' => $ns2,
            'server_ip' => $this->serverIp(),
            'bind_enabled' => $this->bindEnabled(),
            'bootstrap_defaults' => $this->bootstrapDefaults(),
            'detected_server_ip' => $this->detectServerIp(),
        ];
    }

    /**
     * @param  array{ns1?: string, ns2?: string, server_ip?: string, bind_enabled?: bool, bootstrap_defaults?: bool}  $data
     */
    public function update(array $data): void
    {
        $set = function (string $key, string $value): void {
            PanelSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        };

        if (array_key_exists('ns1', $data)) {
            $set(self::KEY_NS1, strtolower(trim((string) $data['ns1'])));
        }
        if (array_key_exists('ns2', $data)) {
            $set(self::KEY_NS2, strtolower(trim((string) $data['ns2'])));
        }
        if (array_key_exists('server_ip', $data)) {
            $set(self::KEY_SERVER_IP, trim((string) $data['server_ip']));
        }
        if (array_key_exists('bind_enabled', $data)) {
            $set(self::KEY_BIND_ENABLED, $data['bind_enabled'] ? '1' : '0');
        }
        if (array_key_exists('bootstrap_defaults', $data)) {
            $set(self::KEY_BOOTSTRAP_DEFAULTS, $data['bootstrap_defaults'] ? '1' : '0');
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function nameServers(): array
    {
        $ns1 = $this->resolvedString(self::KEY_NS1, 'panelze.dns.ns1');
        $ns2 = $this->resolvedString(self::KEY_NS2, 'panelze.dns.ns2');
        if ($ns1 === '') {
            $ns1 = trim((string) @shell_exec('hostname -f 2>/dev/null')) ?: 'ns1';
        }
        if ($ns2 === '') {
            $parts = explode('.', $ns1, 2);
            $ns2 = count($parts) === 2 ? 'ns2.'.$parts[1] : 'ns2';
        }

        return [$ns1, $ns2];
    }

    public function serverIp(): string
    {
        $configured = $this->resolvedString(self::KEY_SERVER_IP, 'panelze.dns.server_ip');
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_IP)) {
            return $configured;
        }

        return $this->detectServerIp();
    }

    public function bindEnabled(): bool
    {
        $stored = $this->stored(self::KEY_BIND_ENABLED);
        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('panelze.dns.bind_enabled', true);
    }

    public function bootstrapDefaults(): bool
    {
        $stored = $this->stored(self::KEY_BOOTSTRAP_DEFAULTS);
        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('panelze.dns.bootstrap_defaults', true);
    }

    public function detectServerIp(): string
    {
        $ips = trim((string) @shell_exec('hostname -I 2>/dev/null') ?: '');
        $first = explode(' ', $ips)[0] ?? '';

        return filter_var($first, FILTER_VALIDATE_IP) ? $first : '';
    }

    private function resolvedString(string $panelKey, string $configKey): string
    {
        $stored = $this->stored($panelKey);
        if ($stored !== null && trim($stored) !== '') {
            return trim($stored);
        }

        return trim((string) config($configKey, ''));
    }

    private function stored(string $key): ?string
    {
        $value = PanelSetting::query()->where('key', $key)->value('value');

        return is_string($value) ? $value : null;
    }
}
