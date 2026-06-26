<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Contabo Cloud VPS/VDS sürücüsü.
 * OAuth2 (password grant) ile token alınır, ardından Compute API kullanılır.
 *
 * Kimlikler (Customer Control Panel → API): client_id, client_secret, api_user (e-posta), api_password.
 * Dikkat: createServer Contabo'da gerçek bir VPS/VDS SİPARİŞİ oluşturur ve ücretlendirme başlatır
 * (aylık sözleşme). Test ederken bunu göz önünde bulundurun.
 *
 * @see https://api.contabo.com/
 */
class ContaboDriver implements CloudProviderDriverInterface
{
    private const AUTH_URL = 'https://auth.contabo.com/auth/realms/contabo/protocol/openid-connect/token';

    private const BASE = 'https://api.contabo.com/v1';

    public function apiName(): string
    {
        return 'contabo';
    }

    public function isConfigured(CloudProvider $account): bool
    {
        $c = $account->credentials ?? [];

        return ! empty($c['client_id']) && ! empty($c['client_secret'])
            && ! empty($c['api_user']) && ! empty($c['api_password']);
    }

    public function testConnection(CloudProvider $account): array
    {
        try {
            $response = $this->request($account, 'get', self::BASE.'/compute/instances', ['size' => 1]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Contabo bağlantı hatası: '.$e->getMessage()];
        }

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Contabo API bağlantısı başarılı.'];
        }

        return ['ok' => false, 'message' => 'Contabo API hatası: HTTP '.$response->status()];
    }

    public function createServer(CloudProvider $account, array $config): array
    {
        $rootPassword = (string) ($config['root_password'] ?? (bin2hex(random_bytes(10)).'Aa1!'));
        $secretId = $this->createPasswordSecret($account, $config['hostname'], $rootPassword);

        $payload = array_filter([
            'imageId' => $config['image'],
            'productId' => $config['plan'],
            'region' => strtoupper($config['region']),
            'displayName' => $config['hostname'],
            'period' => 1,
            'defaultUser' => 'root',
            'rootPassword' => $secretId,
            'userData' => $config['user_data'] ?? null,
            'sshKeys' => ! empty($config['ssh_keys']) ? array_map('intval', array_values($config['ssh_keys'])) : null,
        ], fn ($v) => $v !== null);

        $response = $this->request($account, 'post', self::BASE.'/compute/instances', $payload);
        if (! $response->successful()) {
            $error = $response->json('messages.0') ?? $response->json('message') ?? $response->body();

            throw new RuntimeException('Contabo sunucu oluşturulamadı: '.(is_string($error) ? $error : json_encode($error)));
        }

        $data = $response->json('data.0') ?? [];
        $instanceId = (string) ($data['instanceId'] ?? '');

        return [
            'external_id' => $instanceId,
            'ipv4' => $this->ipv4($data),
            'ipv6' => $this->ipv6($data),
            'root_password' => $rootPassword,
            'status' => (string) ($data['status'] ?? 'provisioning'),
            'meta' => ['provider' => 'contabo', 'secret_id' => $secretId],
        ];
    }

    public function getServer(CloudProvider $account, string $externalId): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/compute/instances/'.$externalId);
        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data.0') ?? [];
        $status = (string) ($data['status'] ?? 'unknown');
        $ipv4 = $this->ipv4($data);

        return [
            'external_id' => (string) ($data['instanceId'] ?? $externalId),
            'ipv4' => $ipv4 ?: null,
            'ipv6' => $this->ipv6($data),
            'status' => $status,
            'ready' => in_array($status, ['running', 'stopped'], true) && ! empty($ipv4),
            'meta' => ['provider' => 'contabo'],
        ];
    }

    public function listServers(CloudProvider $account): array
    {
        $out = [];
        $page = 1;
        do {
            $response = $this->request($account, 'get', self::BASE.'/compute/instances', ['page' => $page, 'size' => 50]);
            if (! $response->successful()) {
                break;
            }
            foreach ($response->json('data') ?? [] as $server) {
                $out[] = [
                    'external_id' => (string) ($server['instanceId'] ?? ''),
                    'hostname' => (string) ($server['displayName'] ?? $server['name'] ?? ''),
                    'ipv4' => $this->ipv4($server),
                    'ipv6' => $this->ipv6($server),
                    'status' => (string) ($server['status'] ?? 'unknown'),
                    'region' => $server['region'] ?? null,
                    'plan' => $server['productId'] ?? null,
                    'image' => $server['imageId'] ?? null,
                ];
            }
            $totalPages = (int) ($response->json('_pagination.totalPages') ?? 1);
            $page = $page < $totalPages ? $page + 1 : 0;
        } while ($page > 0);

        return $out;
    }

    public function powerAction(CloudProvider $account, string $externalId, string $action): array
    {
        $endpoint = match ($action) {
            'start' => 'start',
            'stop' => 'stop',
            'reboot' => 'restart',
            default => null,
        };
        if ($endpoint === null) {
            return ['ok' => false, 'message' => 'Geçersiz güç işlemi.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/compute/instances/'.$externalId.'/actions/'.$endpoint);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'İşlem gönderildi.' : ('Contabo hatası: HTTP '.$response->status()),
        ];
    }

    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array
    {
        $rootPassword = bin2hex(random_bytes(10)).'Aa1!';
        $secretId = $this->createPasswordSecret($account, 'rebuild-'.$externalId, $rootPassword);

        $payload = array_filter([
            'imageId' => $image,
            'rootPassword' => $secretId,
            'defaultUser' => 'root',
        ], fn ($v) => $v !== null);

        // Contabo'da yeniden kurulum PUT ile imageId güncellenerek yapılır.
        $response = $this->request($account, 'put', self::BASE.'/compute/instances/'.$externalId, $payload);
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Contabo yeniden kurulum hatası: HTTP '.$response->status()];
        }

        return ['ok' => true, 'message' => 'Sunucu yeniden kuruluyor.', 'root_password' => $rootPassword];
    }

    public function resetRootPassword(CloudProvider $account, string $externalId): array
    {
        // Contabo root şifresi yeniden kurulumla (rebuild) sıfırlanır; ayrı bir reset endpoint'i yoktur.
        return ['ok' => false, 'message' => 'Contabo root şifresi yalnızca yeniden kurulum (reinstall) ile sıfırlanır.'];
    }

    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array
    {
        $response = $this->request($account, 'put', self::BASE.'/compute/instances/'.$externalId, ['productId' => $plan]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? 'Paket yükseltme talebi gönderildi.'
                : ('Contabo yükseltme hatası: HTTP '.$response->status()),
        ];
    }

    public function listImages(CloudProvider $account): array
    {
        $out = [];
        $response = $this->request($account, 'get', self::BASE.'/compute/images', ['size' => 100, 'standardImage' => 'true']);
        if (! $response->successful()) {
            return $out;
        }
        foreach ($response->json('data') ?? [] as $img) {
            $id = $img['imageId'] ?? null;
            if (! $id) {
                continue;
            }
            $out[] = ['id' => (string) $id, 'label' => (string) ($img['name'] ?? $id)];
        }

        return $out;
    }

    public function listPlans(CloudProvider $account): array
    {
        // Contabo plan/productId listesi API'de dinamik değildir; standart ürün kodları:
        return [
            ['id' => 'V1', 'label' => 'VPS S SSD — 4 vCPU, 8 GB'],
            ['id' => 'V2', 'label' => 'VPS M SSD — 6 vCPU, 16 GB'],
            ['id' => 'V3', 'label' => 'VPS L SSD — 8 vCPU, 30 GB'],
            ['id' => 'V4', 'label' => 'VPS XL SSD — 10 vCPU, 60 GB'],
            ['id' => 'V12', 'label' => 'VPS S NVMe — 4 vCPU, 8 GB'],
            ['id' => 'V13', 'label' => 'VPS M NVMe — 6 vCPU, 16 GB'],
            ['id' => 'V14', 'label' => 'VPS L NVMe — 8 vCPU, 30 GB'],
            ['id' => 'V15', 'label' => 'VPS XL NVMe — 10 vCPU, 60 GB'],
            ['id' => 'V8', 'label' => 'VDS S — 3 pCPU, 24 GB'],
            ['id' => 'V9', 'label' => 'VDS M — 4 pCPU, 32 GB'],
            ['id' => 'V10', 'label' => 'VDS L — 6 pCPU, 48 GB'],
            ['id' => 'V11', 'label' => 'VDS XL — 8 pCPU, 64 GB'],
            ['id' => 'V16', 'label' => 'VDS XXL — 12 pCPU, 96 GB'],
        ];
    }

    public function destroyServer(CloudProvider $account, string $externalId): bool
    {
        // Contabo'da DELETE sözleşmeyi iptal eder (cancel).
        return $this->request($account, 'delete', self::BASE.'/compute/instances/'.$externalId)->successful();
    }

    /**
     * Root parola için Contabo "secret" oluşturur ve secretId döner.
     */
    private function createPasswordSecret(CloudProvider $account, string $name, string $value): int
    {
        $response = $this->request($account, 'post', self::BASE.'/secrets', [
            'name' => 'hv-'.Str::slug($name).'-'.substr(bin2hex(random_bytes(4)), 0, 6),
            'value' => $value,
            'type' => 'password',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Contabo root parola secret oluşturulamadı: HTTP '.$response->status());
        }

        $secretId = $response->json('data.0.secretId');
        if (! is_numeric($secretId)) {
            throw new RuntimeException('Contabo secretId alınamadı.');
        }

        return (int) $secretId;
    }

    /** @param array<string, mixed> $data */
    private function ipv4(array $data): ?string
    {
        return $data['ipConfig']['v4']['ip'] ?? ($data['ipv4'] ?? null) ?: null;
    }

    /** @param array<string, mixed> $data */
    private function ipv6(array $data): ?string
    {
        return $data['ipConfig']['v6']['ip'] ?? ($data['ipv6'] ?? null) ?: null;
    }

    private function token(CloudProvider $account): string
    {
        $c = $account->credentials ?? [];
        $cacheKey = 'cloud.contabo.token.'.md5((string) ($c['client_id'] ?? '').($c['api_user'] ?? ''));

        return Cache::remember($cacheKey, 240, function () use ($c): string {
            $response = Http::asForm()->acceptJson()->timeout(30)->post(self::AUTH_URL, [
                'client_id' => (string) ($c['client_id'] ?? ''),
                'client_secret' => (string) ($c['client_secret'] ?? ''),
                'username' => (string) ($c['api_user'] ?? ''),
                'password' => (string) ($c['api_password'] ?? ''),
                'grant_type' => 'password',
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Contabo token alınamadı (kimlik bilgilerini kontrol edin): HTTP '.$response->status());
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Contabo access_token boş döndü.');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function request(CloudProvider $account, string $method, string $url, array $payload = [])
    {
        $pending = Http::timeout(90)
            ->acceptJson()
            ->withToken($this->token($account))
            ->withHeaders(['x-request-id' => (string) Str::uuid()]);

        return match ($method) {
            'get' => $pending->get($url, $payload),
            'post' => $pending->post($url, $payload),
            'put' => $pending->put($url, $payload),
            'delete' => $pending->delete($url),
            default => $pending->get($url),
        };
    }
}
