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
        private EngineApiService $engine,
    ) {}

    public function hubPayload(): ?array
    {
        return Cache::remember('hostvim.license.hub_payload', 300, function () {
            $key = $this->storedLicense->effectiveKey();
            if ($key === '') {
                return null;
            }
            $hub = $this->licenseHub->validate($key);
            if ($hub !== [] && array_key_exists('valid', $hub)) {
                return $hub;
            }
            $engine = $this->engine->validateLicense($key);
            if (is_array($engine) && ($engine['valid'] ?? false)) {
                return [
                    'valid' => true,
                    'plan' => (string) ($engine['plan'] ?? 'enterprise'),
                    'plan_name' => (string) ($engine['plan'] ?? 'enterprise'),
                    'features' => [],
                ];
            }

            return $hub !== [] ? $hub : null;
        });
    }

    public function isLicenseValid(): bool
    {
        if (filter_var(config('hostvim.license.force_valid', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $hub = $this->hubPayload();

        return $hub !== null && ($hub['valid'] ?? false) === true;
    }

    public function isProPlan(): bool
    {
        if (filter_var(config('hostvim.license.force_pro', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        if (! $this->isLicenseValid()) {
            return false;
        }
        $plan = strtolower(trim((string) ($this->hubPayload()['plan'] ?? '')));
        $proPlans = config('hostvim.license.pro_plan_codes', []);

        return in_array($plan, $proPlans, true);
    }

    public function hasFeature(string $moduleKey): bool
    {
        if (filter_var(config('hostvim.features.'.$moduleKey, false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        if (! $this->isLicenseValid()) {
            return false;
        }
        $features = $this->hubPayload()['features'] ?? [];
        if (! is_array($features) || $features === []) {
            if (! $this->isProPlan()) {
                return false;
            }
            $defaultOnPro = config('hostvim.license.pro_default_modules', ['phpmyadmin_sso']);

            return in_array($moduleKey, $defaultOnPro, true);
        }
        if (! isset($features[$moduleKey])) {
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

    public function hasPhpMyAdminAutoLogin(): bool
    {
        return $this->hasFeature('phpmyadmin_sso');
    }

    public function forgetCache(): void
    {
        Cache::forget('hostvim.license.hub_payload');
    }
}
