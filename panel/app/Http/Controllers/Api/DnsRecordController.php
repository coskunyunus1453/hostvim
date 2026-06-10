<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\Domain;
use App\Services\BindDnsService;
use App\Services\BindZoneWriter;
use App\Services\EngineApiService;
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
    ) {}

    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        return response()->json([
            'records' => $domain->dnsRecords,
            'engine_preview' => $this->engine->dnsList($domain->name),
            'bind' => [
                'enabled' => (bool) config('hostvim.dns.bind_enabled', true),
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
            'type' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'ttl' => 'nullable|integer|min:60',
            'priority' => 'nullable|integer',
        ]);

        $record = $domain->dnsRecords()->create($validated);

        $enginePayload = array_merge($validated, ['id' => (string) $record->id]);
        $bind = $this->triggerBindSync();

        return response()->json([
            'message' => __('dns.created'),
            'record' => $record,
            'engine' => $this->engine->dnsCreate($domain->name, $enginePayload),
            'bind' => $bind,
        ], 201);
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
