<?php

namespace App\Services\Licensing;

use App\Models\SaasLicense;
use App\Models\SaasLicenseActivation;
use App\Services\OfflineLicenseService;
use App\Services\SaasLicenseValidationService;

/**
 * Opak (hv_...) bir lisans anahtarını, panelin host'una bağlı çevrimdışı imzalı
 * (PLZ1...) bir anahtara dönüştürür. Böylece müşteri kolay bir anahtarla satın
 * alır, panel kurulumda onu sağlam (offline doğrulanan, domaine bağlı) bir
 * anahtara çevirir; hub yine uzaktan iptal/limit kontrolü yapabilir.
 */
class SaasLicenseSigningService
{
    public function __construct(
        private SaasLicenseValidationService $validation,
        private OfflineLicenseService $offline,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function activate(string $key, ?string $host, ?string $ip = null, ?string $userAgent = null): array
    {
        $payload = $this->validation->validateKey($key);
        if (($payload['valid'] ?? false) !== true) {
            return $payload;
        }

        $license = SaasLicense::query()
            ->with(['customer', 'product'])
            ->where('license_key', trim($key))
            ->first();

        if (! $license) {
            return $payload;
        }

        $host = $this->normalizeHost($host);

        // Aktivasyon limiti (yalnızca belirli bir host'a bağlanıyorsa anlamlı).
        if ($host !== null) {
            $limit = $this->maxActivations($license);
            $existing = $license->activations()->where('host', $host)->first();
            if (! $existing && $limit > 0) {
                $used = (int) $license->activations()->count();
                if ($used >= $limit) {
                    return array_merge($payload, [
                        'valid' => false,
                        'code' => 'activation_limit',
                        'message' => "Lisans aktivasyon sınırına ulaşıldı ({$limit}). Mevcut bir kurulumu kaldırın veya planınızı yükseltin.",
                        'activations_used' => $used,
                        'activations_limit' => $limit,
                    ]);
                }
            }

            $this->recordActivation($license, $host, $ip, $userAgent);
        }

        $signedKey = null;
        if ($this->offline->canSign()) {
            $signedKey = $this->offline->issue($this->buildClaims($license, $payload, $host));
        }

        return array_merge($payload, [
            'signed_key' => $signedKey,
            'bound_host' => $host,
            'activations_used' => (int) $license->activations()->count(),
            'activations_limit' => $this->maxActivations($license),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildClaims(SaasLicense $license, array $payload, ?string $host): array
    {
        $features = [];
        foreach (($payload['features'] ?? []) as $key => $info) {
            if (($info['enabled'] ?? false) === true) {
                $features[] = (string) $key;
            }
        }

        $exp = $license->expires_at ? $license->expires_at->getTimestamp() : 0;

        return [
            'lid' => 'HV-'.$license->id,
            'to' => (string) ($license->customer->name ?? $license->customer->email ?? ''),
            'plan' => (string) ($license->product->code ?? 'standard'),
            'plan_name' => (string) ($license->product->name ?? ''),
            'feat' => $features,
            'dom' => $host !== null ? [$host] : ['*'],
            'exp' => $exp,
            'grace' => (int) config('panelze_saas.offline_grace_days', 14),
        ];
    }

    private function recordActivation(SaasLicense $license, string $host, ?string $ip, ?string $userAgent): void
    {
        /** @var SaasLicenseActivation $activation */
        $activation = $license->activations()->firstOrNew(['host' => $host]);
        if (! $activation->exists) {
            $activation->activated_at = now();
        }
        $activation->ip = $ip;
        $activation->user_agent = $userAgent ? mb_substr($userAgent, 0, 255) : null;
        $activation->last_seen_at = now();
        $activation->save();
    }

    private function maxActivations(SaasLicense $license): int
    {
        $override = is_array($license->limits_override) ? $license->limits_override : [];
        if (array_key_exists('max_activations', $override)) {
            return max(0, (int) $override['max_activations']);
        }
        $defaults = is_array($license->product->default_limits ?? null) ? $license->product->default_limits : [];
        if (array_key_exists('max_activations', $defaults)) {
            return max(0, (int) $defaults['max_activations']);
        }

        return max(0, (int) config('panelze_saas.default_max_activations', 0));
    }

    private function normalizeHost(?string $host): ?string
    {
        $host = strtolower(trim((string) $host));
        if ($host === '' || $host === '*') {
            return null;
        }
        // URL verildiyse host kısmını al.
        if (str_contains($host, '/')) {
            $parsed = parse_url($host, PHP_URL_HOST);
            if (is_string($parsed) && $parsed !== '') {
                $host = $parsed;
            }
        }

        return $host !== '' ? $host : null;
    }
}
