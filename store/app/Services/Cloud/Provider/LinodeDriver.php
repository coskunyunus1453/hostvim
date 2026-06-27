<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Facades\Http;

class LinodeDriver implements CloudProviderDriverInterface
{
    private const BASE = 'https://api.linode.com/v4';

    public function apiName(): string
    {
        return 'linode';
    }

    public function isConfigured(CloudProvider $account): bool
    {
        return ! empty($account->credentials['api_token']);
    }

    public function testConnection(CloudProvider $account): array
    {
        $response = $this->request($account, 'get', self::BASE.'/profile');
        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Linode (Akamai) bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => 'Linode API hatası: HTTP '.$response->status()];
    }

    public function createServer(CloudProvider $account, array $config): array
    {
        $payload = [
            'label' => $config['hostname'],
            'region' => $config['region'],
            'type' => $config['plan'],
            'image' => $config['image'],
            'root_pass' => $config['root_password'] ?? bin2hex(random_bytes(12)).'A!',
            'booted' => true,
            'tags' => array_keys($config['labels'] ?? []),
        ];

        $response = $this->request($account, 'post', self::BASE.'/linode/instances', $payload);
        if (! $response->successful()) {
            $error = collect($response->json('errors') ?? [])->pluck('reason')->implode(', ') ?: $response->body();

            throw new \RuntimeException('Linode instance oluşturulamadı: '.$error);
        }

        $instance = $response->json() ?? [];
        $linodeId = (string) ($instance['id'] ?? '');

        $ipv4 = $instance['ipv4'][0] ?? null;
        $ipv6 = $instance['ipv6'] ?? null;

        return [
            'external_id' => $linodeId,
            'ipv4' => $ipv4,
            'ipv6' => is_string($ipv6) ? $ipv6 : null,
            'root_password' => $payload['root_pass'],
            'status' => (string) ($instance['status'] ?? 'provisioning'),
            'meta' => ['provider' => 'linode'],
        ];
    }

    public function destroyServer(CloudProvider $account, string $externalId): bool
    {
        return $this->request($account, 'delete', self::BASE.'/linode/instances/'.$externalId)->successful();
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
