<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\SslCertificate;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\HostingSiteTargetResolver;
use App\Services\SslErrorTranslator;
use App\Services\SslIssueService;
use App\Support\HostingSiteTarget;
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
            ->orderByDesc('id')
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
            return response()->json($this->publicSslPayload($request, array_filter([
                'message' => $result['message'] ?? null,
                'certificate' => $result['certificate'] ?? null,
                'engine' => $result['engine'] ?? null,
                'diagnostics' => $result['diagnostics'] ?? null,
            ], fn ($v) => $v !== null)), $result['http_status']);
        }

        return response()->json($this->publicSslPayload($request, array_filter([
            'message' => $result['message'] ?? __('ssl.issued'),
            'certificate' => $result['certificate'] ?? null,
            'engine' => $result['engine'] ?? null,
            'diagnostics' => $result['diagnostics'] ?? null,
            'hostname' => $result['hostname'] ?? null,
        ], fn ($v) => $v !== null)));
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
            return response()->json($this->publicSslPayload($request, [
                'message' => SslErrorTranslator::translate((string) $engine['error'], $target->hostname),
                'certificate' => $cert->fresh(),
            ]), 503);
        }

        $expiresAt = $this->expiresAtFromEngine($engine) ?? now()->addDays(90);
        $cert->update([
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);
        $this->syncSslExpiryFlags($target, $cert);

        return response()->json($this->publicSslPayload($request, [
            'message' => __('ssl.renewed'),
            'engine' => $engine,
            'certificate' => $cert->fresh(),
        ]));
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

        return response()->json($this->publicSslPayload($request, [
            'message' => __('ssl.revoked'),
            'engine' => $engine,
        ]));
    }

    public function manual(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'certificate' => 'required|string|min:64|max:65536',
            'private_key' => 'required|string|min:64|max:65536',
            'subdomain_id' => 'nullable|integer',
        ]);

        if (isset($validated['subdomain_id'])) {
            return response()->json(['message' => __('ssl.manual_root_only')], 422);
        }

        $pemError = $this->validatePemPair($validated['certificate'], $validated['private_key']);
        if ($pemError !== null) {
            return response()->json(['message' => $pemError], 422);
        }

        $engine = $this->engine->uploadManualSSL(
            $domain->name,
            $validated['certificate'],
            $validated['private_key'],
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
            [
                'domain_id' => $domain->id,
                'site_subdomain_id' => null,
            ],
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

        return response()->json($this->publicSslPayload($request, [
            'message' => __('ssl.manual_uploaded'),
            'certificate' => $cert->fresh(),
            'engine' => $engine,
        ]));
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
            $cert = SslCertificate::query()
                ->where('domain_id', $domain->id)
                ->whereNull('site_subdomain_id')
                ->first();
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function publicSslPayload(Request $request, array $payload): array
    {
        if (! $request->user()->isAdmin()) {
            unset($payload['engine']);
        }

        return $payload;
    }

    private function validatePemPair(string $certificate, string $privateKey): ?string
    {
        $cert = @openssl_x509_read($certificate);
        if ($cert === false) {
            return (string) __('ssl.invalid_certificate_pem');
        }

        $key = @openssl_pkey_get_private($privateKey);
        if ($key === false) {
            return (string) __('ssl.invalid_private_key_pem');
        }

        if (! openssl_x509_check_private_key($cert, $key)) {
            return (string) __('ssl.cert_key_mismatch');
        }

        return null;
    }

    private function expiresAtFromEngine(array $engine): ?\Illuminate\Support\Carbon
    {
        foreach (['expires_at', 'not_after', 'expiry'] as $field) {
            if (! empty($engine[$field]) && is_string($engine[$field])) {
                try {
                    return \Illuminate\Support\Carbon::parse($engine[$field]);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    private function syncSslExpiryFlags(HostingSiteTarget $target, SslCertificate $cert): void
    {
        if ($target->isSubdomain() && $target->subdomain) {
            $target->subdomain->update([
                'ssl_enabled' => true,
                'ssl_expiry' => $cert->expires_at,
            ]);

            return;
        }

        $target->domain->update([
            'ssl_enabled' => true,
            'ssl_expiry' => $cert->expires_at,
        ]);
    }
}
