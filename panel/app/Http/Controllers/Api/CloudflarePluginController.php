<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\CloudflarePluginService;
use App\Services\SafeAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CloudflarePluginController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private CloudflarePluginService $cloudflare,
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json($this->cloudflare->status($request->user()));
    }

    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_token' => ['required', 'string', 'min:20', 'max:512'],
        ]);
        $resp = $this->cloudflare->connect($request->user(), $validated['api_token']);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'connect_failed'], 422);
        }
        SafeAuditLogger::info('hostvim.cloudflare', ['action' => 'connect'], $request);

        return response()->json(['message' => __('cloudflare.connected'), 'connection' => $resp]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $this->cloudflare->disconnect($request->user());
        SafeAuditLogger::info('hostvim.cloudflare', ['action' => 'disconnect'], $request);

        return response()->json(['message' => __('cloudflare.disconnected')]);
    }

    public function zones(Request $request): JsonResponse
    {
        $name = $request->query('name');
        $resp = $this->cloudflare->listZones($request->user(), is_string($name) ? $name : null);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'zones_failed'], 422);
        }

        return response()->json(['zones' => $resp['zones'] ?? []]);
    }

    public function domainShow(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        return response()->json($this->cloudflare->domainOverview($request->user(), $domain));
    }

    public function domainLink(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'zone_id' => ['nullable', 'string', 'max:64'],
        ]);
        $resp = $this->cloudflare->linkDomain(
            $request->user(),
            $domain,
            $validated['zone_id'] ?? null
        );
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'link_failed'], 422);
        }

        return response()->json(['message' => __('cloudflare.domain_linked'), 'link' => $resp['link'] ?? null]);
    }

    public function domainUnlink(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $this->cloudflare->unlinkDomain($request->user(), $domain);

        return response()->json(['message' => __('cloudflare.domain_unlinked')]);
    }

    public function domainSsl(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'mode' => ['required', 'string', Rule::in(['off', 'flexible', 'full', 'strict'])],
        ]);
        $resp = $this->cloudflare->setSslMode($request->user(), $domain, $validated['mode']);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'ssl_failed'], 422);
        }

        return response()->json(['message' => __('cloudflare.ssl_updated'), 'ssl_mode' => $resp['value'] ?? $validated['mode']]);
    }

    public function domainDnsPush(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $domain->load('dnsRecords');
        $resp = $this->cloudflare->pushDnsToCloudflare($request->user(), $domain);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'push_failed'], 422);
        }

        return response()->json([
            'message' => __('cloudflare.dns_pushed'),
            'created' => $resp['created'] ?? 0,
            'updated' => $resp['updated'] ?? 0,
        ]);
    }

    public function domainDnsPull(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $resp = $this->cloudflare->pullDnsFromCloudflare($request->user(), $domain);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'pull_failed'], 422);
        }

        return response()->json([
            'message' => __('cloudflare.dns_pulled'),
            'imported' => $resp['imported'] ?? 0,
        ]);
    }

    public function domainDnsProxied(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'record_id' => ['required', 'string', 'max:64'],
            'proxied' => ['required', 'boolean'],
        ]);
        $resp = $this->cloudflare->setRecordProxied(
            $request->user(),
            $domain,
            $validated['record_id'],
            (bool) $validated['proxied']
        );
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'proxied_failed'], 422);
        }

        return response()->json([
            'message' => __('cloudflare.proxied_updated'),
            'record' => $resp['record'] ?? null,
        ]);
    }

    public function domainPurge(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $resp = $this->cloudflare->purgeCache($request->user(), $domain);
        if (! ($resp['ok'] ?? false)) {
            return response()->json(['message' => $resp['error'] ?? 'purge_failed'], 422);
        }

        return response()->json(['message' => __('cloudflare.cache_purged')]);
    }
}
