<?php

namespace App\Services\Provisioning;

use App\Models\Domain;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\DomainService;
use App\Services\HostnameReservationService;
use App\Services\HostingQuotaService;
use App\Services\SafeAuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Faturalama yaşam döngüsünü hosting altyapısına bağlar:
 * ödeme → otomatik kurulum, yenileme → vade uzatma, gecikme → askı, son → fesih.
 *
 * Bilinçli olarak kullanıcının e-postasını/şifresini hiç loglamaz (KVKK/GDPR).
 */
class ProvisioningService
{
    public function __construct(
        private DomainService $domainService,
        private HostingQuotaService $quota,
        private HostnameReservationService $hostnames,
        private BillingSettings $settings,
    ) {}

    /**
     * Sipariş kalemini ilk kez kur: kullanıcı paketini ata, (varsa) alan adı sitesini oluştur,
     * ve hizmet (Subscription) kaydını aktif olarak başlat.
     */
    public function provisionFromOrderItem(User $user, OrderItem $item): Subscription
    {
        return DB::transaction(function () use ($user, $item): Subscription {
            $package = $item->hostingPackage;

            $user->forceFill([
                'status' => 'active',
                'hosting_package_id' => $package?->id,
                'hosting_package_manual_override' => true,
            ])->save();

            $domain = null;
            $domainName = $item->domain !== null ? strtolower(trim($item->domain)) : '';
            if ($domainName !== '') {
                $domain = $user->domains()->where('name', $domainName)->first();
                if ($domain === null) {
                    $this->quota->ensureCanCreateDomain($user);
                    $this->hostnames->assertPrimaryDomainForUser($user, $domainName);
                    $domain = $this->domainService->create(
                        $user,
                        $domainName,
                        (string) $this->settings->get('default_php', '8.2'),
                        (string) $this->settings->get('default_server_type', 'nginx'),
                    );
                }
            }

            $now = Carbon::now();
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'hosting_package_id' => $package?->id,
                'domain_id' => $domain?->id,
                'payment_provider' => 'manual',
                'status' => 'active',
                'service_status' => Subscription::SERVICE_ACTIVE,
                'provisioned_at' => $now,
                'billing_cycle' => $item->billing_cycle,
                'amount' => $item->unit_price,
                'setup_fee' => $item->setup_fee,
                'currency' => $this->settings->currency(),
                'auto_renew' => true,
                'starts_at' => $now,
                'next_due_at' => $this->addCycle($now, $item->billing_cycle),
            ]);

            SafeAuditLogger::info('panelze.billing.provision', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'hosting_package_id' => $package?->id,
                'domain' => $domainName !== '' ? $domainName : null,
            ], request());

            return $subscription;
        });
    }

    /** Yenileme ödemesi: vadeyi bir döngü uzat, askıdaysa tekrar aç. */
    public function renew(Subscription $subscription): Subscription
    {
        $base = $subscription->next_due_at && $subscription->next_due_at->isFuture()
            ? $subscription->next_due_at
            : Carbon::now();

        $subscription->forceFill([
            'status' => 'active',
            'next_due_at' => $this->addCycle($base, $subscription->billing_cycle),
        ]);

        if ($subscription->service_status === Subscription::SERVICE_SUSPENDED) {
            $this->unsuspend($subscription, save: false);
        }
        $subscription->service_status = Subscription::SERVICE_ACTIVE;
        $subscription->save();

        SafeAuditLogger::info('panelze.billing.renew', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'next_due_at' => optional($subscription->next_due_at)->toIso8601String(),
        ], request());

        return $subscription;
    }

    public function suspend(Subscription $subscription, string $reason = 'overdue'): void
    {
        $this->applyDomainStatus($subscription, 'suspended');
        $subscription->update(['service_status' => Subscription::SERVICE_SUSPENDED]);

        SafeAuditLogger::warning('panelze.billing.suspend', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'reason' => $reason,
        ], request());
    }

    public function unsuspend(Subscription $subscription, bool $save = true): void
    {
        $this->applyDomainStatus($subscription, 'active');
        if ($save) {
            $subscription->update(['service_status' => Subscription::SERVICE_ACTIVE]);
        } else {
            $subscription->service_status = Subscription::SERVICE_ACTIVE;
        }

        SafeAuditLogger::info('panelze.billing.unsuspend', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
        ], request());
    }

    public function terminate(Subscription $subscription, bool $deleteSite = false): void
    {
        $domain = $this->domainOf($subscription);
        if ($domain !== null) {
            try {
                if ($deleteSite) {
                    $this->domainService->delete($domain);
                } else {
                    $this->domainService->setPanelStatus($domain, 'suspended');
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $subscription->update([
            'status' => 'cancelled',
            'service_status' => Subscription::SERVICE_TERMINATED,
            'cancelled_at' => Carbon::now(),
        ]);

        SafeAuditLogger::warning('panelze.billing.terminate', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'delete_site' => $deleteSite,
        ], request());
    }

    private function applyDomainStatus(Subscription $subscription, string $status): void
    {
        $domain = $this->domainOf($subscription);
        if ($domain === null) {
            return;
        }
        if (in_array($domain->status, ['deleting'], true)) {
            return;
        }
        if ($status === 'active' && $domain->status !== 'suspended') {
            return;
        }
        try {
            $this->domainService->setPanelStatus($domain, $status);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function domainOf(Subscription $subscription): ?Domain
    {
        if ($subscription->domain_id === null) {
            return null;
        }

        return Domain::query()->find($subscription->domain_id);
    }

    private function addCycle(Carbon $from, string $cycle): Carbon
    {
        return $cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }
}
