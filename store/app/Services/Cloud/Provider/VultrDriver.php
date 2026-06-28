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
        if (! empty($config['user_data'])) {
            $payload['user_data'] = base64_encode((string) $config['user_data']);
        }
        if (! empty($config['ssh_keys'])) {
            $payload['sshkey_id'] = array_values($config['ssh_keys']);
        }

        $response = $this->request($account, 'post', self::BASE.'/instances', $payload);
        if (! $response->successful()) {
            $error = $response->json('error') ?? $response->body();

            throw new \RuntimeException('Vultr instance oluşturulamadı: '.(is_string($error) ? $error : json_encode($error)));
        }

        $instance = $response->json('instance') ?? [];
        $ip = $instance['main_ip'] ?? null;

        return [
            'external_id' => (string) ($instance['id'] ?? ''),
            'ipv4' => ($ip && $ip !== '0.0.0.0') ? $ip : null,
            'ipv6' => $instance['ipv6_main_ip'] ?? null,
            'root_password' => $instance['default_password'] ?? null,
            'status' => (string) ($instance['status'] ?? 'pending'),
            'meta' => ['provider' => 'vultr'],
        ];
    }

    public function getServer(CloudProvider $account, string $externalId): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/instances/'.$externalId);
        if (! $response->successful()) {
            return null;
        }

        $instance = $response->json('instance') ?? [];
        $status = (string) ($instance['status'] ?? 'pending');
        $serverStatus = (string) ($instance['server_status'] ?? '');
        $ip = $instance['main_ip'] ?? null;
        $hasIp = $ip && $ip !== '0.0.0.0';

        return [
            'external_id' => (string) ($instance['id'] ?? $externalId),
            'ipv4' => $hasIp ? $ip : null,
            'ipv6' => $instance['ipv6_main_ip'] ?? null,
            'status' => $status,
            'ready' => $status === 'active' && $hasIp && in_array($serverStatus, ['ok', 'installingbooting', ''], true),
            'meta' => ['provider' => 'vultr'],
        ];
    }

    public function listServers(CloudProvider $account): array
    {
        $out = [];
        $cursor = null;
        $guard = 0;
        do {
            $guard++;
            $query = ['per_page' => 200];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }
            $response = $this->request($account, 'get', self::BASE.'/instances', $query);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('instances') ?? [] as $instance) {
                $ip = $instance['main_ip'] ?? null;
                $out[] = [
                    'external_id' => (string) ($instance['id'] ?? ''),
                    'hostname' => (string) ($instance['label'] ?? $instance['hostname'] ?? ''),
                    'ipv4' => ($ip && $ip !== '0.0.0.0') ? $ip : null,
                    'ipv6' => $instance['ipv6_main_ip'] ?? null,
                    'status' => (string) ($instance['status'] ?? 'unknown'),
                    'region' => $instance['region'] ?? null,
                    'plan' => $instance['plan'] ?? null,
                    'image' => isset($instance['os_id']) ? (string) $instance['os_id'] : null,
                ];
            }
            $cursor = $response->json('meta.links.next') ?: null;
        } while ($cursor && $guard < 20);

        return $out;
    }

    public function powerAction(CloudProvider $account, string $externalId, string $action): array
    {
        $endpoint = match ($action) {
            'start' => 'start',
            'stop' => 'halt',
            'reboot' => 'reboot',
            default => null,
        };
        if ($endpoint === null) {
            return ['ok' => false, 'message' => 'Geçersiz güç işlemi.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/instances/'.$externalId.'/'.$endpoint);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'İşlem gönderildi.' : ('Vultr hatası: HTTP '.$response->status()),
        ];
    }

    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array
    {
        // Farkli OS istenirse os_id degistir, ayni OS icin reinstall.
        if ($image && is_numeric($image)) {
            $response = $this->request($account, 'patch', self::BASE.'/instances/'.$externalId, ['os_id' => (int) $image]);
        } else {
            $response = $this->request($account, 'post', self::BASE.'/instances/'.$externalId.'/reinstall');
        }
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Vultr yeniden kurulum hatası: HTTP '.$response->status()];
        }

        $pass = $response->json('instance.default_password');

        return [
            'ok' => true,
            'message' => 'Sunucu yeniden kuruluyor.',
            'root_password' => is_string($pass) ? $pass : null,
        ];
    }

    public function resetRootPassword(CloudProvider $account, string $externalId): array
    {
        // Vultr ayri sifre sifirlama sunmaz; reinstall yeni sifre uretir.
        $response = $this->request($account, 'post', self::BASE.'/instances/'.$externalId.'/reinstall');
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Vultr şifre sıfırlama hatası: HTTP '.$response->status()];
        }

        $pass = $response->json('instance.default_password');

        return [
            'ok' => true,
            'message' => 'Sunucu yeniden kuruldu ve yeni root şifre atandı.',
            'root_password' => is_string($pass) ? $pass : null,
        ];
    }

    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array
    {
        $response = $this->request($account, 'patch', self::BASE.'/instances/'.$externalId, ['plan' => $plan]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'Paket yükseltiliyor.' : ('Vultr yükseltme hatası: HTTP '.$response->status()),
        ];
    }

    public function listImages(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/os', ['per_page' => 500]);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('os') ?? [] as $os) {
            $id = $os['id'] ?? null;
            if ($id === null) {
                continue;
            }
            $out[] = ['id' => (string) $id, 'label' => (string) ($os['name'] ?? $id)];
        }

        return $out;
    }

    public function listPlans(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/plans', ['per_page' => 500]);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('plans') ?? [] as $plan) {
            $id = $plan['id'] ?? null;
            if (! $id) {
                continue;
            }
            $label = $id.' — '.($plan['vcpu_count'] ?? '?').' vCPU, '.(($plan['ram'] ?? 0) / 1024).' GB RAM';
            $out[] = ['id' => (string) $id, 'label' => $label];
        }

        return $out;
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
            'patch' => $pending->patch($url, $payload),
            'delete' => $pending->delete($url),
            default => $pending->get($url),
        };
    }
}
