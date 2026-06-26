<?php

namespace App\Services;

use App\Models\PanelSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PanelDnsSettingsService
{
    public const CACHE_KEY = 'panelze:dns:settings';

    public const CACHE_TTL_SECONDS = 300;
    public const KEY_NS1 = 'dns.ns1';

    public const KEY_NS2 = 'dns.ns2';

    public const KEY_SERVER_IP = 'dns.server_ip';

    public const KEY_BIND_ENABLED = 'dns.bind_enabled';

    public const KEY_BOOTSTRAP_DEFAULTS = 'dns.bootstrap_defaults';

    /**
     * @return array{
     *     persisted: bool,
     *     configured: bool,
     *     ns1: string,
     *     ns2: string,
     *     server_ip: string,
     *     bind_enabled: bool,
     *     bootstrap_defaults: bool,
     *     detected_server_ip: string,
     *     suggested_ns1: string,
     *     suggested_ns2: string,
     * }
     */
    public function forApi(): array
    {
        $persisted = PanelSetting::query()
            ->whereIn('key', [
                self::KEY_NS1,
                self::KEY_NS2,
                self::KEY_SERVER_IP,
            ])
            ->exists();

        [$ns1, $ns2] = $this->nameServers();
        [$suggestedNs1, $suggestedNs2] = $this->suggestedNameServers();

        return [
            'persisted' => $persisted,
            'configured' => $this->isConfigured(),
            'ns1' => $ns1,
            'ns2' => $ns2,
            'server_ip' => $this->serverIp(),
            'bind_enabled' => $this->bindEnabled(),
            'bootstrap_defaults' => $this->bootstrapDefaults(),
            'detected_server_ip' => $this->detectServerIp(),
            'suggested_ns1' => $suggestedNs1,
            'suggested_ns2' => $suggestedNs2,
        ];
    }

    public function isConfigured(): bool
    {
        [$ns1, $ns2] = $this->nameServers();

        return $ns1 !== ''
            && $ns2 !== ''
            && $this->serverIp() !== ''
            && ! $this->looksLikeServerHostname($ns1)
            && ! $this->looksLikeServerHostname($ns2);
    }

    /** @/www/mail/webmail A kayıtları için yeterli (NS glue şart değil). */
    public function hasServerIp(): bool
    {
        return $this->serverIp() !== '';
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

        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function nameServers(): array
    {
        $ns1 = $this->resolvedString(self::KEY_NS1, 'panelze.dns.ns1');
        $ns2 = $this->resolvedString(self::KEY_NS2, 'panelze.dns.ns2');

        if ($ns1 !== '' && $this->looksLikeServerHostname($ns1)) {
            $ns1 = '';
        }
        if ($ns2 !== '' && $this->looksLikeServerHostname($ns2)) {
            $ns2 = '';
        }

        if ($ns1 === '' || $ns2 === '') {
            [$suggestedNs1, $suggestedNs2] = $this->suggestedNameServers();
            if ($ns1 === '') {
                $ns1 = $suggestedNs1;
            }
            if ($ns2 === '') {
                $ns2 = $suggestedNs2;
            }
        }

        return [$ns1, $ns2];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function suggestedNameServers(): array
    {
        $parent = trim((string) config('panelze.dns.ns_parent_domain', ''));
        if ($parent === '') {
            $parent = $this->parentDomainFromAppUrl();
        }
        if ($parent === '') {
            return ['', ''];
        }

        return ['ns1.'.$parent, 'ns2.'.$parent];
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

    private function parentDomainFromAppUrl(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($host) || $host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }
        $host = strtolower($host);
        if ($this->looksLikeServerHostname($host)) {
            return '';
        }
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return '';
        }

        return implode('.', array_slice($parts, -2));
    }

    private function looksLikeServerHostname(string $host): bool
    {
        $host = strtolower(rtrim(trim($host), '.'));
        if ($host === '') {
            return true;
        }
        if (preg_match('/(^|\.)contaboserver\.net$/', $host)) {
            return true;
        }
        if (preg_match('/^vmi\d+\./', $host)) {
            return true;
        }
        $serverFqdn = strtolower(trim((string) @shell_exec('hostname -f 2>/dev/null') ?: ''));
        if ($serverFqdn !== '' && $host === $serverFqdn) {
            return true;
        }

        return false;
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
        $value = $this->settingsMap()->get($key);

        return is_string($value) ? $value : null;
    }

    /** @return Collection<string, string|null> */
    private function settingsMap(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): Collection {
            return PanelSetting::query()
                ->whereIn('key', [
                    self::KEY_NS1,
                    self::KEY_NS2,
                    self::KEY_SERVER_IP,
                    self::KEY_BIND_ENABLED,
                    self::KEY_BOOTSTRAP_DEFAULTS,
                ])
                ->pluck('value', 'key');
        });
    }
}
