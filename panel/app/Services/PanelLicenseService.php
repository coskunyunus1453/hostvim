<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Panel genelinde geçerli lisans (hub / engine) ve Pro modül bayrakları.
 */
class PanelLicenseService
{
    public function __construct(
        private PanelStoredLicenseService $storedLicense,
        private LicenseHubClient $licenseHub,
        private OfflineLicenseService $offline,
    ) {}

    /**
     * Lisans doğrulama (hibrit):
     *  1. Offline imzalı anahtar (Ed25519, gömülü public key) = ANA otorite.
     *  2. Online hub (yapılandırılmışsa) = yalnızca uzaktan İPTAL ve offline
     *     imzası olmayan (eski hub-tabanlı) anahtarlar için yedek doğrulama.
     *
     * Not: Artık "her zaman geçerli" engine yedeği YOKTUR; geçerli imza ya da
     * hub onayı olmadan lisans geçersizdir.
     */
    public function hubPayload(): ?array
    {
        return Cache::remember('panelze.license.hub_payload', 300, function () {
            $key = $this->storedLicense->effectiveKey();
            if ($key === '') {
                return null;
            }

            $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: null;
            $offline = $this->offline->publicKey() !== ''
                ? $this->offline->verify($key, is_string($host) ? $host : null)
                : null;

            $hubBase = rtrim(trim((string) config('panelze.license_server', '')), '/');
            if ($hubBase !== '') {
                $hub = $this->licenseHub->validate($key);

                // Online iptal: hub açıkça iptal/askı diyorsa offline geçerli olsa bile reddet.
                if ($hub !== [] && ($hub['valid'] ?? null) === false
                    && in_array((string) ($hub['code'] ?? ''), ['license_revoked', 'revoked', 'suspended', 'disabled'], true)) {
                    return $hub;
                }

                // Offline geçerli değilse (ör. eski hub-tabanlı anahtar) hub onayını kullan.
                if (($offline === null || ($offline['valid'] ?? false) !== true)
                    && $hub !== [] && array_key_exists('valid', $hub)) {
                    return $hub;
                }
            }

            return $offline;
        });
    }

    public function isLicenseValid(): bool
    {
        if (filter_var(config('panelze.license.force_valid', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $hub = $this->hubPayload();

        return $hub !== null && ($hub['valid'] ?? false) === true;
    }

    public function isProPlan(): bool
    {
        if (filter_var(config('panelze.license.force_pro', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        if (! $this->isLicenseValid()) {
            return false;
        }
        $plan = strtolower(trim((string) ($this->hubPayload()['plan'] ?? '')));
        $proPlans = config('panelze.license.pro_plan_codes', []);

        return in_array($plan, $proPlans, true);
    }

    /**
     * "Tam erişim" planı mı (enterprise/vendor gibi)? Bu planlar, lisans
     * payload'ında kısmi bir özellik listesi olsa bile tüm modülleri açar.
     */
    public function isFullAccessPlan(): bool
    {
        if (! $this->isProPlan()) {
            return false;
        }
        $plan = strtolower(trim((string) ($this->hubPayload()['plan'] ?? '')));
        $full = config('panelze.license.full_access_plan_codes', ['enterprise', 'vendor']);

        return is_array($full) && in_array($plan, $full, true);
    }

    public function hasFeature(string $moduleKey): bool
    {
        if (filter_var(config('panelze.features.'.$moduleKey, false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        if (! $this->isLicenseValid()) {
            return false;
        }
        $features = $this->hubPayload()['features'] ?? [];
        if (! is_array($features) || $features === []) {
            // Lisans payload'ı açık bir özellik listesi taşımıyorsa: geçerli bir
            // Pro/Enterprise plan TÜM pro modüllerini açar (paket = tam pro).
            // Pro olmayan (community vb.) planlarda kapalı kalır.
            return $this->isProPlan();
        }
        if (! isset($features[$moduleKey])) {
            // Payload özellik listesi taşıyor ancak bu modülü içermiyor.
            // Enterprise/vendor gibi "tam paket" planlar yine de tüm modülleri açar;
            // diğer pro planlarda yalnızca paketlenmiş varsayılan modüller açılır.
            if ($this->isProPlan()) {
                if ($this->isFullAccessPlan()) {
                    return true;
                }
                $bundled = config('panelze.license.pro_default_modules', []);

                return is_array($bundled) && in_array($moduleKey, $bundled, true);
            }

            return false;
        }

        return (bool) ($features[$moduleKey]['enabled'] ?? false);
    }

    public function planCode(): ?string
    {
        if (! $this->isLicenseValid()) {
            return null;
        }

        return strtolower(trim((string) ($this->hubPayload()['plan'] ?? ''))) ?: null;
    }

    public function expiresAt(): ?string
    {
        $hub = $this->hubPayload();

        return isset($hub['expires_at']) ? (string) $hub['expires_at'] : null;
    }

    public function isCommunityPlan(): bool
    {
        if (! $this->isLicenseValid()) {
            return $this->storedLicense->effectiveKey() !== '';
        }
        $plan = $this->planCode();
        $community = config('panelze.license.community_plan_codes', []);

        return $plan !== null && in_array($plan, $community, true);
    }

    /**
     * Hub yanıtından faturalama / lisans özeti (müşteri paneli).
     *
     * @return array<string, mixed>
     */
    public function billingSummary(): array
    {
        $key = $this->storedLicense->effectiveKey();
        $hubConfigured = rtrim(trim((string) config('panelze.license_server', '')), '/') !== '';

        $base = [
            'has_license_key' => $key !== '',
            'hub_configured' => $hubConfigured,
        ];

        if ($key === '') {
            return array_merge($base, [
                'valid' => false,
                'tier' => 'none',
            ]);
        }

        $hub = $this->hubPayload();
        if ($hub === null) {
            return array_merge($base, [
                'valid' => false,
                'tier' => 'unknown',
                'hub_reachable' => false,
            ]);
        }

        $valid = ($hub['valid'] ?? false) === true;
        $tier = $this->resolveTier($valid, $hub);

        return array_merge($base, [
            'valid' => $valid,
            'tier' => $tier,
            'plan' => $hub['plan'] ?? null,
            'plan_name' => $hub['plan_name'] ?? null,
            'license_status' => $hub['status'] ?? null,
            'expires_at' => $hub['expires_at'] ?? null,
            'subscription_status' => is_array($hub['subscription'] ?? null)
                ? ($hub['subscription']['status'] ?? null)
                : null,
            'renews_at' => is_array($hub['subscription'] ?? null)
                ? ($hub['subscription']['renews_at'] ?? null)
                : null,
            'billing_provider' => is_array($hub['billing'] ?? null)
                ? ($hub['billing']['provider'] ?? null)
                : null,
            'payment_method_label' => $this->formatBillingProvider(
                is_array($hub['billing'] ?? null) ? ($hub['billing']['provider'] ?? null) : null
            ),
            'customer' => is_array($hub['customer'] ?? null) ? $hub['customer'] : null,
            'downgraded_to_community' => ! $valid && $key !== '',
            'code' => $hub['code'] ?? null,
            'message' => $hub['message'] ?? null,
            'hub_reachable' => true,
        ]);
    }

    public function hasPhpMyAdminAutoLogin(): bool
    {
        return $this->hasFeature('phpmyadmin_sso');
    }

    public function forgetCache(): void
    {
        Cache::forget('panelze.license.hub_payload');
    }

    /**
     * @param  array<string, mixed>  $hub
     */
    private function resolveTier(bool $valid, array $hub): string
    {
        if (! $valid) {
            return 'community';
        }
        if ($this->isProPlan()) {
            return 'pro';
        }
        $plan = strtolower(trim((string) ($hub['plan'] ?? '')));
        $community = config('panelze.license.community_plan_codes', []);
        if ($plan !== '' && in_array($plan, $community, true)) {
            return 'community';
        }

        return 'standard';
    }

    private function formatBillingProvider(?string $provider): ?string
    {
        if ($provider === null || trim($provider) === '') {
            return null;
        }

        return match (strtolower(trim($provider))) {
            'stripe' => 'Stripe (kart)',
            'paytr' => 'PayTR',
            'manual', 'bank', 'bank_transfer', 'eft' => 'Havale / EFT',
            'whmcs' => 'WHMCS',
            'invoice' => 'Fatura / manuel',
            default => trim($provider),
        };
    }
}
