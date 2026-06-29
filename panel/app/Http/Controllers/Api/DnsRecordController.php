<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\Domain;
use App\Services\BindDnsService;
use App\Services\BindZoneWriter;
use App\Services\DnsHostingProvisioner;
use App\Services\DnsRecordValidator;
use App\Services\DomainDnsBootstrapService;
use App\Services\EngineApiService;
use App\Services\PanelDnsSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DnsRecordController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
        private BindZoneWriter $zoneWriter,
        private BindDnsService $bindDns,
        private DomainDnsBootstrapService $dnsBootstrap,
        private PanelDnsSettingsService $dnsSettings,
        private DnsRecordValidator $dnsValidator,
        private DnsHostingProvisioner $dnsHosting,
    ) {}

    private const DNS_TYPES = 'A,AAAA,CNAME,MX,TXT,NS,CAA,SRV,PTR';

    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $enginePreview = [];
        if ($request->user()->isAdmin()) {
            $enginePreview = $this->engine->dnsList($domain->name);
        }

        $subdomains = $domain->siteSubdomains()
            ->orderBy('hostname')
            ->get(['id', 'hostname', 'path_segment'])
            ->map(static fn ($s) => [
                'id' => $s->id,
                'hostname' => $s->hostname,
                'path_segment' => $s->path_segment,
            ]);

        return response()->json([
            'domain' => $domain->name,
            'records' => $domain->dnsRecords,
            'subdomains' => $subdomains,
            'engine_preview' => $enginePreview,
            'bind' => [
                'enabled' => $this->dnsSettings->bindEnabled(),
                'ns' => $this->bindDns->nameServers(),
                'server_ip' => $this->bindDns->serverIp(),
            ],
        ]);
    }

    /**
     * BIND zone metni (panel kayıtları + SOA/NS).
     */
    public function exportZone(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $domain->load(['dnsRecords' => static fn ($q) => $q->orderBy('type')->orderBy('name')]);
        [$ns1, $ns2] = $this->bindDns->nameServers();

        return response()->json([
            'domain' => $domain->name,
            'format' => 'bind',
            'zone' => $this->zoneWriter->zoneText(
                $domain,
                $domain->dnsRecords,
                $ns1,
                $ns2,
                $this->bindDns->serverIp(),
                (int) date('YmdH'),
            ),
        ]);
    }

    public function store(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'type' => 'required|string|in:'.self::DNS_TYPES,
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:4096',
            'ttl' => 'nullable|integer|min:60|max:604800',
            'priority' => 'nullable|integer|min:0|max:65535',
        ]);
        $validated = $this->dnsValidator->validateForStore($validated);

        $record = $domain->dnsRecords()->create($validated);

        $enginePayload = array_merge($validated, ['id' => (string) $record->id]);
        $bind = $this->triggerBindSync();
        $hosting = $this->dnsHosting->ensureFromDnsRecord($domain, $record);

        return response()->json([
            'message' => __('dns.created'),
            'record' => $record,
            'engine' => $this->engine->dnsCreate($domain->name, $enginePayload),
            'bind' => $bind,
            'hosting' => $hosting,
        ], 201);
    }

    public function bootstrapDefaults(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $result = $this->dnsBootstrap->repairAndProvision($domain);
        if (! empty($result['error'])) {
            return response()->json([
                'message' => __('dns.bootstrap_failed'),
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'message' => __('dns.bootstrap_done'),
            'created' => (int) ($result['created'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'records' => $domain->dnsRecords()->get(),
            'bind' => [
                'enabled' => $this->dnsSettings->bindEnabled(),
                'ns' => $this->bindDns->nameServers(),
                'server_ip' => $this->bindDns->serverIp(),
            ],
        ]);
    }

    public function destroy(Request $request, DnsRecord $dnsRecord): JsonResponse
    {
        $domain = $dnsRecord->domain;
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $id = (string) $dnsRecord->id;
        $dnsRecord->delete();
        $bind = $this->triggerBindSync();

        return response()->json([
            'message' => __('dns.deleted'),
            'engine' => $this->engine->dnsDeleteRecord($domain->name, $id),
            'bind' => $bind,
        ]);
    }

    /**
     * @return array{ok: bool, skipped?: bool, message?: string}
     */
    private function triggerBindSync(): array
    {
        try {
            $result = $this->bindDns->syncViaSudo();
            if (! ($result['ok'] ?? false) && ! ($result['skipped'] ?? false)) {
                Log::warning('BIND sync failed after DNS change', $result);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('BIND sync error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
