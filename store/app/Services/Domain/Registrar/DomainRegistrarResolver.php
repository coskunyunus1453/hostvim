<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class DomainRegistrarResolver
{
    /** @var array<string, DomainRegistrarDriverInterface> */
    private array $drivers;

    public function __construct(
        PorkbunRegistrarDriver $porkbun,
        SpaceshipRegistrarDriver $spaceship,
        CloudflareRegistrarDriver $cloudflare,
        MetunicRegistrarDriver $metunic,
    ) {
        $this->drivers = [
            $porkbun->apiName() => $porkbun,
            $spaceship->apiName() => $spaceship,
            $cloudflare->apiName() => $cloudflare,
            $metunic->apiName() => $metunic,
        ];
    }

    public function driver(string $apiName): DomainRegistrarDriverInterface
    {
        $apiName = strtolower(trim($apiName));
        if (! isset($this->drivers[$apiName])) {
            throw new InvalidArgumentException('Bilinmeyen domain API: '.$apiName);
        }

        return $this->drivers[$apiName];
    }

    /** @return Collection<int, DomainRegistrar> */
    public function enabledAccounts(): Collection
    {
        return DomainRegistrar::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (DomainRegistrar $account) => $account->isConfigured());
    }

    public function account(string $apiName): ?DomainRegistrar
    {
        $account = DomainRegistrar::query()->where('api_name', $apiName)->first();
        if ($account === null || ! $account->is_enabled || ! $account->isConfigured()) {
            return null;
        }

        return $account;
    }

    /** @return list<string> */
    public function apiNames(): array
    {
        return array_keys($this->drivers);
    }

    public function providerLabel(string $apiName): string
    {
        $catalog = config('domain_registrars.providers.'.$apiName);

        return (string) ($catalog['name'] ?? $apiName);
    }
}
