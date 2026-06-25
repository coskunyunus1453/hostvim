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
        if (! empty($config['user_data'])) {
            $payload['user_data'] = $config['user_data'];
        }
        if (! empty($config['ssh_keys'])) {
            $payload['ssh_keys'] = array_values($config['ssh_keys']);
        }

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

    public function getServer(CloudProvider $account, string $externalId): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/servers/'.$externalId);
        if (! $response->successful()) {
            return null;
        }

        $server = $response->json('server') ?? [];
        $status = (string) ($server['status'] ?? 'unknown');
        $ipv4 = $server['public_net']['ipv4']['ip'] ?? null;

        return [
            'external_id' => (string) ($server['id'] ?? $externalId),
            'ipv4' => $ipv4 ?: null,
            'ipv6' => $server['public_net']['ipv6']['ip'] ?? null,
            'status' => $status,
            'ready' => $status === 'running' && ! empty($ipv4),
            'meta' => ['provider' => 'hetzner'],
        ];
    }

    public function listServers(CloudProvider $account): array
    {
        $out = [];
        $page = 1;
        do {
            $response = $this->request($account, 'get', self::BASE.'/servers', ['page' => $page, 'per_page' => 50]);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('servers') ?? [] as $server) {
                $out[] = [
                    'external_id' => (string) ($server['id'] ?? ''),
                    'hostname' => (string) ($server['name'] ?? ''),
                    'ipv4' => $server['public_net']['ipv4']['ip'] ?? null,
                    'ipv6' => $server['public_net']['ipv6']['ip'] ?? null,
                    'status' => (string) ($server['status'] ?? 'unknown'),
                    'region' => $server['datacenter']['location']['name'] ?? null,
                    'plan' => $server['server_type']['name'] ?? null,
                    'image' => $server['image']['name'] ?? null,
                ];
            }
            $next = $response->json('meta.pagination.next_page');
            $page = is_numeric($next) ? (int) $next : 0;
        } while ($page > 0);

        return $out;
    }

    public function powerAction(CloudProvider $account, string $externalId, string $action): array
    {
        $endpoint = match ($action) {
            'start' => 'poweron',
            'stop' => 'poweroff',
            'reboot' => 'reboot',
            default => null,
        };
        if ($endpoint === null) {
            return ['ok' => false, 'message' => 'Geçersiz güç işlemi.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/servers/'.$externalId.'/actions/'.$endpoint);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'İşlem gönderildi.' : ('Hetzner hatası: HTTP '.$response->status()),
        ];
    }

    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array
    {
        $payload = [];
        if ($image) {
            $payload['image'] = $image;
        }
        $response = $this->request($account, 'post', self::BASE.'/servers/'.$externalId.'/actions/rebuild', $payload);
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Hetzner yeniden kurulum hatası: '.($response->json('error.message') ?? ('HTTP '.$response->status()))];
        }

        $root = $response->json('root_password');

        return [
            'ok' => true,
            'message' => 'Sunucu yeniden kuruluyor.',
            'root_password' => is_string($root) ? $root : null,
        ];
    }

    public function resetRootPassword(CloudProvider $account, string $externalId): array
    {
        $response = $this->request($account, 'post', self::BASE.'/servers/'.$externalId.'/actions/reset_password');
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Hetzner şifre sıfırlama hatası: HTTP '.$response->status()];
        }

        $root = $response->json('root_password');

        return [
            'ok' => true,
            'message' => 'Root şifre sıfırlandı.',
            'root_password' => is_string($root) ? $root : null,
        ];
    }

    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array
    {
        $response = $this->request($account, 'post', self::BASE.'/servers/'.$externalId.'/actions/change_type', [
            'server_type' => $plan,
            'upgrade_disk' => true,
        ]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Paket yükseltiliyor (sunucu kapalı olmalı).'
                : ('Hetzner yükseltme hatası: '.($response->json('error.message') ?? ('HTTP '.$response->status()))),
        ];
    }

    public function listImages(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/images', ['type' => 'system', 'per_page' => 100]);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('images') ?? [] as $img) {
            $name = $img['name'] ?? null;
            if (! $name) {
                continue;
            }
            $out[] = ['id' => (string) $name, 'label' => (string) ($img['description'] ?? $name)];
        }

        return $out;
    }

    public function listPlans(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/server_types', ['per_page' => 100]);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('server_types') ?? [] as $t) {
            $name = $t['name'] ?? null;
            if (! $name) {
                continue;
            }
            $label = strtoupper((string) $name).' — '.($t['cores'] ?? '?').' vCPU, '.($t['memory'] ?? '?').' GB RAM';
            $out[] = ['id' => (string) $name, 'label' => $label];
        }

        return $out;
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
