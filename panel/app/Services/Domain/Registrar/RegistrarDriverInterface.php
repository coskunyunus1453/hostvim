<?php

namespace App\Services\Domain\Registrar;

use App\Models\User;

interface RegistrarDriverInterface
{
    /**
     * @return array{status: string, registrar: string, ref?: string, expires_at?: string, notes?: string}
     */
    public function register(string $domain, int $years, User $user): array;
}
