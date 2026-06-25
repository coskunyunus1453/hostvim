<?php

namespace App\Services;

use App\Models\DnsRecord;
use App\Models\Domain;
use App\Models\SiteSubdomain;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * DNS kaydı eklendiğinde alt alan barındırmasını otomatik hazırlar (vhost, PHP, Laravel public kökü).
 */
class DnsHostingProvisioner
{
    public function __construct(
        private PanelDnsSettingsService $dnsSettings,
        private SubdomainService $subdomains,
        private EngineApiService $engine,
    ) {}

    /**
     * Sunucu IP'sine işaret eden alt alan A kaydı için site alt alanı oluşturur veya web yığınını senkronlar.
     *
     * @return array{action: string, subdomain?: SiteSubdomain}|null
     */
    public function ensureFromDnsRecord(Domain $domain, DnsRecord $record): ?array
    {
        if (strtoupper((string) $record->type) !== 'A') {
            return null;
        }

        if (! $this->dnsSettings->hasServerIp()) {
            return null;
        }

        $serverIp = $this->dnsSettings->serverIp();
        $value = trim((string) $record->value);
        if ($serverIp === '' || $value !== $serverIp) {
            return null;
        }

        $apex = strtolower(trim($domain->name));
        $name = strtolower(trim((string) $record->name));
        if ($name === '' || $name === '@' || $name === '*') {
            return null;
        }

        $fqdn = str_ends_with($name, '.'.$apex) ? $name : $name.'.'.$apex;
        if ($fqdn === $apex) {
            return null;
        }

        $existing = $domain->siteSubdomains()->where('hostname', $fqdn)->first();
        if ($existing !== null) {
            $this->syncSubdomainWeb($domain, $existing);

            return ['action' => 'synced', 'subdomain' => $existing->fresh()];
        }

        try {
            $sub = $this->subdomains->add($domain, [
                'hostname' => $fqdn,
                'prefix' => $name,
            ]);
        } catch (ValidationException $e) {
            Log::info('dns_subdomain_auto_provision_skipped', [
                'domain_id' => $domain->id,
                'hostname' => $fqdn,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }

        return ['action' => 'created', 'subdomain' => $sub];
    }

    public function syncSubdomainWeb(Domain $domain, SiteSubdomain $sub): void
    {
        $resp = $this->engine->syncSubdomainWeb($domain->name, $sub->path_segment);
        if (! empty($resp['php_version']) && is_string($resp['php_version'])) {
            $sub->update(['php_version' => (string) $resp['php_version']]);
        }
    }
}
