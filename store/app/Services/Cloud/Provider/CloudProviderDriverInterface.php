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
     * @param  array{hostname: string, region: string, plan: string, image: string, labels?: array<string, string>}  $config
     * @return array{external_id: string, ipv4: ?string, ipv6: ?string, root_password: ?string, status: string, meta?: array<string, mixed>}
     */
    public function createServer(CloudProvider $account, array $config): array;

    public function destroyServer(CloudProvider $account, string $externalId): bool;
}
