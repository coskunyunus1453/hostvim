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
        if (! empty($config['user_data'])) {
            $payload['metadata'] = ['user_data' => base64_encode((string) $config['user_data'])];
        }
        if (! empty($config['ssh_keys'])) {
            $payload['authorized_keys'] = array_values($config['ssh_keys']);
        }

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
            'ipv6' => is_string($ipv6) ? explode('/', $ipv6)[0] : null,
            'root_password' => $payload['root_pass'],
            'status' => (string) ($instance['status'] ?? 'provisioning'),
            'meta' => ['provider' => 'linode'],
        ];
    }

    public function getServer(CloudProvider $account, string $externalId): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/linode/instances/'.$externalId);
        if (! $response->successful()) {
            return null;
        }

        $instance = $response->json() ?? [];
        $status = (string) ($instance['status'] ?? 'provisioning');
        $ipv4 = $instance['ipv4'][0] ?? null;
        $ipv6 = $instance['ipv6'] ?? null;

        return [
            'external_id' => (string) ($instance['id'] ?? $externalId),
            'ipv4' => $ipv4 ?: null,
            'ipv6' => is_string($ipv6) ? explode('/', $ipv6)[0] : null,
            'status' => $status,
            'ready' => $status === 'running' && ! empty($ipv4),
            'meta' => ['provider' => 'linode'],
        ];
    }

    public function listServers(CloudProvider $account): array
    {
        $out = [];
        $page = 1;
        do {
            $response = $this->request($account, 'get', self::BASE.'/linode/instances', ['page' => $page, 'page_size' => 100]);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('data') ?? [] as $instance) {
                $ipv6 = $instance['ipv6'] ?? null;
                $out[] = [
                    'external_id' => (string) ($instance['id'] ?? ''),
                    'hostname' => (string) ($instance['label'] ?? ''),
                    'ipv4' => $instance['ipv4'][0] ?? null,
                    'ipv6' => is_string($ipv6) ? explode('/', $ipv6)[0] : null,
                    'status' => (string) ($instance['status'] ?? 'unknown'),
                    'region' => $instance['region'] ?? null,
                    'plan' => $instance['type'] ?? null,
                    'image' => $instance['image'] ?? null,
                ];
            }
            $pages = (int) ($response->json('pages') ?? 1);
            $page++;
        } while ($page <= $pages && $page < 20);

        return $out;
    }

    public function powerAction(CloudProvider $account, string $externalId, string $action): array
    {
        $endpoint = match ($action) {
            'start' => 'boot',
            'stop' => 'shutdown',
            'reboot' => 'reboot',
            default => null,
        };
        if ($endpoint === null) {
            return ['ok' => false, 'message' => 'Geçersiz güç işlemi.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/linode/instances/'.$externalId.'/'.$endpoint);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'İşlem gönderildi.' : ('Linode hatası: HTTP '.$response->status()),
        ];
    }

    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array
    {
        if (! $image) {
            return ['ok' => false, 'message' => 'Linode yeniden kurulumu için bir işletim sistemi (image) seçilmelidir.'];
        }

        $rootPass = bin2hex(random_bytes(12)).'A!';
        $response = $this->request($account, 'post', self::BASE.'/linode/instances/'.$externalId.'/rebuild', [
            'image' => $image,
            'root_pass' => $rootPass,
            'booted' => true,
        ]);
        if (! $response->successful()) {
            $error = collect($response->json('errors') ?? [])->pluck('reason')->implode(', ') ?: ('HTTP '.$response->status());

            return ['ok' => false, 'message' => 'Linode yeniden kurulum hatası: '.$error];
        }

        return [
            'ok' => true,
            'message' => 'Sunucu yeniden kuruluyor.',
            'root_password' => $rootPass,
        ];
    }

    public function resetRootPassword(CloudProvider $account, string $externalId): array
    {
        // Linode ayri root sifre sifirlama sunmaz; "Yeniden Kur" islemiyle yeni sifre atanir.
        return [
            'ok' => false,
            'message' => 'Linode\'da root şifre, "Yeniden Kur" (rebuild) işlemiyle yenilenir.',
            'root_password' => null,
        ];
    }

    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array
    {
        $response = $this->request($account, 'post', self::BASE.'/linode/instances/'.$externalId.'/resize', [
            'type' => $plan,
        ]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Paket yükseltiliyor.'
                : ('Linode yükseltme hatası: '.(collect($response->json('errors') ?? [])->pluck('reason')->implode(', ') ?: ('HTTP '.$response->status()))),
        ];
    }

    public function listImages(CloudProvider $account): array
    {
        $out = [];
        $page = 1;
        do {
            $response = $this->request($account, 'get', self::BASE.'/images', ['page' => $page, 'page_size' => 100]);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('data') ?? [] as $img) {
                $id = $img['id'] ?? null;
                if (! $id) {
                    continue;
                }
                $out[] = ['id' => (string) $id, 'label' => (string) ($img['label'] ?? $id)];
            }
            $pages = (int) ($response->json('pages') ?? 1);
            $page++;
        } while ($page <= $pages && $page < 10);

        return $out;
    }

    public function listPlans(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/linode/types', ['page_size' => 200]);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('data') ?? [] as $t) {
            $id = $t['id'] ?? null;
            if (! $id) {
                continue;
            }
            $label = ($t['label'] ?? $id).' — '.($t['vcpus'] ?? '?').' vCPU, '.(($t['memory'] ?? 0) / 1024).' GB RAM';
            $out[] = ['id' => (string) $id, 'label' => $label];
        }

        return $out;
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
