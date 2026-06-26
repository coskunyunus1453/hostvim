<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;

interface DomainRegistrarDriverInterface
{
    public function apiName(): string;

    public function isConfigured(DomainRegistrar $account): bool;

    /** @return array{ok: bool, message: string} */
    public function testConnection(DomainRegistrar $account): array;

    /**
     * @return array<string, array{register: float, renew: float, transfer?: float, currency: string}>
     */
    public function fetchTldPricing(DomainRegistrar $account): array;

    /**
     * @return array{available: bool, register_price?: float, renew_price?: float, transfer_price?: float, currency: string, reason?: string}
     */
    public function checkAvailability(DomainRegistrar $account, string $domain): array;
}
