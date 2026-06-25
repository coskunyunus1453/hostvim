<?php

namespace App\Services\Cloud;

use App\Jobs\InstallPanelOnServerJob;
use App\Models\CloudProvider;
use App\Models\CloudServer;
use App\Services\Cloud\Provider\CloudProviderResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Mevcut bulut sunucularinin yasam dongusu yonetimi (admin/panel aksiyonlari):
 * guc, yeniden kurulum/OS, root sifre, paket yukseltme, panel kurulumu, silme.
 */
class CloudServerManager
{
    public function __construct(
        private CloudProviderResolver $providers,
        private CloudSettings $settings,
    ) {}

    /** @return array{ok: bool, message: string} */
    public function power(CloudServer $server, string $action): array
    {
        [$driver, $account] = $this->resolve($server);
        $result = $driver->powerAction($account, $server->external_id, $action);
        $this->log($server, 'power.'.$action, $result);

        return ['ok' => (bool) $result['ok'], 'message' => (string) $result['message']];
    }

    /** @return array{ok: bool, message: string} */
    public function rebuild(CloudServer $server, ?string $image = null): array
    {
        [$driver, $account] = $this->resolve($server);
        $result = $driver->rebuildServer($account, $server->external_id, $image);
        if ($result['ok'] ?? false) {
            $attrs = ['status' => CloudServer::STATUS_PROVISIONING];
            if ($image) {
                $attrs['image'] = $image;
            }
            if (! empty($result['root_password'])) {
                $attrs['root_password'] = $result['root_password'];
            }
            $server->update($attrs);
        }
        $this->log($server, 'rebuild', $result);

        return ['ok' => (bool) $result['ok'], 'message' => (string) $result['message']];
    }

    /** @return array{ok: bool, message: string} */
    public function resetPassword(CloudServer $server): array
    {
        [$driver, $account] = $this->resolve($server);
        $result = $driver->resetRootPassword($account, $server->external_id);
        if (($result['ok'] ?? false) && ! empty($result['root_password'])) {
            $server->update(['root_password' => $result['root_password']]);
        }
        $this->log($server, 'reset_password', ['ok' => $result['ok'] ?? false, 'message' => $result['message'] ?? '']);

        return ['ok' => (bool) $result['ok'], 'message' => (string) $result['message']];
    }

    /** @return array{ok: bool, message: string} */
    public function resize(CloudServer $server, string $plan): array
    {
        [$driver, $account] = $this->resolve($server);
        $result = $driver->resizeServer($account, $server->external_id, $plan);
        if ($result['ok'] ?? false) {
            $server->update(['plan' => $plan]);
        }
        $this->log($server, 'resize', $result);

        return ['ok' => (bool) $result['ok'], 'message' => (string) $result['message']];
    }

    /** @return list<array{id: string, label: string}> */
    public function images(CloudServer $server): array
    {
        [$driver, $account] = $this->resolve($server);

        return Cache::remember(
            'cloud:images:'.$server->provider_api,
            now()->addHours(6),
            fn () => $driver->listImages($account),
        );
    }

    /** @return list<array{id: string, label: string}> */
    public function plans(CloudServer $server): array
    {
        [$driver, $account] = $this->resolve($server);

        return Cache::remember(
            'cloud:plans:'.$server->provider_api,
            now()->addHours(6),
            fn () => $driver->listPlans($account),
        );
    }

    /** @return array{ok: bool, message: string} */
    public function installPanel(CloudServer $server): array
    {
        if (! $server->ipv4) {
            return ['ok' => false, 'message' => 'Sunucunun IP adresi yok; önce IP atanmasını bekleyin.'];
        }
        if (! $server->root_password) {
            return ['ok' => false, 'message' => 'Root şifresi kayıtlı değil. Önce "Root Şifre Sıfırla" yapın.'];
        }
        if (($server->meta['panel_install'] ?? null) === 'running') {
            return ['ok' => false, 'message' => 'Panel kurulumu zaten sürüyor.'];
        }

        $server->update(['meta' => array_merge($server->meta ?? [], [
            'panel_install' => 'queued',
            'panel_install_at' => now()->toIso8601String(),
        ])]);

        InstallPanelOnServerJob::dispatch($server->id);
        $this->log($server, 'install_panel', ['ok' => true, 'message' => 'Kuyruğa alındı']);

        return ['ok' => true, 'message' => 'Panelze kurulumu başlatıldı. Birkaç dakika içinde tamamlanır.'];
    }

    public function destroy(CloudServer $server): array
    {
        [$driver, $account] = $this->resolve($server);
        $ok = $driver->destroyServer($account, $server->external_id);
        if ($ok) {
            $server->update(['status' => CloudServer::STATUS_DESTROYED]);
        }
        $this->log($server, 'destroy', ['ok' => $ok, 'message' => $ok ? 'Silindi' : 'Silinemedi']);

        return ['ok' => $ok, 'message' => $ok ? 'Sunucu silindi.' : 'Sunucu silinemedi (API hatası).'];
    }

    /**
     * @return array{0: \App\Services\Cloud\Provider\CloudProviderDriverInterface, 1: CloudProvider}
     */
    private function resolve(CloudServer $server): array
    {
        if (! $server->external_id) {
            throw new RuntimeException('Sunucunun sağlayıcı kimliği (external_id) yok.');
        }

        $account = CloudProvider::query()->where('api_name', $server->provider_api)->first();
        if ($account === null || ! $account->isConfigured()) {
            throw new RuntimeException($this->providers->providerLabel($server->provider_api).' API yapılandırılmamış.');
        }

        return [$this->providers->driver($server->provider_api), $account];
    }

    /** @param array<string, mixed> $result */
    private function log(CloudServer $server, string $action, array $result): void
    {
        Log::info('cloud.server.'.$action, [
            'server_id' => $server->id,
            'provider' => $server->provider_api,
            'external_id' => $server->external_id,
            'ok' => $result['ok'] ?? null,
            'message' => $result['message'] ?? null,
        ]);
    }
}
