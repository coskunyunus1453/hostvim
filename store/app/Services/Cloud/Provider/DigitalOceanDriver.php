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
        if (! empty($config['user_data'])) {
            $payload['user_data'] = $config['user_data'];
        }
        if (! empty($config['ssh_keys'])) {
            $payload['ssh_keys'] = array_values($config['ssh_keys']);
        }

        $response = $this->request($account, 'post', self::BASE.'/droplets', $payload);
        if (! $response->successful()) {
            $error = $response->json('message') ?? $response->body();

            throw new \RuntimeException('DigitalOcean Droplet oluşturulamadı: '.$error);
        }

        $droplet = $response->json('droplet') ?? [];
        $ipv4 = collect($droplet['networks']['v4'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;
        $ipv6 = collect($droplet['networks']['v6'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;

        // DO create yanitinda IP genelde bos gelir; getServer() ile polling yapilir.
        $hasSsh = ! empty($config['ssh_keys']);

        return [
            'external_id' => (string) ($droplet['id'] ?? ''),
            'ipv4' => $ipv4,
            'ipv6' => $ipv6,
            'root_password' => null,
            'status' => (string) ($droplet['status'] ?? 'new'),
            'meta' => [
                'provider' => 'digitalocean',
                'note' => $hasSsh ? 'SSH anahtarı ile giriş yapın.' : 'Cloud-init ile otomatik kurulum yapıldı; root erişimi için panelden şifre sıfırlayın.',
            ],
        ];
    }

    public function getServer(CloudProvider $account, string $externalId): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/droplets/'.$externalId);
        if (! $response->successful()) {
            return null;
        }

        $droplet = $response->json('droplet') ?? [];
        $status = (string) ($droplet['status'] ?? 'new');
        $ipv4 = collect($droplet['networks']['v4'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;
        $ipv6 = collect($droplet['networks']['v6'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;

        return [
            'external_id' => (string) ($droplet['id'] ?? $externalId),
            'ipv4' => $ipv4 ?: null,
            'ipv6' => $ipv6 ?: null,
            'status' => $status,
            'ready' => $status === 'active' && ! empty($ipv4),
            'meta' => ['provider' => 'digitalocean'],
        ];
    }

    public function listServers(CloudProvider $account): array
    {
        $out = [];
        $url = self::BASE.'/droplets?per_page=200';
        $guard = 0;
        while ($url !== null && $guard < 20) {
            $guard++;
            $response = $this->request($account, 'get', $url);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('droplets') ?? [] as $droplet) {
                $ipv4 = collect($droplet['networks']['v4'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null;
                $out[] = [
                    'external_id' => (string) ($droplet['id'] ?? ''),
                    'hostname' => (string) ($droplet['name'] ?? ''),
                    'ipv4' => $ipv4 ?: null,
                    'ipv6' => collect($droplet['networks']['v6'] ?? [])->firstWhere('type', 'public')['ip_address'] ?? null,
                    'status' => (string) ($droplet['status'] ?? 'unknown'),
                    'region' => $droplet['region']['slug'] ?? null,
                    'plan' => $droplet['size_slug'] ?? null,
                    'image' => $droplet['image']['slug'] ?? null,
                ];
            }
            $url = $response->json('links.pages.next');
        }

        return $out;
    }

    public function powerAction(CloudProvider $account, string $externalId, string $action): array
    {
        $type = match ($action) {
            'start' => 'power_on',
            'stop' => 'power_off',
            'reboot' => 'reboot',
            default => null,
        };
        if ($type === null) {
            return ['ok' => false, 'message' => 'Geçersiz güç işlemi.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/droplets/'.$externalId.'/actions', ['type' => $type]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'İşlem gönderildi.' : ('DigitalOcean hatası: HTTP '.$response->status()),
        ];
    }

    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array
    {
        $payload = ['type' => 'rebuild'];
        if ($image) {
            $payload['image'] = is_numeric($image) ? (int) $image : $image;
        }
        $response = $this->request($account, 'post', self::BASE.'/droplets/'.$externalId.'/actions', $payload);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Droplet yeniden kuruluyor. Erişim için panelden şifre sıfırlayın veya SSH anahtarı kullanın.'
                : ('DigitalOcean yeniden kurulum hatası: '.($response->json('message') ?? ('HTTP '.$response->status()))),
            'root_password' => null,
        ];
    }

    public function resetRootPassword(CloudProvider $account, string $externalId): array
    {
        $response = $this->request($account, 'post', self::BASE.'/droplets/'.$externalId.'/actions', ['type' => 'password_reset']);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Şifre sıfırlama başlatıldı. DigitalOcean yeni root şifresini hesap e-postanıza gönderir.'
                : ('DigitalOcean şifre sıfırlama hatası: HTTP '.$response->status()),
            'root_password' => null,
        ];
    }

    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array
    {
        $response = $this->request($account, 'post', self::BASE.'/droplets/'.$externalId.'/actions', [
            'type' => 'resize',
            'size' => $plan,
            'disk' => true,
        ]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Paket yükseltiliyor (droplet kapatılıp yeniden boyutlandırılır).'
                : ('DigitalOcean yükseltme hatası: '.($response->json('message') ?? ('HTTP '.$response->status()))),
        ];
    }

    public function listImages(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/images?type=distribution&per_page=200');
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('images') ?? [] as $img) {
            $slug = $img['slug'] ?? null;
            if (! $slug) {
                continue;
            }
            $label = trim(($img['distribution'] ?? '').' '.($img['name'] ?? '')) ?: $slug;
            $out[] = ['id' => (string) $slug, 'label' => $label];
        }

        return $out;
    }

    public function listPlans(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/sizes?per_page=200');
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('sizes') ?? [] as $size) {
            $slug = $size['slug'] ?? null;
            if (! $slug || ! ($size['available'] ?? true)) {
                continue;
            }
            $label = $slug.' — '.($size['vcpus'] ?? '?').' vCPU, '.(($size['memory'] ?? 0) / 1024).' GB RAM';
            $out[] = ['id' => (string) $slug, 'label' => $label];
        }

        return $out;
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
