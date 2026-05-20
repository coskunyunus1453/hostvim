<?php

namespace App\Services;

use App\Models\CloudflareConnection;
use App\Models\Domain;
use App\Models\DomainCloudflareZone;
use App\Models\PluginModule;
use App\Models\User;
use App\Models\UserPluginModule;
use Illuminate\Support\Str;

class CloudflarePluginService
{
    public const SLUG = 'integration-cloudflare';

    public function __construct(
        private CloudflareApiService $api,
    ) {}

    public function isPluginActive(User $user): bool
    {
        $mod = PluginModule::query()->where('slug', self::SLUG)->first();
        if (! $mod) {
            return false;
        }

        return UserPluginModule::query()
            ->where('user_id', $user->id)
            ->where('plugin_module_id', $mod->id)
            ->where('is_active', true)
            ->exists();
    }

    public function requireActive(User $user): void
    {
        if (! $this->isPluginActive($user)) {
            abort(422, __('cloudflare.plugin_not_active'));
        }
    }

    public function connectionFor(User $user): ?CloudflareConnection
    {
        return CloudflareConnection::query()->where('user_id', $user->id)->first();
    }

    /**
     * @return array{connected: bool, email?: string|null, verified_at?: string|null}
     */
    public function status(User $user): array
    {
        $conn = $this->connectionFor($user);
        if (! $conn) {
            return ['connected' => false, 'plugin_active' => $this->isPluginActive($user)];
        }

        return [
            'connected' => true,
            'plugin_active' => $this->isPluginActive($user),
            'email' => $conn->account_email,
            'verified_at' => optional($conn->verified_at)->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, email?: string}
     */
    public function connect(User $user, string $apiToken): array
    {
        $this->requireActive($user);
        $verify = $this->api->verifyToken($apiToken);
        if (! ($verify['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($verify['error'] ?? 'invalid_token')];
        }
        $result = (array) ($verify['data']['result'] ?? []);
        $status = (string) ($result['status'] ?? '');
        if ($status !== 'active') {
            return ['ok' => false, 'error' => 'token_not_active'];
        }

        $conn = CloudflareConnection::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'api_token' => trim($apiToken),
                'account_email' => null,
                'account_id' => null,
                'verified_at' => now(),
            ]
        );

        return [
            'ok' => true,
            'email' => $conn->account_email,
            'verified_at' => optional($conn->verified_at)->toIso8601String(),
        ];
    }

    public function disconnect(User $user): void
    {
        $this->requireActive($user);
        $conn = $this->connectionFor($user);
        if ($conn) {
            DomainCloudflareZone::query()->where('cloudflare_connection_id', $conn->id)->delete();
            $conn->delete();
        }
    }

    /**
     * @return array{ok: bool, error?: string, zones?: array<int, array<string, mixed>>}
     */
    public function listZones(User $user, ?string $name = null): array
    {
        $this->requireActive($user);
        $conn = $this->connectionFor($user);
        if (! $conn) {
            return ['ok' => false, 'error' => 'not_connected'];
        }

        return $this->api->listZones((string) $conn->api_token, $name);
    }

    /**
     * @return array{ok: bool, error?: string, link?: array<string, mixed>}
     */
    public function linkDomain(User $user, Domain $domain, ?string $zoneId = null): array
    {
        $this->requireActive($user);
        if ((int) $domain->user_id !== (int) $user->id) {
            return ['ok' => false, 'error' => 'forbidden'];
        }
        $conn = $this->connectionFor($user);
        if (! $conn) {
            return ['ok' => false, 'error' => 'not_connected'];
        }

        $token = (string) $conn->api_token;
        $zone = null;
        if ($zoneId) {
            $z = $this->api->getZone($token, $zoneId);
            if (! ($z['ok'] ?? false)) {
                return ['ok' => false, 'error' => (string) ($z['error'] ?? 'zone_not_found')];
            }
            $zone = $z['zone'];
        } else {
            $list = $this->api->listZones($token, $domain->name);
            if (! ($list['ok'] ?? false)) {
                return ['ok' => false, 'error' => (string) ($list['error'] ?? 'zone_lookup_failed')];
            }
            foreach ($list['zones'] ?? [] as $z) {
                if (strtolower((string) ($z['name'] ?? '')) === strtolower($domain->name)) {
                    $zone = $z;
                    break;
                }
            }
            if (! $zone) {
                return ['ok' => false, 'error' => 'zone_not_found_for_domain'];
            }
        }

        $ssl = $this->api->getSslMode($token, (string) $zone['id']);
        $sslMode = ($ssl['ok'] ?? false) ? (string) ($ssl['value'] ?? 'full') : 'full';

        $link = DomainCloudflareZone::query()->updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'cloudflare_connection_id' => $conn->id,
                'zone_id' => (string) $zone['id'],
                'zone_name' => (string) ($zone['name'] ?? $domain->name),
                'ssl_mode' => $sslMode,
                'status' => (string) ($zone['status'] ?? 'active'),
                'linked_at' => now(),
            ]
        );

        return [
            'ok' => true,
            'link' => [
                'zone_id' => $link->zone_id,
                'zone_name' => $link->zone_name,
                'ssl_mode' => $link->ssl_mode,
                'status' => $link->status,
            ],
        ];
    }

    public function unlinkDomain(User $user, Domain $domain): void
    {
        $this->requireActive($user);
        DomainCloudflareZone::query()->where('domain_id', $domain->id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function domainOverview(User $user, Domain $domain): array
    {
        $this->requireActive($user);
        $link = DomainCloudflareZone::query()->where('domain_id', $domain->id)->first();
        if (! $link) {
            return ['linked' => false];
        }
        $conn = $link->connection;
        if (! $conn || (int) $conn->user_id !== (int) $user->id) {
            return ['linked' => false];
        }

        $token = (string) $conn->api_token;
        $zone = $this->api->getZone($token, $link->zone_id);
        $dns = $this->api->listDnsRecords($token, $link->zone_id);
        $ssl = $this->api->getSslMode($token, $link->zone_id);

        return [
            'linked' => true,
            'zone_id' => $link->zone_id,
            'zone_name' => $link->zone_name,
            'ssl_mode' => ($ssl['ok'] ?? false) ? ($ssl['value'] ?? $link->ssl_mode) : $link->ssl_mode,
            'zone_status' => ($zone['ok'] ?? false) ? ($zone['zone']['status'] ?? null) : $link->status,
            'proxied_hint' => ($zone['ok'] ?? false) ? ($zone['zone']['paused'] ?? false) : false,
            'dns_records' => ($dns['ok'] ?? false) ? ($dns['records'] ?? []) : [],
            'dns_error' => ($dns['ok'] ?? false) ? null : ($dns['error'] ?? null),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, created?: int, updated?: int}
     */
    public function pushDnsToCloudflare(User $user, Domain $domain): array
    {
        $link = $this->resolveLink($user, $domain);
        if (! $link) {
            return ['ok' => false, 'error' => 'not_linked'];
        }
        $token = (string) $link->connection->api_token;
        $existing = $this->api->listDnsRecords($token, $link->zone_id);
        if (! ($existing['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($existing['error'] ?? 'dns_list_failed')];
        }
        $byKey = [];
        foreach ($existing['records'] ?? [] as $r) {
            $key = strtoupper((string) ($r['type'] ?? '')).'|'.strtolower((string) ($r['name'] ?? ''));
            $byKey[$key] = $r;
        }

        $created = 0;
        $updated = 0;
        foreach ($domain->dnsRecords as $rec) {
            $type = strtoupper(trim((string) $rec->type));
            $name = $this->cfRecordName($domain->name, (string) $rec->name);
            $key = $type.'|'.strtolower($name);
            $payload = [
                'type' => $type,
                'name' => $name,
                'content' => trim((string) $rec->value),
                'ttl' => max(1, (int) ($rec->ttl ?: 3600)),
                'proxied' => in_array($type, ['A', 'AAAA', 'CNAME'], true),
            ];
            if ($type === 'MX') {
                $payload['priority'] = (int) ($rec->priority ?? 10);
            }
            if (isset($byKey[$key])) {
                $id = (string) $byKey[$key]['id'];
                $resp = $this->api->updateDnsRecord($token, $link->zone_id, $id, $payload);
                if ($resp['ok'] ?? false) {
                    $updated++;
                }
            } else {
                $resp = $this->api->createDnsRecord($token, $link->zone_id, $payload);
                if ($resp['ok'] ?? false) {
                    $created++;
                }
            }
        }

        return ['ok' => true, 'created' => $created, 'updated' => $updated];
    }

    /**
     * @return array{ok: bool, error?: string, imported?: int}
     */
    public function pullDnsFromCloudflare(User $user, Domain $domain): array
    {
        $link = $this->resolveLink($user, $domain);
        if (! $link) {
            return ['ok' => false, 'error' => 'not_linked'];
        }
        $token = (string) $link->connection->api_token;
        $list = $this->api->listDnsRecords($token, $link->zone_id);
        if (! ($list['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($list['error'] ?? 'dns_list_failed')];
        }

        $imported = 0;
        $zone = strtolower(trim($domain->name));
        foreach ($list['records'] ?? [] as $r) {
            $type = strtoupper((string) ($r['type'] ?? ''));
            if (! in_array($type, ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS', 'SRV'], true)) {
                continue;
            }
            $cfName = strtolower((string) ($r['name'] ?? ''));
            $shortName = $this->panelRecordName($zone, $cfName);
            $value = (string) ($r['content'] ?? '');
            $ttl = (int) ($r['ttl'] ?? 3600);
            $priority = $type === 'MX' ? (int) ($r['priority'] ?? 10) : null;

            $domain->dnsRecords()->updateOrCreate(
                ['type' => $type, 'name' => $shortName],
                ['value' => $value, 'ttl' => $ttl, 'priority' => $priority]
            );
            $imported++;
        }

        return ['ok' => true, 'imported' => $imported];
    }

    /**
     * @return array{ok: bool, error?: string, record?: array<string, mixed>}
     */
    public function setRecordProxied(User $user, Domain $domain, string $recordId, bool $proxied): array
    {
        $link = $this->resolveLink($user, $domain);
        if (! $link) {
            return ['ok' => false, 'error' => 'not_linked'];
        }

        return $this->api->updateDnsRecord(
            (string) $link->connection->api_token,
            $link->zone_id,
            $recordId,
            ['proxied' => $proxied]
        );
    }

    /**
     * @return array{ok: bool, error?: string, value?: string}
     */
    public function setSslMode(User $user, Domain $domain, string $mode): array
    {
        $link = $this->resolveLink($user, $domain);
        if (! $link) {
            return ['ok' => false, 'error' => 'not_linked'];
        }
        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['off', 'flexible', 'full', 'strict'], true)) {
            return ['ok' => false, 'error' => 'invalid_ssl_mode'];
        }
        $resp = $this->api->setSslMode((string) $link->connection->api_token, $link->zone_id, $mode);
        if ($resp['ok'] ?? false) {
            $link->ssl_mode = $mode;
            $link->save();
        }

        return $resp;
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function purgeCache(User $user, Domain $domain): array
    {
        $link = $this->resolveLink($user, $domain);
        if (! $link) {
            return ['ok' => false, 'error' => 'not_linked'];
        }

        return $this->api->purgeEverything((string) $link->connection->api_token, $link->zone_id);
    }

    private function resolveLink(User $user, Domain $domain): ?DomainCloudflareZone
    {
        $this->requireActive($user);
        if ((int) $domain->user_id !== (int) $user->id) {
            return null;
        }

        return DomainCloudflareZone::query()
            ->with('connection')
            ->where('domain_id', $domain->id)
            ->first();
    }

    private function cfRecordName(string $zone, string $panelName): string
    {
        $zone = strtolower(trim($zone));
        $n = strtolower(trim($panelName));
        if ($n === '' || $n === '@') {
            return $zone;
        }
        if (Str::endsWith($n, '.'.$zone) || $n === $zone) {
            return $n;
        }
        if (str_contains($n, '.')) {
            return $n;
        }

        return $n.'.'.$zone;
    }

    private function panelRecordName(string $zone, string $cfName): string
    {
        $zone = strtolower(trim($zone));
        $cfName = strtolower(trim(rtrim($cfName, '.')));
        if ($cfName === $zone) {
            return '@';
        }
        $suffix = '.'.$zone;
        if (Str::endsWith($cfName, $suffix)) {
            return substr($cfName, 0, -strlen($suffix));
        }

        return $cfName;
    }
}
