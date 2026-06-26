<?php

namespace App\Services\Domain;

use App\Jobs\FulfillDomainOrderJob;
use App\Models\DomainName;
use App\Models\Order;
use App\Services\AdminNotificationService;
use Throwable;

/**
 * Domain siparislerini (item_type=domain_register) odeme sonrasi otomatik olarak
 * saglayicida (Spaceship) register eder ve musteriye baglar.
 */
class DomainProvisioningService
{
    public function __construct(
        private DomainManagementService $management,
        private AdminNotificationService $notifications,
    ) {}

    public function dispatchIfNeeded(Order $order): void
    {
        $order = $order->fresh('items');
        if ($order === null || $order->payment_status !== 'paid') {
            return;
        }
        if (! $this->hasDomainItems($order)) {
            return;
        }
        if ($this->management->preferredApi() === null) {
            // Otomatik kayit destekleyen saglayici yoksa atla (manuel surec).
            return;
        }

        FulfillDomainOrderJob::dispatch($order->id);
    }

    public function process(Order $order): void
    {
        $order = $order->fresh('items');
        if ($order === null || $order->payment_status !== 'paid') {
            return;
        }

        foreach ($this->domainItems($order) as $item) {
            $domain = strtolower(trim((string) ($item->domain_name ?? '')));
            if ($domain === '') {
                continue;
            }

            $existing = DomainName::query()->where('domain', $domain)->first();
            if ($existing !== null && in_array($existing->status, ['registered', 'active', 'registering'], true)) {
                // Zaten kayitli/kayit suruyor — mukerrer islemi onle, sadece musteriye bagla.
                if (! $existing->customer_email) {
                    $existing->update(['customer_email' => $order->customer_email, 'order_id' => $order->id]);
                }

                continue;
            }

            $years = max(1, min(10, (int) ($item->domain_years ?? 1)));
            $apiName = is_array($item->config_meta ?? null) ? ($item->config_meta['registrar_api'] ?? null) : null;

            try {
                $result = $this->management->registerForOrder($order, $domain, $years, $apiName);
                if (! ($result['ok'] ?? false)) {
                    $this->notifications->fromDomainProvisionFailed($order, $domain, $result['message'] ?? null);
                }
            } catch (Throwable $e) {
                report($e);
                $this->notifications->fromDomainProvisionFailed($order, $domain, $e->getMessage());
            }
        }
    }

    private function hasDomainItems(Order $order): bool
    {
        return $this->domainItems($order) !== [];
    }

    /** @return list<\App\Models\OrderItem> */
    private function domainItems(Order $order): array
    {
        $out = [];
        foreach ($order->items as $item) {
            if (($item->item_type ?? '') === 'domain_register') {
                $out[] = $item;
            }
        }

        return $out;
    }
}
