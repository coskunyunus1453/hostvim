<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\SiteDomainAlias;
use App\Services\DomainService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\HostnameReservationService;
use App\Services\SafeAuditLogger;
use App\Services\SslIssueService;
use App\Services\SubdomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(
        private DomainService $domainService,
        private HostingQuotaService $quota,
        private EngineApiService $engine,
        private SslIssueService $sslIssue,
        private SubdomainService $subdomains,
        private HostnameReservationService $hostnames,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $domains = $request->user()->domains()
            ->with(['sslCertificate', 'databases', 'siteSubdomains', 'siteDomainAliases'])
            ->latest()
            ->paginate(20);

        return response()->json($domains);
    }

    public function options(Request $request): JsonResponse
    {
        $rows = $request->user()->domains()
            ->select(['id', 'name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function switchableServerTypes(): JsonResponse
    {
        return response()->json([
            'server_types' => $this->engine->listManagedServerTypes(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'php_version' => 'nullable|string|in:7.4,8.0,8.1,8.2,8.3,8.4',
            'server_type' => 'nullable|string|in:nginx,apache,openlitespeed',
            'issue_lets_encrypt' => 'nullable|boolean',
            'lets_encrypt_email' => 'nullable|email',
        ]);

        $this->quota->ensureCanCreateDomain($request->user());

        $domain = $this->domainService->create(
            $request->user(),
            $validated['name'],
            $validated['php_version'] ?? '8.2',
            $validated['server_type'] ?? 'nginx',
        );

        $ssl = null;
        if ($request->boolean('issue_lets_encrypt')) {
            $ssl = $this->sslIssue->issue(
                $request->user(),
                $domain->fresh(),
                $validated['lets_encrypt_email'] ?? null,
                config('panelze.lets_encrypt_email') ?: null
            );
            SafeAuditLogger::info('domains.create_ssl_attempt', [
                'user_id' => $request->user()->id,
                'domain_id' => $domain->id,
                'ok' => $ssl['ok'] ?? false,
            ], $request);
        }

        return response()->json([
            'message' => __('domains.created'),
            'domain' => $domain->fresh(['sslCertificate']),
            'ssl' => $ssl,
        ], 201);
    }

    public function show(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('view', $domain);

        return response()->json([
            'domain' => $domain->load([
                'sslCertificate', 'databases', 'emailAccounts',
                'dnsRecords', 'backups',
            ]),
        ]);
    }

    public function logs(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('view', $domain);
        $lines = (int) $request->integer('lines', 200);
        $lines = max(20, min(1000, $lines));

        $result = $this->engine->getSiteLogs($domain->name, $lines);
        if (! empty($result['error'])) {
            return response()->json([
                'message' => (string) $result['error'],
            ], 502);
        }

        return response()->json($result);
    }

    public function traffic(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('view', $domain);
        $lines = (int) $request->integer('lines', 8000);
        $lines = max(100, min(20000, $lines));

        $result = $this->engine->getSiteTraffic($domain->name, $lines);
        if (! empty($result['error'])) {
            return response()->json([
                'message' => (string) $result['error'],
            ], 502);
        }

        return response()->json($result);
    }

    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('delete', $domain);

        $got = trim((string) $request->input('confirmation', ''));
        $candidates = array_values(array_unique(array_filter(array_map('trim', [
            (string) __('domains.delete_confirm_expected'),
            'SILMEKİSTİYORUM',
            'DELETEALLDATA',
        ]))));
        $ok = false;
        foreach ($candidates as $c) {
            if ($c !== '' && hash_equals($c, $got)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            return response()->json([
                'message' => __('domains.delete_confirm_mismatch'),
            ], 422);
        }

        $this->domainService->delete($domain);

        return response()->json(['message' => __('domains.deleted')]);
    }

    public function setStatus(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'status' => 'required|string|in:active,suspended',
        ]);

        try {
            $this->domainService->setPanelStatus($domain, $validated['status']);
        } catch (\Throwable $e) {
            report($e);
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 503;
            if (! is_int($code) || $code < 400 || $code > 599) {
                $code = 503;
            }
            $msg = $e->getMessage() ?: __('domains.status_updated');
            if (EngineApiService::isLikelyConnectionFailure($msg)) {
                $msg = 'Engine servisine ulasilamiyor. ENGINE_API_URL, ENGINE_INTERNAL_KEY ve panelze-engine servisini kontrol edin.';
            }

            return response()->json([
                'message' => $msg,
            ], $code);
        }

        return response()->json([
            'message' => __('domains.status_updated'),
            'domain' => $domain->fresh(),
        ]);
    }

    public function reprovision(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        try {
            $fresh = $this->domainService->reprovision($domain);
        } catch (\Throwable $e) {
            report($e);
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 503;
            if (! is_int($code) || $code < 400 || $code > 599) {
                $code = 503;
            }
            $msg = $e->getMessage() ?: __('domains.reprovision_failed');
            if (EngineApiService::isLikelyConnectionFailure($msg)) {
                $msg = 'Engine servisine ulasilamiyor. ENGINE_API_URL, ENGINE_INTERNAL_KEY ve panelze-engine servisini kontrol edin.';
            }

            return response()->json(['message' => $msg], $code);
        }

        return response()->json([
            'message' => __('domains.reprovisioned'),
            'domain' => $fresh,
        ]);
    }

    public function switchServer(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'server_type' => 'required|string|in:nginx,apache,openlitespeed',
        ]);

        try {
            $this->domainService->switchServerType($domain, $validated['server_type']);
        } catch (\Throwable $e) {
            report($e);
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 503;
            if (! is_int($code) || $code < 400 || $code > 599) {
                $code = 503;
            }
            $msg = $e->getMessage() ?: __('domains.server_switched');
            if (EngineApiService::isLikelyConnectionFailure($msg)) {
                $msg = 'Engine servisine ulasilamiyor. ENGINE_API_URL, ENGINE_INTERNAL_KEY ve panelze-engine servisini kontrol edin.';
            }

            return response()->json([
                'message' => $msg,
            ], $code);
        }

        return response()->json([
            'message' => __('domains.server_switched'),
            'domain' => $domain->fresh(),
        ]);
    }

    public function switchPhp(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'php_version' => 'required|string|in:7.4,8.0,8.1,8.2,8.3,8.4',
        ]);

        try {
            $this->domainService->switchPhpVersion($domain, $validated['php_version']);
        } catch (\Throwable $e) {
            report($e);
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 503;
            if (! is_int($code) || $code < 400 || $code > 599) {
                $code = 503;
            }
            $msg = $e->getMessage() ?: __('domains.php_switched');
            if (EngineApiService::isLikelyConnectionFailure($msg)) {
                $msg = 'Engine servisine ulasilamiyor. ENGINE_API_URL, ENGINE_INTERNAL_KEY ve panelze-engine servisini kontrol edin.';
            }

            return response()->json([
                'message' => $msg,
            ], $code);
        }

        return response()->json([
            'message' => __('domains.php_switched'),
            'domain' => $domain->fresh(),
        ]);
    }

    public function storeSubdomain(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'prefix' => 'required_without:hostname|nullable|string|max:253',
            'hostname' => 'required_without:prefix|nullable|string|max:253',
            'path_segment' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|in:7.4,8.0,8.1,8.2,8.3,8.4',
        ]);

        $sub = $this->subdomains->add($domain, $validated);

        SafeAuditLogger::info('domains.subdomain_added', [
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'hostname' => $sub->hostname,
            'path_segment' => $sub->path_segment,
        ], $request);

        return response()->json([
            'message' => __('sites.subdomain_added'),
            'subdomain' => $sub,
        ], 201);
    }

    public function destroySubdomain(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'path_segment' => 'required|string|max:255',
        ]);

        $this->subdomains->remove($domain, $validated['path_segment']);

        SafeAuditLogger::info('domains.subdomain_removed', [
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'path_segment' => $validated['path_segment'],
        ], $request);

        return response()->json(['message' => __('sites.subdomain_removed')]);
    }

    public function storeAlias(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'hostname' => 'required|string|max:253',
        ]);

        $hostLc = strtolower(trim($validated['hostname']));
        $this->hostnames->assertAliasAllowed($domain, $hostLc);

        $resp = $this->engine->siteAddAlias($domain->name, $hostLc);
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error']], 422);
        }

        try {
            $alias = SiteDomainAlias::create([
                'domain_id' => $domain->id,
                'hostname' => $hostLc,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->engine->siteRemoveAlias($domain->name, $hostLc);

            return response()->json(['message' => __('sites.alias_db_rollback')], 503);
        }

        SafeAuditLogger::info('domains.alias_added', [
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'hostname' => $hostLc,
        ], $request);

        return response()->json([
            'message' => __('sites.alias_added'),
            'alias' => $alias,
        ], 201);
    }

    public function destroyAlias(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'hostname' => 'required|string|max:253',
        ]);

        $hostLc = strtolower(trim($validated['hostname']));
        $alias = $domain->siteDomainAliases()->where('hostname', $hostLc)->first();
        if ($alias === null) {
            return response()->json(['message' => __('sites.alias_not_found')], 404);
        }

        $resp = $this->engine->siteRemoveAlias($domain->name, $hostLc);
        if (! empty($resp['error'])) {
            $err = strtolower(trim((string) $resp['error']));
            $ignorable = $err !== '' && (str_contains($err, 'not found') || str_contains($err, 'does not exist'));
            if (! $ignorable) {
                return response()->json(['message' => $resp['error']], 422);
            }
        }

        $alias->delete();

        SafeAuditLogger::info('domains.alias_removed', [
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'hostname' => $hostLc,
        ], $request);

        return response()->json(['message' => __('sites.alias_removed')]);
    }
}
