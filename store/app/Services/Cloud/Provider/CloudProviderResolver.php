<?php

namespace App\Services\Cloud\Provider;

use App\Models\CloudProvider;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CloudProviderResolver
{
    /** @var array<string, CloudProviderDriverInterface> */
    private array $drivers;

    public function __construct(
        HetznerCloudDriver $hetzner,
        VultrDriver $vultr,
        DigitalOceanDriver $digitalocean,
        LinodeDriver $linode,
    ) {
        $this->drivers = [
            $hetzner->apiName() => $hetzner,
            $vultr->apiName() => $vultr,
            $digitalocean->apiName() => $digitalocean,
            $linode->apiName() => $linode,
        ];
    }

    public function driver(string $apiName): CloudProviderDriverInterface
    {
        $apiName = strtolower(trim($apiName));
        if (! isset($this->drivers[$apiName])) {
            throw new InvalidArgumentException('Bilinmeyen bulut API: '.$apiName);
        }

        return $this->drivers[$apiName];
    }

    public function account(string $apiName): ?CloudProvider
    {
        $account = CloudProvider::query()->where('api_name', $apiName)->first();
        if ($account === null || ! $account->is_enabled || ! $account->isConfigured()) {
            return null;
        }

        return $account;
    }

    /** @return Collection<int, CloudProvider> */
    public function enabledAccounts(): Collection
    {
        return CloudProvider::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (CloudProvider $a) => $a->isConfigured());
    }

    /** @return list<string> */
    public function apiNames(): array
    {
        return array_keys($this->drivers);
    }

    public function providerLabel(string $apiName): string
    {
        return (string) (config('cloud_providers.providers.'.$apiName.'.name') ?? $apiName);
    }
}
