<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MetunicRegistrarDriver implements DomainRegistrarDriverInterface
{
    public function apiName(): string
    {
        return 'metunic';
    }

    public function isConfigured(DomainRegistrar $account): bool
    {
        $creds = $account->credentials ?? [];

        return ! empty($creds['username']) && ! empty($creds['password']);
    }

    public function testConnection(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return ['ok' => false, 'message' => 'Kullanıcı adı ve şifre gerekli.'];
        }

        try {
            $this->sessionCookie($account);

            return ['ok' => true, 'message' => 'Metunic API oturumu açıldı.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchTldPricing(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return [];
        }

        $tlds = ['.com.tr', '.net.tr', '.org.tr', '.tr', '.gen.tr', '.web.tr', '.info.tr', '.biz.tr', '.name.tr', '.tv.tr'];
        $out = [];

        foreach ($tlds as $tld) {
            $pricing = $this->pricingForTld($account, $tld);
            if ($pricing !== null) {
                $out[$tld] = $pricing;
            }
        }

        return $out;
    }

    public function checkAvailability(DomainRegistrar $account, string $domain): array
    {
        if (! $this->isConfigured($account)) {
            return ['available' => false, 'currency' => 'TRY', 'reason' => 'not_configured'];
        }

        $response = $this->authed($account)->get($this->baseUrl($account).'/domains/check', [
            'domain' => strtolower($domain),
        ]);

        if (! $response->successful()) {
            return ['available' => false, 'currency' => 'TRY', 'reason' => 'api_error'];
        }

        $body = $response->json() ?? [];
        $data = $body['data'] ?? $body;
        $available = filter_var($data['available'] ?? $data['isAvailable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $tld = $this->extractTld($domain);
        $pricing = $this->pricingForTld($account, $tld);

        return [
            'available' => $available,
            'register_price' => $pricing['register'] ?? null,
            'renew_price' => $pricing['renew'] ?? null,
            'currency' => 'TRY',
            'reason' => $available ? null : 'unavailable',
        ];
    }

    /** @return array{register: float, renew: float, currency: string}|null */
    private function pricingForTld(DomainRegistrar $account, string $tld): ?array
    {
        $tldParam = ltrim(strtolower($tld), '.');
        $response = $this->authed($account)->get($this->baseUrl($account).'/pricings/pricings-tld', [
            'tld' => $tldParam,
            'duration' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json() ?? [];
        $data = $body['data'] ?? $body;
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $register = (float) ($data['price'] ?? $data['registrationPrice'] ?? $data['amount'] ?? 0);
        $renew = (float) ($data['renewPrice'] ?? $data['renewalPrice'] ?? $register);

        if ($register <= 0) {
            return null;
        }

        return [
            'register' => round($register, 2),
            'renew' => round($renew > 0 ? $renew : $register, 2),
            'currency' => 'TRY',
        ];
    }

    private function authed(DomainRegistrar $account)
    {
        $cookie = $this->sessionCookie($account);
        $cookieName = (string) ($account->credentials['cookie_name'] ?? 'WiseCPMetunicFononline');

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders(['Cookie' => $cookieName.'='.$cookie]);
    }

    private function sessionCookie(DomainRegistrar $account): string
    {
        $cacheKey = 'metunic_session:'.$account->id;

        return Cache::remember($cacheKey, now()->addMinutes(25), function () use ($account): string {
            $creds = $account->credentials ?? [];
            $response = Http::timeout(30)
                ->acceptJson()
                ->post($this->baseUrl($account).'/auth/login', [
                    'username' => (string) ($creds['username'] ?? ''),
                    'password' => (string) ($creds['password'] ?? ''),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Metunic oturum açılamadı (HTTP '.$response->status().').');
            }

            $token = $response->json('token')
                ?? $response->json('data.token')
                ?? $response->json('sessionId');

            if (is_string($token) && $token !== '') {
                return $token;
            }

            foreach ($response->cookies() as $cookie) {
                if (str_contains(strtolower($cookie->getName()), 'metunic') || str_contains(strtolower($cookie->getName()), 'session')) {
                    return $cookie->getValue();
                }
            }

            throw new \RuntimeException('Metunic oturum bilgisi alınamadı.');
        });
    }

    private function baseUrl(DomainRegistrar $account): string
    {
        $url = trim((string) ($account->credentials['base_url'] ?? ''));

        return rtrim($url !== '' ? $url : 'https://api.metunic.com.tr/v1', '/');
    }

    private function extractTld(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if (preg_match('/\.(com|net|org|gen|web|info|biz|name|tv|cc|bel)\.tr$/', $domain)) {
            return substr($domain, strrpos($domain, '.', -5));
        }
        if (str_ends_with($domain, '.tr')) {
            return '.tr';
        }

        return '.'.explode('.', $domain, 2)[1] ?? '';
    }
}
