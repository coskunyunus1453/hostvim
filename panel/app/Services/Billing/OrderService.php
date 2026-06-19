<?php

namespace App\Services\Billing;

use App\Models\HostingPackage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Services\SafeAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private BillingSettings $settings,
        private InvoiceService $invoices,
    ) {}

    /**
     * @param  list<array{package_id:int, billing_cycle:string, domain?:?string}>  $items
     * @return array{order: Order, invoice: Invoice}
     */
    public function place(User $user, array $items, ?int $resellerId = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'En az bir ürün seçin.']);
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
                $package = HostingPackage::query()->where('is_active', true)->findOrFail((int) $row['package_id']);
                $cycle = ($row['billing_cycle'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';
                $price = (float) ($cycle === 'yearly' ? $package->price_yearly : $package->price_monthly);
                $domain = isset($row['domain']) ? strtolower(trim((string) $row['domain'])) : '';

                $order->items()->create([
                    'hosting_package_id' => $package->id,
                    'billing_cycle' => $cycle,
                    'domain' => $domain !== '' ? $domain : null,
                    'unit_price' => $price,
                    'setup_fee' => 0,
                ]);
                $total += $price;
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
