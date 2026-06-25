<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;

interface CloudProviderDriverInterface
{
    public function apiName(): string;

    public function isConfigured(CloudProvider $account): bool;

    /** @return array{ok: bool, message: string} */
    public function testConnection(CloudProvider $account): array;

    /**
     * @param  array{hostname: string, region: string, plan: string, image: string, labels?: array<string, string>, user_data?: ?string, ssh_keys?: list<string>}  $config
     * @return array{external_id: string, ipv4: ?string, ipv6: ?string, root_password: ?string, status: string, meta?: array<string, mixed>}
     */
    public function createServer(CloudProvider $account, array $config): array;

    /**
     * Sunucunun guncel durumunu ve IP bilgisini ceker (provisioning sonrasi polling icin).
     *
     * @return array{external_id: string, ipv4: ?string, ipv6: ?string, status: string, ready: bool, meta?: array<string, mixed>}|null
     */
    public function getServer(CloudProvider $account, string $externalId): ?array;

    /**
     * Saglayicidaki tum sunuculari listeler (panele senkron/import icin).
     *
     * @return list<array{external_id: string, hostname: string, ipv4: ?string, ipv6: ?string, status: string, region: ?string, plan: ?string, image: ?string}>
     */
    public function listServers(CloudProvider $account): array;

    /**
     * Sunucu guc islemi: 'start' | 'stop' | 'reboot'.
     *
     * @return array{ok: bool, message: string}
     */
    public function powerAction(CloudProvider $account, string $externalId, string $action): array;

    /**
     * Sunucuyu yeniden kurar / format atar (opsiyonel yeni isletim sistemi imaji ile).
     *
     * @return array{ok: bool, message: string, root_password?: ?string}
     */
    public function rebuildServer(CloudProvider $account, string $externalId, ?string $image = null): array;

    /**
     * Root sifresini sifirlar. Bazi saglayicilar yeni sifreyi doner, bazilari e-posta/panel ile bildirir.
     *
     * @return array{ok: bool, message: string, root_password?: ?string}
     */
    public function resetRootPassword(CloudProvider $account, string $externalId): array;

    /**
     * Sunucu planini/paketini yukseltir/degistirir.
     *
     * @return array{ok: bool, message: string}
     */
    public function resizeServer(CloudProvider $account, string $externalId, string $plan): array;

    /**
     * Kurulabilir isletim sistemi imajlarini listeler.
     *
     * @return list<array{id: string, label: string}>
     */
    public function listImages(CloudProvider $account): array;

    /**
     * Mevcut plan/paketleri listeler (yukseltme icin).
     *
     * @return list<array{id: string, label: string}>
     */
    public function listPlans(CloudProvider $account): array;

    public function destroyServer(CloudProvider $account, string $externalId): bool;
}
