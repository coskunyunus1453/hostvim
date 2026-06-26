<?php

namespace App\Services\Billing;

use App\Models\HostingPackage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Services\Domain\DomainAvailabilityService;
use App\Services\SafeAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private BillingSettings $settings,
        private InvoiceService $invoices,
        private DomainAvailabilityService $domains,
    ) {}

    /**
     * @param  list<array{item_type?:string, package_id?:int, billing_cycle?:string, domain?:?string, domain_years?:int}>  $items
     * @return array{order: Order, invoice: Invoice}
     */
    public function place(User $user, array $items, ?int $resellerId = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'En az bir ürün seçin.']);
        }

        if (! (bool) $this->settings->get('enabled', true)) {
            throw ValidationException::withMessages(['items' => 'Faturalama şu an kapalı.']);
        }

        return DB::transaction(function () use ($user, $items, $resellerId): array {
            $order = Order::create([
                'number' => 'TEMP',
                'user_id' => $user->id,
                'reseller_id' => $resellerId,
                'status' => Order::STATUS_PENDING,
                'currency' => $this->settings->currency(),
                'total' => 0,
            ]);
            $order->update(['number' => $this->invoices->numberFor('order_prefix', $order->id)]);

            $total = 0.0;
            foreach ($items as $row) {
                $type = strtolower(trim((string) ($row['item_type'] ?? 'hosting')));

                if ($type === 'domain_register') {
                    if (! (bool) $this->settings->get('domain_register_enabled', true)) {
                        throw ValidationException::withMessages(['items' => 'Alan adı kaydı şu an kapalı.']);
                    }
                    $domain = isset($row['domain']) ? strtolower(trim((string) $row['domain'])) : '';
                    if ($domain === '') {
                        throw ValidationException::withMessages(['items' => 'Alan adı gerekli.']);
                    }
                    $years = max(1, min(10, (int) ($row['domain_years'] ?? 1)));
                    $storePrice = isset($row['unit_price']) ? round((float) $row['unit_price'], 2) : null;
                    if ($storePrice !== null && $storePrice > 0) {
                        $price = $storePrice;
                    } else {
                        $check = $this->domains->check($domain);
                        if (! ($check['available'] ?? false)) {
                            throw ValidationException::withMessages(['domain' => 'Alan adı müsait değil veya desteklenmiyor.']);
                        }
                        $price = $this->domains->priceFor($domain, $years);
                    }

                    $order->items()->create([
                        'item_type' => 'domain_register',
                        'billing_cycle' => 'yearly',
                        'domain' => $domain,
                        'domain_years' => $years,
                        'registrar_api' => ! empty($row['registrar_api']) ? (string) $row['registrar_api'] : null,
                        'unit_price' => $price,
                        'setup_fee' => 0,
                    ]);
                    $total += $price;
                } elseif ($type === 'manual') {
                    $label = trim((string) ($row['product_name'] ?? ''));
                    if ($label === '') {
                        throw ValidationException::withMessages(['items' => 'Manuel ürün adı gerekli.']);
                    }
                    $cycle = ($row['billing_cycle'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';
                    $price = round((float) ($row['unit_price'] ?? 0), 2);
                    if ($price <= 0) {
                        throw ValidationException::withMessages(['items' => 'Manuel ürün fiyatı geçersiz.']);
                    }

                    $order->items()->create([
                        'item_type' => 'manual',
                        'billing_cycle' => $cycle,
                        'domain' => $label,
                        'unit_price' => $price,
                        'setup_fee' => 0,
                    ]);
                    $total += $price;
                } else {
                    $package = HostingPackage::query()->where('is_active', true)->findOrFail((int) ($row['package_id'] ?? 0));
                    $cycle = ($row['billing_cycle'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';
                    $price = (float) ($cycle === 'yearly' ? $package->price_yearly : $package->price_monthly);
                    $domain = isset($row['domain']) ? strtolower(trim((string) $row['domain'])) : '';

                    $order->items()->create([
                        'item_type' => 'hosting',
                        'hosting_package_id' => $package->id,
                        'billing_cycle' => $cycle,
                        'domain' => $domain !== '' ? $domain : null,
                        'unit_price' => $price,
                        'setup_fee' => 0,
                    ]);
                    $total += $price;
                }
            }

            $order->update(['total' => round($total, 2)]);

            $invoice = $this->invoices->createForOrder($order->fresh('items.hostingPackage', 'user'));

            SafeAuditLogger::info('panelze.billing.order_placed', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'items' => count($items),
                'invoice_id' => $invoice->id,
            ], request());

            return ['order' => $order->fresh('items'), 'invoice' => $invoice];
        });
    }
}
