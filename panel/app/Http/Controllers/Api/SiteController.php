<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\SiteDomainAlias;
use App\Models\SiteSubdomain;
use App\Services\DomainService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\HostnameReservationService;
use App\Services\SafeAuditLogger;
use App\Services\SslIssueService;
use App\Services\SubdomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Modern “Site” API — her site bir `Domain` kaydıdır; alt alan ve alias çocuk tablolarda.
 */
class SiteController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private DomainService $domainService,
        private EngineApiService $engine,
        private HostnameReservationService $hostnames,
        private HostingQuotaService $quota,
        private SslIssueService $sslIssue,
        private SubdomainService $subdomains,
    ) {}

    public function list(Request $request): JsonResponse
    {
        $paginator = $request->user()->domains()
            ->with(['sslCertificate', 'siteSubdomains', 'siteDomainAliases'])
            ->latest()
            ->paginate(30);

        $paginator->getCollection()->transform(fn (Domain $d) => $this->serializeSite($d));

        return response()->json($paginator);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:253',
            'php_version' => 'nullable|string|in:7.4,8.0,8.1,8.2,8.3,8.4',
            'server_type' => 'nullable|string|in:nginx,apache,openlitespeed',
            'issue_lets_encrypt' => 'nullable|boolean',
            'lets_encrypt_email' => 'nullable|email',
        ]);

        $this->quota->ensureCanCreateDomain($request->user());
        $this->hostnames->assertPrimaryDomainForUser($request->user(), $validated['domain']);

        $domain = $this->domainService->create(
            $request->user(),
            $validated['domain'],
            $validated['php_version'] ?? '8.2',
            $validated['server_type'] ?? 'nginx',
        );

        SafeAuditLogger::info('sites.created', [
            'user_id' => $request->user()->id,
            'site_id' => $domain->id,
            'domain' => $domain->name,
        ], $request);

        $ssl = null;
        if ($request->boolean('issue_lets_encrypt')) {
            $ssl = $this->sslIssue->issue(
                $request->user(),
                $domain->fresh(),
                $validated['lets_encrypt_email'] ?? null,
                config('panelze.lets_encrypt_email') ?: null
            );
            SafeAuditLogger::info('sites.create_ssl_attempt', [
                'user_id' => $request->user()->id,
                'site_id' => $domain->id,
                'ok' => $ssl['ok'] ?? false,
            ], $request);
        }

        return response()->json([
            'message' => __('sites.created'),
            'site' => $this->serializeSite($domain->fresh(['sslCertificate', 'siteSubdomains', 'siteDomainAliases'])),
            'ssl' => $ssl,
        ], 201);
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:domains,id',
            'confirmation' => 'required|string',
        ]);

        $domain = Domain::query()->findOrFail($validated['site_id']);
        $this->authorize('delete', $domain);

        $got = trim((string) $validated['confirmation']);
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

        SafeAuditLogger::info('sites.delete', [
            'user_id' => $request->user()->id,
            'site_id' => $domain->id,
            'domain' => $domain->name,
        ], $request);

        $this->domainService->delete($domain);

        return response()->json(['message' => __('sites.deleted')]);
    }

    public function addSubdomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:domains,id',
            'hostname' => 'nullable|string|max:253',
            'prefix' => 'nullable|string|max:253',
            'path_segment' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|in:7.4,8.0,8.1,8.2,8.3,8.4',
        ]);

        if (trim((string) ($validated['hostname'] ?? '')) === '' && trim((string) ($validated['prefix'] ?? '')) === '') {
            return response()->json(['message' => __('domains.subdomain_prefix_required')], 422);
        }

        $site = Domain::query()->findOrFail($validated['site_id']);
        $this->authorize('update', $site);

        try {
            $sub = $this->subdomains->add($site, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        SafeAuditLogger::info('sites.subdomain_added', [
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'hostname' => $sub->hostname,
            'path_segment' => $sub->path_segment,
        ], $request);

        return response()->json([
            'message' => __('sites.subdomain_added'),
            'subdomain' => $sub,
        ], 201);
    }

    public function removeSubdomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:domains,id',
            'path_segment' => 'required|string|max:255',
        ]);

        $site = Domain::query()->findOrFail($validated['site_id']);
        $this->authorize('update', $site);

        try {
            $this->subdomains->remove($site, $validated['path_segment']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? $e->getMessage(),
            ], 422);
        }

        SafeAuditLogger::info('sites.subdomain_removed', [
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'path_segment' => $validated['path_segment'],
        ], $request);

        return response()->json(['message' => __('sites.subdomain_removed')]);
    }

    public function addDomainAlias(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:domains,id',
            'hostname' => 'required|string|max:253',
        ]);

        $site = Domain::query()->findOrFail($validated['site_id']);
        $this->authorize('update', $site);

        $hostLc = strtolower(trim($validated['hostname']));
        $this->hostnames->assertAliasAllowed($site, $hostLc);

        $resp = $this->engine->siteAddAlias($site->name, $hostLc);
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error']], 422);
        }

        try {
            SiteDomainAlias::create([
                'domain_id' => $site->id,
                'hostname' => $hostLc,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->engine->siteRemoveAlias($site->name, $hostLc);

            return response()->json(['message' => __('sites.alias_db_rollback')], 503);
        }

        SafeAuditLogger::info('sites.alias_added', [
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'hostname' => $hostLc,
        ], $request);

        return response()->json([
            'message' => __('sites.alias_added'),
            'alias' => SiteDomainAlias::query()
                ->where('domain_id', $site->id)
                ->where('hostname', $hostLc)
                ->first(),
        ], 201);
    }

    public function removeDomainAlias(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:domains,id',
            'hostname' => 'required|string|max:253',
        ]);

        $site = Domain::query()->findOrFail($validated['site_id']);
        $this->authorize('update', $site);

        $hostLc = strtolower(trim($validated['hostname']));
        $alias = $site->siteDomainAliases()->where('hostname', $hostLc)->first();
        if ($alias === null) {
            return response()->json(['message' => __('sites.alias_not_found')], 404);
        }

        $resp = $this->engine->siteRemoveAlias($site->name, $hostLc);
        if (! empty($resp['error']) && ! $this->ignorableEngineNotFound($resp['error'])) {
            return response()->json(['message' => $resp['error']], 422);
        }

        $alias->delete();

        SafeAuditLogger::info('sites.alias_removed', [
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'hostname' => $hostLc,
        ], $request);

        return response()->json(['message' => __('sites.alias_removed')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSite(Domain $d): array
    {
        return [
            'id' => $d->id,
            'primary_domain' => $d->name,
            'root_path' => $d->document_root,
            'status' => $d->status,
            'ssl_enabled' => (bool) $d->ssl_enabled,
            'ssl_expiry' => $d->ssl_expiry,
            'php_version' => $d->php_version,
            'server_type' => $d->server_type,
            'is_primary_account_domain' => (bool) $d->is_primary,
            'subdomains' => $d->siteSubdomains->map(fn (SiteSubdomain $s) => [
                'id' => $s->id,
                'hostname' => $s->hostname,
                'path_segment' => $s->path_segment,
                'document_root' => $s->document_root,
            ])->values(),
            'aliases' => $d->siteDomainAliases->map(fn (SiteDomainAlias $a) => [
                'id' => $a->id,
                'hostname' => $a->hostname,
            ])->values(),
        ];
    }

    private function ignorableEngineNotFound(mixed $error): bool
    {
        $e = strtolower(trim((string) $error));

        return $e !== '' && (str_contains($e, 'not found') || str_contains($e, 'does not exist'));
    }
}
