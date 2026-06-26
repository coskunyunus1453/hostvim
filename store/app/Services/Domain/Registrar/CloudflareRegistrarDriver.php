<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudflareRegistrarDriver implements DomainRegistrarDriverInterface
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    public function apiName(): string
    {
        return 'cloudflare';
    }

    public function isConfigured(DomainRegistrar $account): bool
    {
        $creds = $account->credentials ?? [];

        return ! empty($creds['api_token']) && ! empty($creds['account_id']);
    }

    public function testConnection(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return ['ok' => false, 'message' => 'API Token ve Account ID gerekli.'];
        }

        $accountId = (string) ($account->credentials['account_id'] ?? '');
        $response = $this->request($account, 'get', self::BASE.'/accounts/'.$accountId);

        if ($response->json('success') === true) {
            return ['ok' => true, 'message' => 'Cloudflare Registrar bağlantısı başarılı.'];
        }

        $error = collect($response->json('errors') ?? [])->pluck('message')->first();

        return ['ok' => false, 'message' => (string) ($error ?: 'Cloudflare bağlantısı başarısız.')];
    }

    public function fetchTldPricing(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return [];
        }

        $knownTlds = ['.com', '.net', '.org', '.io', '.dev', '.app', '.xyz', '.info'];
        $domains = [];
        foreach ($knownTlds as $tld) {
            $domains[] = 'hostvim-sync-'.Str::random(8).$tld;
        }

        $response = $this->domainCheck($account, $domains);
        $out = [];

        foreach ($response as $row) {
            if (! is_array($row)) {
                continue;
            }
            $domain = strtolower((string) ($row['name'] ?? ''));
            $tld = $this->extractTld($domain);
            $pricing = $row['pricing'] ?? null;
            if ($tld === '' || ! is_array($pricing)) {
                continue;
            }
            $register = (float) ($pricing['registration_cost'] ?? 0);
            $renew = (float) ($pricing['renewal_cost'] ?? 0);
            if ($register <= 0 && $renew <= 0) {
                continue;
            }
            $out[$tld] = [
                'register' => $register > 0 ? $register : $renew,
                'renew' => $renew > 0 ? $renew : $register,
                'currency' => strtoupper((string) ($pricing['currency'] ?? 'USD')),
            ];
        }

        return $out;
    }

    public function checkAvailability(DomainRegistrar $account, string $domain): array
    {
        if (! $this->isConfigured($account)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'not_configured'];
        }

        $rows = $this->domainCheck($account, [$domain]);
        $row = $rows[0] ?? null;
        if (! is_array($row)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'api_error'];
        }

        $available = (bool) ($row['registrable'] ?? false);
        $pricing = $row['pricing'] ?? [];
        $register = (float) ($pricing['registration_cost'] ?? 0);
        $renew = (float) ($pricing['renewal_cost'] ?? 0);

        return [
            'available' => $available,
            'register_price' => $register > 0 ? $register : null,
            'renew_price' => $renew > 0 ? $renew : ($register > 0 ? $register : null),
            'currency' => strtoupper((string) ($pricing['currency'] ?? 'USD')),
            'reason' => $available ? null : (string) ($row['reason'] ?? 'unavailable'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function domainCheck(DomainRegistrar $account, array $domains): array
    {
        $accountId = (string) ($account->credentials['account_id'] ?? '');
        $response = $this->request($account, 'post', self::BASE.'/accounts/'.$accountId.'/registrar/domain-check', [
            'domains' => array_values($domains),
        ]);

        if ($response->json('success') !== true) {
            return [];
        }

        return $response->json('result.domains') ?? [];
    }

    private function request(DomainRegistrar $account, string $method, string $url, array $payload = [])
    {
        $token = (string) ($account->credentials['api_token'] ?? '');
        $pending = Http::timeout(30)
            ->acceptJson()
            ->withToken($token);

        return match ($method) {
            'get' => $pending->get($url, $payload),
            'post' => $pending->post($url, $payload),
            default => $pending->get($url),
        };
    }

    private function extractTld(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '' || ! str_contains($domain, '.')) {
            return '';
        }

        if (preg_match('/\.(com|net|org|bel|gen|web|info|biz|name|tv|cc)\.tr$/', $domain, $m)) {
            return '.'.$m[1].'.tr';
        }

        return '.'.explode('.', $domain, 2)[1] ?? '';
    }
}
