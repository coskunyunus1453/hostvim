<?php

namespace App\Services;

use App\Models\SaasLicense;
use App\Models\SaasProductModule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SaasLicenseValidationService
{
    public function __construct(
        private PanelIntegrationsService $integrations,
    ) {}
    /**
     * @return array<string, mixed>
     */
    public function validateKey(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return $this->invalid('empty_key');
        }

        $license = SaasLicense::query()
            ->with(['customer', 'product'])
            ->where('license_key', $key)
            ->first();

        if (! $license) {
            return $this->invalid('not_found');
        }

        if ($license->status === 'revoked') {
            return $this->invalid('revoked');
        }

        if ($license->status === 'suspended') {
            return $this->invalid('suspended');
        }

        $now = Carbon::now();
        // MySQL NOW() ile uygulama saat dilimi kayması «henüz başlamadı» yanlış pozitifi verebilir.
        if ($license->starts_at && $license->starts_at->gt($now->copy()->addMinute())) {
            return $this->invalid('not_started', $license);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            if ($license->status !== 'expired') {
                $license->update(['status' => 'expired']);
            }

            return $this->invalid('expired', $license);
        }

        if ($license->status === 'expired') {
            return $this->invalid('expired', $license);
        }

        $product = $license->product;
        if (! $product || ! $product->is_active) {
            return $this->invalid('product_inactive', $license);
        }

        $features = $this->buildFeatures($license);

        return [
            'valid' => true,
            'status' => $license->status,
            'plan' => $product->code,
            'plan_name' => $product->name,
            'expires_at' => $this->iso($license->expires_at),
            'limits' => $this->mergeLimits($license),
            'features' => $features,
            'integrations' => $this->integrationsPayload($features),
            'customer' => [
                'name' => (string) $license->customer->name,
                'email' => (string) ($license->customer->email ?? ''),
            ],
            'subscription' => [
                'status' => $license->subscription_status,
                'renews_at' => $this->iso($license->subscription_renews_at),
            ],
            'billing' => [
                'provider' => $license->billing_provider,
                'reference' => $license->billing_reference,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invalid(string $code, ?SaasLicense $license = null): array
    {
        $out = [
            'valid' => false,
            'code' => $code,
            'message' => match ($code) {
                'empty_key' => 'License key required',
                'not_found' => 'Unknown license key',
                'revoked' => 'License revoked',
                'suspended' => 'License suspended',
                'expired' => 'License expired',
                'not_started' => 'License not yet active',
                'product_inactive' => 'Product disabled',
                default => 'Invalid license',
            },
        ];
        if ($license) {
            $out['license_id'] = $license->id;
            $product = $license->product;
            $out['plan'] = $product?->code;
            $out['plan_name'] = $product?->name;
            $out['expires_at'] = $this->iso($license->expires_at);
            $out['status'] = $license->status;
            $out['subscription'] = [
                'status' => $license->subscription_status,
                'renews_at' => $this->iso($license->subscription_renews_at),
            ];
            $out['billing'] = [
                'provider' => $license->billing_provider,
                'reference' => $license->billing_reference,
            ];
        }

        return $out;
    }

    private function iso(?CarbonInterface $dt): ?string
    {
        return $dt ? $dt->toIso8601String() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mergeLimits(SaasLicense $license): array
    {
        $base = $license->product->default_limits ?? [];

        return array_merge(is_array($base) ? $base : [], $license->limits_override ?? []);
    }

    /**
     * @return array<string, array{enabled: bool, quota: int|null}>
     */
    private function buildFeatures(SaasLicense $license): array
    {
        $modules = SaasProductModule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $defaults = $license->product->default_modules ?? [];
        if (! is_array($defaults)) {
            $defaults = [];
        }
        $over = $license->modules_override ?? [];
        if (! is_array($over)) {
            $over = [];
        }

        $out = [];
        foreach ($modules as $mod) {
            $key = $mod->key;
            $enabled = array_key_exists($key, $over)
                ? (bool) $over[$key]
                : (bool) ($defaults[$key] ?? false);

            $out[$key] = [
                'enabled' => $enabled,
                'quota' => null,
                'label' => $mod->label,
                'ui_paths' => $mod->ui_paths ?? [],
                'api_route_prefixes' => $mod->api_route_prefixes ?? [],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{enabled: bool, quota: int|null}>  $features
     * @return array<string, mixed>
     */
    private function integrationsPayload(array $features): array
    {
        $out = [];
        if (($features['backups_pro']['enabled'] ?? false) === true) {
            $gd = $this->integrations->googleDriveForPanel();
            if ($gd !== null) {
                $out['google_drive'] = $gd;
            }
        }

        return $out;
    }
}
