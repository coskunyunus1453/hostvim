<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistration;
use App\Models\User;

/** Registrar API anahtarı yoksa kayıt yönetici onay kuyruğuna düşer. */
class ManualRegistrarDriver implements RegistrarDriverInterface
{
    public function register(string $domain, int $years, User $user): array
    {
        return [
            'status' => DomainRegistration::STATUS_PENDING,
            'registrar' => 'manual',
            'ref' => 'MAN-'.strtoupper(substr(sha1($domain.$user->id.time()), 0, 12)),
            'notes' => 'Manuel registrar kuyruğu — yönetici panelden tamamlayacak.',
        ];
    }
}
