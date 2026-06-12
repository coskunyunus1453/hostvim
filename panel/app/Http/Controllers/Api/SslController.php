<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\SslCertificate;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\HostingSiteTargetResolver;
use App\Services\SslIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SslController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
        private SslIssueService $sslIssue,
        private HostingSiteTargetResolver $targets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $domainIds = $request->user()->domains()->pluck('id');
        $certs = SslCertificate::whereIn('domain_id', $domainIds)
            ->with([
                'domain:id,name,force_https,ssl_enabled',
                'siteSubdomain:id,hostname',
            ])
            ->get();

        return response()->json(['certificates' => $certs]);
    }

    public function issue(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'nullable|email',
            'subdomain_id' => 'nullable|integer',
        ]);

        $result = $this->sslIssue->issue(
            $request->user(),
            $domain,
            $validated['email'] ?? null,
            config('panelze.lets_encrypt_email') ?: null,
            isset($validated['subdomain_id']) ? (int) $validated['subdomain_id'] : null,
        );

        if (! $result['ok']) {
            return response()->json(array_filter([
                'message' => $result['message'] ?? null,
                'certificate' => $result['certificate'] ?? null,
                'engine' => $result['engine'] ?? null,
                'diagnostics' => $result['diagnostics'] ?? null,
            ], fn ($v) => $v !== null), $result['http_status']);
        }

        return response()->json(array_filter([
            'message' => $result['message'] ?? __('ssl.issued'),
            'certificate' => $result['certificate'] ?? null,
            'engine' => $result['engine'] ?? null,
            'diagnostics' => $result['diagnostics'] ?? null,
            'hostname' => $result['hostname'] ?? null,
        ], fn ($v) => $v !== null));
    }

    public function renew(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'subdomain_id' => 'nullable|integer',
        ]);

        $this->quota->ensureSslAllowed($request->user());

        $target = $this->targets->forDomain(
            $domain,
            isset($validated['subdomain_id']) ? (int) $validated['subdomain_id'] : null,
        );

        $cert = SslCertificate::query()
            ->where('domain_id', $domain->id)
            ->where('site_subdomain_id', $target->subdomain?->id)
            ->first();
        if (! $cert) {
            return response()->json(['message' => __('ssl.missing')], 404);
        }

        $engine = $this->engine->renewSSL(
            $target->hostname,
            $target->isSubdomain() ? $target->engineSiteName : null,
            $target->subdomain?->path_segment,
        );
        if (! empty($engine['error'])) {
            return response()->json([
                'message' => $engine['error'],
                'certificate' => $cert->fresh(),
            ], 503);
        }

        return response()->json([
            'message' => __('ssl.renewed'),
            'engine' => $engine,
            'certificate' => $cert->fresh(),
        ]);
    }

    public function revoke(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'subdomain_id' => 'nullable|integer',
        ]);

        $target = $this->targets->forDomain(
            $domain,
            isset($validated['subdomain_id']) ? (int) $validated['subdomain_id'] : null,
        );

        $engine = $this->engine->revokeSSL(
            $target->hostname,
            $target->isSubdomain() ? $target->engineSiteName : null,
            $target->subdomain?->path_segment,
        );
        if (! empty($engine['error'])) {
            return response()->json(['message' => $engine['error']], 503);
        }

        SslCertificate::query()
            ->where('domain_id', $domain->id)
            ->where('site_subdomain_id', $target->subdomain?->id)
            ->delete();

        if ($target->isSubdomain() && $target->subdomain) {
            $target->subdomain->update([
                'ssl_enabled' => false,
                'ssl_expiry' => null,
            ]);
        } else {
            $domain->update([
                'ssl_enabled' => false,
                'ssl_expiry' => null,
            ]);
        }

        return response()->json([
            'message' => __('ssl.revoked'),
            'engine' => $engine,
        ]);
    }

    public function manual(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'certificate' => 'required|string|min:64',
            'private_key' => 'required|string|min:64',
        ]);
        $engine = $this->engine->uploadManualSSL(
            $domain->name,
            $validated['certificate'],
            $validated['private_key']
        );
        if (! empty($engine['error'])) {
            return response()->json(['message' => $engine['error']], 422);
        }

        $issuedAt = null;
        $expiresAt = null;
        $parsed = @openssl_x509_parse($validated['certificate']);
        if (is_array($parsed)) {
            if (isset($parsed['validFrom_time_t']) && is_numeric($parsed['validFrom_time_t'])) {
                $issuedAt = date('Y-m-d H:i:s', (int) $parsed['validFrom_time_t']);
            }
            if (isset($parsed['validTo_time_t']) && is_numeric($parsed['validTo_time_t'])) {
                $expiresAt = date('Y-m-d H:i:s', (int) $parsed['validTo_time_t']);
            }
        }

        $cert = SslCertificate::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'provider' => 'manual',
                'type' => 'uploaded',
                'status' => 'active',
                'issued_at' => $issuedAt ?? now(),
                'expires_at' => $expiresAt,
                'auto_renew' => false,
            ]
        );
        $domain->update([
            'ssl_enabled' => true,
            'ssl_expiry' => $cert->expires_at,
            'force_https' => true,
        ]);

        return response()->json([
            'message' => __('ssl.manual_uploaded'),
            'certificate' => $cert->fresh(),
            'engine' => $engine,
        ]);
    }

    public function updateSettings(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'auto_renew' => ['sometimes', 'boolean'],
            'force_https' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json(['message' => __('ssl.nothing_to_update')], 422);
        }

        if (array_key_exists('force_https', $validated)) {
            $force = (bool) $validated['force_https'];
            $domain->update(['force_https' => $force]);
            if ($domain->ssl_enabled) {
                $engine = $this->engine->setSiteSslSettings($domain->name, $force);
                if (! empty($engine['error'])) {
                    return response()->json(['message' => (string) $engine['error']], 422);
                }
            }
        }

        if (array_key_exists('auto_renew', $validated)) {
            $cert = $domain->sslCertificate;
            if (! $cert) {
                return response()->json(['message' => __('ssl.missing')], 404);
            }
            $cert->update(['auto_renew' => (bool) $validated['auto_renew']]);
        }

        return response()->json([
            'message' => __('ssl.settings_saved'),
            'domain' => $domain->fresh()->load('sslCertificate'),
        ]);
    }
}
