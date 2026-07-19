<?php

namespace App\Services\Domain;

use App\Models\DomainRegistration;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Domain\Registrar\RegistrarDriverResolver;
use App\Services\DomainDnsBootstrapService;
use App\Services\SafeAuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DomainRegistrarService
{
    public function __construct(
        private RegistrarDriverResolver $resolver,
        private DomainDnsBootstrapService $dnsBootstrap,
    ) {}

    public function registerFromOrderItem(User $user, OrderItem $item): DomainRegistration
    {
        $domain = strtolower(trim((string) $item->domain));
        $years = max(1, (int) $item->domain_years);

        return DB::transaction(function () use ($user, $item, $domain, $years): DomainRegistration {
            $existing = DomainRegistration::query()->where('domain', $domain)->lockForUpdate()->first();
            if ($existing !== null && in_array($existing->status, [DomainRegistration::STATUS_PENDING, DomainRegistration::STATUS_ACTIVE], true)) {
                return $existing;
            }

            $result = $this->resolver->driver()->register($domain, $years, $user);

            $registrar = (string) ($item->registrar_api ?? '');
            if ($registrar === '') {
                $registrar = $result['registrar'];
            } else {
                $result['registrar'] = $registrar;
            }

            $reg = DomainRegistration::create([
                'user_id' => $user->id,
                'order_item_id' => $item->id,
                'domain' => $domain,
                'years' => $years,
                'status' => $result['status'],
                'registrar' => $registrar !== '' ? $registrar : $result['registrar'],
                'registrar_ref' => $result['ref'] ?? null,
                'expires_at' => isset($result['expires_at']) ? Carbon::parse($result['expires_at']) : Carbon::now()->addYears($years),
                'notes' => $result['notes'] ?? null,
            ]);

            SafeAuditLogger::info('panelze.domain.register', [
                'user_id' => $user->id,
                'domain' => $domain,
                'registrar' => $reg->registrar,
                'status' => $reg->status,
            ], request());

            $this->dnsBootstrap->ensureAuthoritativeZone($user, $domain);

            return $reg;
        });
    }
}
