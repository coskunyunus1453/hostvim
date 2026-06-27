<?php

namespace App\Services\Panel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PanelzeApiService
{
    public function isConfigured(): bool
    {
        return config('panelze.api_url') !== '' && config('panelze.secret') !== '';
    }

    /**
     * @return array{ok:bool,panel?:string,integration?:string,version?:string}
     */
    public function test(): array
    {
        return $this->request('get', '/api/integrations/store/test');
    }

    /**
     * @return list<array{id:int,name:string,slug:string,price_monthly:string,price_yearly:string}>
     */
    public function packages(): array
    {
        $response = $this->request('get', '/api/integrations/store/packages');

        return $response['packages'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function fulfillStatus(string $storeOrderNumber): array
    {
        return $this->request('get', '/api/integrations/store/fulfill/status', [
            'store_order_number' => $storeOrderNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function fulfill(array $payload): array
    {
        return $this->request('post', '/api/integrations/store/fulfill', $payload);
    }

    /**
     * Spaceship tarafinda kayit tamamlanan domaini panelde de guncel duruma ceker.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function markDomainRegistered(array $payload): array
    {
        return $this->request('post', '/api/integrations/store/domains/registered', $payload);
    }

    /**
     * @return array{enabled?:bool,currency?:string,tlds?:list<array{tld:string,register_price:float,renew_price:float}>}
     */
    public function domainTlds(): array
    {
        $response = $this->request('get', '/api/integrations/store/domains/tlds');

        return $response['tlds'] ?? [];
    }

    /**
     * @return array{domain:string,available:bool,register_price?:float,renew_price?:float,currency?:string,reason?:string}
     */
    public function checkDomain(string $domain): array
    {
        return $this->request('get', '/api/integrations/store/domains/check', [
            'domain' => $domain,
        ]);
    }

    /**
     * @return array{linked?: bool, panel_user_id?: int, name?: string}
     */
    public function customerLinkByEmail(string $email): array
    {
        return $this->request('post', '/api/integrations/store/customer/link', [
            'email' => $email,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function customerRequest(string $method, string $path, array $payload = []): array
    {
        return $this->request($method, $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function syncSettings(array $payload): array
    {
        return $this->request('post', '/api/integrations/store/settings/sync', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Panelze API yapılandırılmamış (PANELZE_API_URL / PANELZE_STORE_SECRET).');
        }

        $baseUrl = (string) config('panelze.api_url');
        $this->assertSecureUrl($baseUrl);

        $url = $baseUrl.$path;
        $timeout = (int) config('panelze.timeout', 30);

        try {
            $pending = Http::timeout($timeout)
                ->acceptJson()
                ->withToken((string) config('panelze.secret'));

            $response = match (strtolower($method)) {
                'get' => $pending->get($url, $payload),
                'post' => $pending->post($url, $payload),
                'patch' => $pending->patch($url, $payload),
                default => throw new RuntimeException("Desteklenmeyen HTTP metodu: {$method}"),
            };

            if ($response->failed()) {
                $message = $response->json('message') ?? 'Panelze API isteği başarısız.';
                Log::warning('Panelze API hatası', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                throw new RuntimeException(is_string($message) ? $message : 'Panelze API isteği başarısız.');
            }

            return $response->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            Log::error('Panelze API bağlantı hatası', ['path' => $path]);
            throw new RuntimeException('Panelze paneline bağlanılamadı.');
        }
    }

    private function assertSecureUrl(string $url): void
    {
        if ($url === '') {
            return;
        }

        if (app()->environment('local', 'testing')) {
            return;
        }

        if (config('panelze.allow_internal_http', true)) {
            $host = parse_url($url, PHP_URL_HOST);
            if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
                return;
            }
        }

        if (! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Panelze API URL HTTPS olmalıdır.');
        }
    }
}
