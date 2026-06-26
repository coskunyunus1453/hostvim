<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Facades\Http;

class VultrDriver implements CloudProviderDriverInterface
{
    private const BASE = 'https://api.vultr.com/v2';

    public function apiName(): string
    {
        return 'vultr';
    }

    public function isConfigured(CloudProvider $account): bool
    {
        return ! empty($account->credentials['api_key']);
    }

    public function testConnection(CloudProvider $account): array
    {
        $response = $this->request($account, 'get', self::BASE.'/account');
        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Vultr bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => 'Vultr API hatası: HTTP '.$response->status()];
    }

    public function createServer(CloudProvider $account, array $config): array
    {
        $payload = [
            'region' => $config['region'],
            'plan' => $config['plan'],
            'os_id' => is_numeric($config['image']) ? (int) $config['image'] : $config['image'],
            'label' => $config['hostname'],
            'hostname' => $config['hostname'],
            'tags' => array_keys($config['labels'] ?? []),
            'enable_ipv6' => true,
        ];

        $response = $this->request($account, 'post', self::BASE.'/instances', $payload);
        if (! $response->successful()) {
            $error = $response->json('error') ?? $response->body();

            throw new \RuntimeException('Vultr instance oluşturulamadı: '.(is_string($error) ? $error : json_encode($error)));
        }

        $instance = $response->json('instance') ?? [];

        return [
            'external_id' => (string) ($instance['id'] ?? ''),
            'ipv4' => $instance['main_ip'] ?? null,
            'ipv6' => $instance['ipv6_main_ip'] ?? null,
            'root_password' => $instance['default_password'] ?? null,
            'status' => (string) ($instance['status'] ?? 'pending'),
            'meta' => ['provider' => 'vultr'],
        ];
    }

    public function destroyServer(CloudProvider $account, string $externalId): bool
    {
        return $this->request($account, 'delete', self::BASE.'/instances/'.$externalId)->successful();
    }

    private function request(CloudProvider $account, string $method, string $url, array $payload = [])
    {
        $key = (string) ($account->credentials['api_key'] ?? '');
        $pending = Http::timeout(90)
            ->acceptJson()
            ->withHeaders(['Authorization' => 'Bearer '.$key]);

        return match ($method) {
            'get' => $pending->get($url, $payload),
            'post' => $pending->post($url, $payload),
            'delete' => $pending->delete($url),
            default => $pending->get($url),
        };
    }
}
