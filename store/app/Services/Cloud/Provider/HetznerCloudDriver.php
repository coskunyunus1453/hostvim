<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HetznerCloudDriver implements CloudProviderDriverInterface
{
    private const BASE = 'https://api.hetzner.cloud/v1';

    public function apiName(): string
    {
        return 'hetzner';
    }

    public function isConfigured(CloudProvider $account): bool
    {
        return ! empty($account->credentials['api_token']);
    }

    public function testConnection(CloudProvider $account): array
    {
        $response = $this->request($account, 'get', self::BASE.'/locations');
        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Hetzner Cloud bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => 'Hetzner API hatası: HTTP '.$response->status()];
    }

    public function createServer(CloudProvider $account, array $config): array
    {
        $payload = [
            'name' => $config['hostname'],
            'server_type' => $config['plan'],
            'image' => $config['image'],
            'location' => $config['region'],
            'start_after_create' => true,
            'labels' => $config['labels'] ?? [],
        ];

        $response = $this->request($account, 'post', self::BASE.'/servers', $payload);
        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new \RuntimeException('Hetzner sunucu oluşturulamadı: '.$error);
        }

        $server = $response->json('server') ?? [];
        $rootPassword = $response->json('root_password');

        return [
            'external_id' => (string) ($server['id'] ?? ''),
            'ipv4' => $server['public_net']['ipv4']['ip'] ?? null,
            'ipv6' => $server['public_net']['ipv6']['ip'] ?? null,
            'root_password' => is_string($rootPassword) ? $rootPassword : null,
            'status' => (string) ($server['status'] ?? 'provisioning'),
            'meta' => ['provider' => 'hetzner'],
        ];
    }

    public function destroyServer(CloudProvider $account, string $externalId): bool
    {
        return $this->request($account, 'delete', self::BASE.'/servers/'.$externalId)->successful();
    }

    private function request(CloudProvider $account, string $method, string $url, array $payload = [])
    {
        $token = (string) ($account->credentials['api_token'] ?? '');
        $pending = Http::timeout(90)->acceptJson()->withToken($token);

        return match ($method) {
            'get' => $pending->get($url, $payload),
            'post' => $pending->post($url, $payload),
            'delete' => $pending->delete($url),
            default => $pending->get($url),
        };
    }
}
