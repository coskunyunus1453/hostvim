<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Facades\Http;

class DigitalOceanDriver implements CloudProviderDriverInterface
{
    private const BASE = 'https://api.digitalocean.com/v2';

    public function apiName(): string
    {
        return 'digitalocean';
    }

    public function isConfigured(CloudProvider $account): bool
    {
        return ! empty($account->credentials['api_token']);
    }

    public function testConnection(CloudProvider $account): array
    {
        $response = $this->request($account, 'get', self::BASE.'/account');
        if ($response->successful()) {
            return ['ok' => true, 'message' => 'DigitalOcean bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => 'DigitalOcean API hatası: HTTP '.$response->status()];
    }

    public function createServer(CloudProvider $account, array $config): array
    {
        $payload = [
            'name' => $config['hostname'],
            'region' => $config['region'],
            'size' => $config['plan'],
            'image' => $config['image'],
            'tags' => array_values($config['labels'] ?? []),
        ];

        $response = $this->request($account, 'post', self::BASE.'/droplets', $payload);
        if (! $response->successful()) {
            $error = $response->json('message') ?? $response->body();

            throw new \RuntimeException('DigitalOcean Droplet oluşturulamadı: '.$error);
        }

        $droplet = $response->json('droplet') ?? [];
        $ipv4 = collect($droplet['networks']['v4'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;
        $ipv6 = collect($droplet['networks']['v6'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;

        return [
            'external_id' => (string) ($droplet['id'] ?? ''),
            'ipv4' => $ipv4,
            'ipv6' => $ipv6,
            'root_password' => null,
            'status' => (string) ($droplet['status'] ?? 'new'),
            'meta' => ['provider' => 'digitalocean', 'note' => 'SSH anahtarı veya panel reset password kullanın.'],
        ];
    }

    public function destroyServer(CloudProvider $account, string $externalId): bool
    {
        return $this->request($account, 'delete', self::BASE.'/droplets/'.$externalId)->successful();
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
