<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\DnsRecord;

class DomainDnsBootstrapService
{
    public function __construct(
        private BindDnsService $bindDns,
        private EngineApiService $engine,
        private DnsRecordValidator $validator,
        private PanelDnsSettingsService $dnsSettings,
    ) {}

    /**
     * Hatalı kayıtları temizler/düzeltir ve eksik varsayılanları ekler.
     *
     * @return array{repaired: int, removed: int, created: int, skipped: int, error?: string}
     */
    public function repairAndProvision(Domain $domain): array
    {
        if (! $this->dnsSettings->isConfigured()) {
            return [
                'repaired' => 0,
                'removed' => 0,
                'created' => 0,
                'skipped' => 0,
                'error' => 'dns_not_configured',
            ];
        }

        $ip = $this->bindDns->serverIp();
        if ($ip === '') {
            return [
                'repaired' => 0,
                'removed' => 0,
                'created' => 0,
                'skipped' => 0,
                'error' => 'server_ip_not_configured',
            ];
        }

        $removed = 0;
        $repaired = 0;
        $domain->loadMissing('dnsRecords');

        foreach ($domain->dnsRecords as $record) {
            if ($this->validator->isApexNsRecord((string) $record->type, (string) $record->name)) {
                $this->deleteRecord($domain, $record);
                $removed++;

                continue;
            }

            if (strtoupper((string) $record->type) === 'A' && ! $this->validator->isValidAValue((string) $record->value)) {
                $record->update(['value' => $ip]);
                $this->syncEngineRecord($domain, $record->fresh());
                $repaired++;
            }
        }

        $defaults = $this->ensureDefaults($domain, false);
        $created = (int) ($defaults['created'] ?? 0);
        $skipped = (int) ($defaults['skipped'] ?? 0);

        if ($removed > 0 || $repaired > 0 || $created > 0) {
            $this->bindDns->syncViaSudo();
        }

        return [
            'repaired' => $repaired,
            'removed' => $removed,
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{created: int, skipped: int, error?: string}
     */
    public function ensureDefaults(Domain $domain, bool $syncBind = true): array
    {
        if (! $this->dnsSettings->isConfigured()) {
            return ['created' => 0, 'skipped' => 0, 'error' => 'dns_not_configured'];
        }

        $ip = $this->bindDns->serverIp();
        if ($ip === '') {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->plannedRecords($domain, $ip) as $row) {
            $existing = $domain->dnsRecords()
                ->where('type', $row['type'])
                ->where('name', $row['name'])
                ->first();

            if ($existing) {
                if ($row['type'] === 'A' && ! $this->validator->isValidAValue((string) $existing->value)) {
                    $existing->update(['value' => $row['value']]);
                    $this->syncEngineRecord($domain, $existing->fresh());
                    $created++;
                } else {
                    $skipped++;
                }

                continue;
            }

            $record = $domain->dnsRecords()->create([
                'type' => $row['type'],
                'name' => $row['name'],
                'value' => $row['value'],
                'ttl' => $row['ttl'] ?? 3600,
                'priority' => $row['priority'] ?? null,
            ]);

            $this->engine->dnsCreate($domain->name, array_merge($row, [
                'id' => (string) $record->id,
                'ttl' => $row['ttl'] ?? 3600,
                'priority' => $row['priority'] ?? null,
            ]));
            $created++;
        }

        if ($syncBind && $created > 0) {
            $this->bindDns->syncViaSudo();
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function repairAllActiveDomains(): array
    {
        $totals = ['repaired' => 0, 'removed' => 0, 'created' => 0, 'domains' => 0];
        $domains = Domain::query()->where('status', 'active')->orderBy('name')->get();
        foreach ($domains as $domain) {
            $result = $this->repairAndProvision($domain);
            if (! empty($result['error'])) {
                continue;
            }
            $totals['domains']++;
            $totals['repaired'] += (int) ($result['repaired'] ?? 0);
            $totals['removed'] += (int) ($result['removed'] ?? 0);
            $totals['created'] += (int) ($result['created'] ?? 0);
        }

        return $totals;
    }

    /**
     * @return list<array{type: string, name: string, value: string, ttl?: int, priority?: int|null}>
     */
    private function plannedRecords(Domain $domain, string $ip): array
    {
        $zone = strtolower(trim($domain->name));
        $records = [
            ['type' => 'A', 'name' => '@', 'value' => $ip, 'ttl' => 3600],
            ['type' => 'A', 'name' => 'www', 'value' => $ip, 'ttl' => 3600],
            ['type' => 'A', 'name' => 'mail', 'value' => $ip, 'ttl' => 3600],
            ['type' => 'A', 'name' => 'webmail', 'value' => $ip, 'ttl' => 3600],
        ];

        [$ns1, $ns2] = $this->bindDns->nameServers();
        foreach (array_filter([$ns1, $ns2]) as $ns) {
            $glue = $this->glueHostForZone($ns, $zone);
            if ($glue === null) {
                continue;
            }
            $records[] = ['type' => 'A', 'name' => $glue, 'value' => $ip, 'ttl' => 3600];
        }

        return $records;
    }

    private function glueHostForZone(string $ns, string $zone): ?string
    {
        $ns = strtolower(rtrim(trim($ns), '.'));
        $zone = strtolower(rtrim($zone, '.'));

        if ($ns === $zone || ! str_ends_with($ns, '.'.$zone)) {
            return null;
        }

        $label = substr($ns, 0, -strlen('.'.$zone));
        if ($label === '' || str_contains($label, '.')) {
            return null;
        }

        return $label;
    }

    private function deleteRecord(Domain $domain, DnsRecord $record): void
    {
        $id = (string) $record->id;
        $record->delete();
        $this->engine->dnsDeleteRecord($domain->name, $id);
    }

    private function syncEngineRecord(Domain $domain, DnsRecord $record): void
    {
        $this->engine->dnsCreate($domain->name, [
            'id' => (string) $record->id,
            'type' => $record->type,
            'name' => $record->name,
            'value' => $record->value,
            'ttl' => $record->ttl ?? 3600,
            'priority' => $record->priority,
        ]);
    }
}
