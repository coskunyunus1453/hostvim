<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Http;

class PorkbunRegistrarDriver implements DomainRegistrarDriverInterface
{
    private const BASE = 'https://api.porkbun.com/api/json/v3';

    public function apiName(): string
    {
        return 'porkbun';
    }

    public function isConfigured(DomainRegistrar $account): bool
    {
        $creds = $account->credentials ?? [];

        return ! empty($creds['api_key']) && ! empty($creds['secret_key']);
    }

    public function testConnection(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return ['ok' => false, 'message' => 'API Key ve Secret Key gerekli.'];
        }

        $response = $this->authRequest($account, 'post', self::BASE.'/ping');

        if (($response['status'] ?? '') === 'SUCCESS') {
            return ['ok' => true, 'message' => 'Porkbun bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => (string) ($response['message'] ?? 'Porkbun bağlantısı başarısız.')];
    }

    public function fetchTldPricing(DomainRegistrar $account): array
    {
        $response = Http::timeout(45)->acceptJson()->post(self::BASE.'/pricing/get');

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json('pricing') ?? $response->json() ?? [];
        $out = [];

        foreach ($data as $tldKey => $row) {
            if (! is_array($row)) {
                continue;
            }
            $register = $this->money($row['registration'] ?? $row['registration_price'] ?? null);
            $renew = $this->money($row['renewal'] ?? $row['renewal_price'] ?? null);
            if ($register <= 0 && $renew <= 0) {
                continue;
            }
            $tld = '.'.ltrim(strtolower((string) $tldKey), '.');
            $out[$tld] = [
                'register' => $register > 0 ? $register : $renew,
                'renew' => $renew > 0 ? $renew : $register,
                'transfer' => $this->money($row['transfer'] ?? null) ?: null,
                'currency' => 'USD',
            ];
        }

        return $out;
    }

    public function checkAvailability(DomainRegistrar $account, string $domain): array
    {
        if (! $this->isConfigured($account)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'not_configured'];
        }

        $response = $this->authRequest($account, 'post', self::BASE.'/domain/checkDomain/'.urlencode($domain));

        if (($response['status'] ?? '') !== 'SUCCESS') {
            return [
                'available' => false,
                'currency' => 'USD',
                'reason' => (string) ($response['message'] ?? 'api_error'),
            ];
        }

        $available = strtolower((string) ($response['avail'] ?? 'no')) === 'yes';
        $price = $this->money($response['price'] ?? $response['registration_price'] ?? null);
        $renew = $this->money($response['renewal_price'] ?? null);

        return [
            'available' => $available,
            'register_price' => $price > 0 ? $price : null,
            'renew_price' => $renew > 0 ? $renew : ($price > 0 ? $price : null),
            'currency' => 'USD',
            'reason' => $available ? null : 'unavailable',
        ];
    }

    /** @return array<string, mixed> */
    private function authRequest(DomainRegistrar $account, string $method, string $url): array
    {
        $creds = $account->credentials ?? [];
        $body = [
            'apikey' => (string) ($creds['api_key'] ?? ''),
            'secretapikey' => (string) ($creds['secret_key'] ?? ''),
        ];

        $response = Http::timeout(30)->acceptJson()->$method($url, $body);

        return $response->json() ?? [];
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $amount = (float) $value;

        // Porkbun bazen kuruş (penny) döner.
        if ($amount > 500) {
            return round($amount / 100, 2);
        }

        return round($amount, 2);
    }
}
