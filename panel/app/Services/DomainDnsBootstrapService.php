<?php

namespace App\Services;

use App\Models\Domain;

class DomainDnsBootstrapService
{
    public function __construct(
        private BindDnsService $bindDns,
        private EngineApiService $engine,
    ) {}

    /**
     * Eksik varsayılan kayıtları ekler (@, www, mail, webmail; NS glue için ns1/ns2 A).
     *
     * @return array{created: int, skipped: int, error?: string}
     */
    public function ensureDefaults(Domain $domain): array
    {
        $ip = $this->bindDns->serverIp();
        if ($ip === '') {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->plannedRecords($domain, $ip) as $row) {
            $exists = $domain->dnsRecords()
                ->where('type', $row['type'])
                ->where('name', $row['name'])
                ->exists();
            if ($exists) {
                $skipped++;

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

        if ($created > 0) {
            $this->bindDns->syncViaSudo();
        }

        return ['created' => $created, 'skipped' => $skipped];
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
}
