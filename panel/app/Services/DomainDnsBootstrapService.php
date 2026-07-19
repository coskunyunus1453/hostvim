<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\DnsRecord;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DomainDnsBootstrapService
{
    public function __construct(
        private BindDnsService $bindDns,
        private EngineApiService $engine,
        private DnsRecordValidator $validator,
        private PanelDnsSettingsService $dnsSettings,
        private MailDkimService $dkim,
    ) {}

    /**
     * Hatalı kayıtları temizler/düzeltir ve eksik varsayılanları ekler.
     *
     * @return array{repaired: int, removed: int, created: int, skipped: int, error?: string}
     */
    public function repairAndProvision(Domain $domain): array
    {
        if (! $this->dnsSettings->hasServerIp()) {
            return [
                'repaired' => 0,
                'removed' => 0,
                'created' => 0,
                'skipped' => 0,
                'error' => 'server_ip_not_configured',
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

        // Zone dosyası transaction commit öncesi harici artisan ile yazılamaz; kuyruk kullan.
        if ($this->dnsSettings->bindEnabled()) {
            $this->bindDns->scheduleSync();
        }

        return [
            'repaired' => $repaired,
            'removed' => $removed,
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Tek bir alt alan adı için parent zone'da A kaydını garanti eder.
     *
     * @return array{created: int, skipped: int, error?: string}
     */
    public function ensureSubdomainDnsRecord(Domain $domain, string $hostname): array
    {
        if (! $this->dnsSettings->hasServerIp()) {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $ip = $this->bindDns->serverIp();
        if ($ip === '') {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $label = $this->subdomainLabel($domain->name, $hostname);
        if ($label === null || $label === '' || $label === '@') {
            return ['created' => 0, 'skipped' => 0];
        }

        [$created, $skipped] = $this->applyPlannedRecord($domain, [
            'type' => 'A',
            'name' => $label,
            'value' => $ip,
            'ttl' => 3600,
        ]);

        if ($created > 0 && $this->dnsSettings->bindEnabled()) {
            $this->bindDns->scheduleSync();
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Alan adı kaydı veya manuel ekleme sonrası panel Domain + varsayılan DNS + BIND zone garantisi.
     * Hosting kalemi aynı siparişte sonra gelse bile idempotent çalışır.
     */
    public function ensureAuthoritativeZone(User $user, string $domainName): ?Domain
    {
        $domainName = strtolower(rtrim(trim($domainName), '.'));
        if ($domainName === '') {
            return null;
        }

        $domain = Domain::query()->where('name', $domainName)->first();
        if ($domain !== null) {
            if ((int) $domain->user_id !== (int) $user->id) {
                return null;
            }
        } else {
            $fallbackRoot = rtrim((string) config('panelze.hosting_web_root'), DIRECTORY_SEPARATOR);
            $provisionalRoot = $fallbackRoot !== ''
                ? $fallbackRoot.DIRECTORY_SEPARATOR.$domainName.DIRECTORY_SEPARATOR.'public_html'
                : $domainName.DIRECTORY_SEPARATOR.'public_html';

            $domain = $user->domains()->create([
                'name' => $domainName,
                'document_root' => $provisionalRoot,
                'php_version' => (string) config('panelze.default_php_version', '8.2'),
                'server_type' => 'nginx',
                'status' => 'active',
                'is_primary' => ! $user->domains()->exists(),
            ]);
        }

        $result = $this->repairAndProvision($domain);
        if (! empty($result['error'])) {
            Log::warning('panelze.dns.ensure_authoritative_zone_failed', [
                'domain' => $domainName,
                'user_id' => $user->id,
                'error' => $result['error'],
            ]);
        }

        return $domain->fresh();
    }

    /**
     * Alt alan adı silinince parent zone'daki A kaydını/kayıtlarını temizler.
     */
    public function removeSubdomainDnsRecord(Domain $domain, string $hostname): void
    {
        $label = $this->subdomainLabel($domain->name, $hostname);
        if ($label === null || $label === '' || $label === '@') {
            return;
        }

        $removed = 0;
        foreach ($domain->dnsRecords()->where('type', 'A')->where('name', $label)->get() as $record) {
            $this->deleteRecord($domain, $record);
            $removed++;
        }

        if ($removed > 0 && $this->dnsSettings->bindEnabled()) {
            $this->bindDns->scheduleSync();
        }
    }

    /**
     * Hostname'in parent zone'a göre etiketini döndürür (yardim.example.com → "yardim").
     */
    private function subdomainLabel(string $zone, string $hostname): ?string
    {
        $zone = strtolower(rtrim(trim($zone), '.'));
        $host = strtolower(rtrim(trim($hostname), '.'));

        if ($zone === '' || $host === '') {
            return null;
        }

        if ($host === $zone) {
            return '@';
        }

        $suffix = '.'.$zone;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $label = substr($host, 0, -strlen($suffix));

        return $label !== '' ? $label : null;
    }

    /**
     * @return array{created: int, skipped: int, error?: string}
     */
    public function ensureDefaults(Domain $domain, bool $syncBind = true): array
    {
        if (! $this->dnsSettings->hasServerIp()) {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $ip = $this->bindDns->serverIp();
        if ($ip === '') {
            return ['created' => 0, 'skipped' => 0, 'error' => 'server_ip_not_configured'];
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->plannedRecords($domain, $ip) as $row) {
            [$rowCreated, $rowSkipped] = $this->applyPlannedRecord($domain, $row);
            $created += $rowCreated;
            $skipped += $rowSkipped;
        }

        if ($domain->emailAccounts()->exists()) {
            $zone = strtolower(trim($domain->name));
            $this->dkim->syncDomainKeys([$zone]);
            $dkimTxt = $this->dkim->txtRecordValueForDomain($zone);
            if ($dkimTxt !== null) {
                [$rowCreated, $rowSkipped] = $this->applyPlannedRecord($domain, [
                    'type' => 'TXT',
                    'name' => 'default._domainkey',
                    'value' => $dkimTxt,
                    'ttl' => 3600,
                ]);
                $created += $rowCreated;
                $skipped += $rowSkipped;
            }
        }

        if ($syncBind && $this->dnsSettings->bindEnabled()) {
            $this->bindDns->scheduleSync();
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

        $domain->loadMissing('siteSubdomains');
        foreach ($domain->siteSubdomains as $sub) {
            $label = $this->subdomainLabel($zone, (string) $sub->hostname);
            if ($label !== null && $label !== '' && $label !== '@') {
                $records[] = ['type' => 'A', 'name' => $label, 'value' => $ip, 'ttl' => 3600];
            }
        }

        if ($this->dnsSettings->isConfigured()) {
            [$ns1, $ns2] = $this->bindDns->nameServers();
            foreach (array_filter([$ns1, $ns2]) as $ns) {
                $glue = $this->glueHostForZone($ns, $zone);
                if ($glue === null) {
                    continue;
                }
                $records[] = ['type' => 'A', 'name' => $glue, 'value' => $ip, 'ttl' => 3600];
            }
        }

        $mxHost = 'mail.'.$zone;
        $records[] = [
            'type' => 'MX',
            'name' => '@',
            'value' => $mxHost,
            'priority' => 10,
            'ttl' => 3600,
        ];
        $records[] = [
            'type' => 'TXT',
            'name' => '@',
            'value' => "v=spf1 mx a ip4:{$ip} ~all",
            'ttl' => 3600,
        ];
        $records[] = [
            'type' => 'TXT',
            'name' => '_dmarc',
            'value' => "v=DMARC1; p=quarantine; rua=mailto:postmaster@{$zone}",
            'ttl' => 3600,
        ];

        return $records;
    }

    /**
     * @param  array{type: string, name: string, value: string, ttl?: int, priority?: int|null}  $row
     * @return array{0: int, 1: int}
     */
    private function applyPlannedRecord(Domain $domain, array $row): array
    {
        $existing = $domain->dnsRecords()
            ->where('type', $row['type'])
            ->where('name', $row['name'])
            ->first();

        if ($existing) {
            $needsUpdate = false;

            if ($row['type'] === 'A' && ! $this->validator->isValidAValue((string) $existing->value)) {
                $needsUpdate = true;
            } elseif (in_array($row['type'], ['MX', 'TXT'], true)) {
                $needsUpdate = (string) $existing->value !== (string) $row['value']
                    || (int) ($existing->priority ?? 0) !== (int) ($row['priority'] ?? 0);
            }

            if ($needsUpdate) {
                $existing->update([
                    'value' => $row['value'],
                    'priority' => $row['priority'] ?? null,
                    'ttl' => $row['ttl'] ?? 3600,
                ]);
                $this->syncEngineRecord($domain, $existing->fresh());

                return [1, 0];
            }

            return [0, 1];
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

        return [1, 0];
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
