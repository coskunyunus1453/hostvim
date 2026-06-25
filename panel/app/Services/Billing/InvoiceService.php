<?php

namespace App\Services\Billing;

use App\Mail\InvoiceCreatedMail;
use App\Mail\InvoicePaidMail;
use App\Mail\ServiceProvisionedMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Subscription;
use App\Services\Provisioning\ProvisioningService;
use App\Services\Domain\DomainRegistrarService;
use App\Services\SafeAuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvoiceService
{
    public function __construct(
        private BillingSettings $settings,
        private ProvisioningService $provisioning,
        private DomainRegistrarService $domainRegistrar,
    ) {}

    /** Bir sipariş için ilk faturayı oluşturur (kurulum ücretleri dahil). */
    public function createForOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order): Invoice {
            $gross = 0.0;
            $lines = [];
            foreach ($order->items as $item) {
                $name = $item->hostingPackage?->name ?? 'Hosting';
                if (($item->item_type ?? 'hosting') === 'domain_register') {
                    $label = 'Alan adı kaydı: '.$item->domain.' ('.$item->domain_years.' yıl)';
                } elseif (($item->item_type ?? '') === 'manual') {
                    $cycle = $item->billing_cycle === 'yearly' ? 'Yıllık' : 'Aylık';
                    $label = ($item->domain ?? 'Manuel hizmet').' ('.$cycle.')';
                } else {
                    $cycle = $item->billing_cycle === 'yearly' ? 'Yıllık' : 'Aylık';
                    $label = $name.' ('.$cycle.')'.($item->domain ? ' — '.$item->domain : '');
                }
                $lines[] = ['description' => $label, 'quantity' => 1, 'unit_price' => (float) $item->unit_price];
                $gross += (float) $item->unit_price;
                if ((float) $item->setup_fee > 0) {
                    $lines[] = ['description' => 'Kurulum ücreti — '.$name, 'quantity' => 1, 'unit_price' => (float) $item->setup_fee];
                    $gross += (float) $item->setup_fee;
                }
            }

            $invoice = $this->createInvoice(
                userId: $order->user_id,
                gross: $gross,
                lines: $lines,
                dueAt: Carbon::now()->addDays((int) $this->settings->get('due_days', 7)),
                orderId: $order->id,
            );

            $this->dispatchMail(fn () => Mail::to($order->user->email)->queue(new InvoiceCreatedMail($invoice->fresh('items'))));

            return $invoice;
        });
    }

    /** Bir hizmet (subscription) için yenileme faturası oluşturur. */
    public function createRenewal(Subscription $subscription): Invoice
    {
        $name = $subscription->hostingPackage?->name ?? 'Hosting';
        $cycle = $subscription->billing_cycle === 'yearly' ? 'Yıllık' : 'Aylık';
        $domain = $subscription->domain?->name;
        $label = 'Yenileme: '.$name.' ('.$cycle.')'.($domain ? ' — '.$domain : '');

        $due = $subscription->next_due_at && $subscription->next_due_at->isFuture()
            ? $subscription->next_due_at
            : Carbon::now()->addDays((int) $this->settings->get('due_days', 7));

        $invoice = $this->createInvoice(
            userId: $subscription->user_id,
            gross: (float) $subscription->amount,
            lines: [['description' => $label, 'quantity' => 1, 'unit_price' => (float) $subscription->amount, 'subscription_id' => $subscription->id]],
            dueAt: $due,
            subscriptionId: $subscription->id,
        );

        $this->dispatchMail(fn () => Mail::to($subscription->user->email)->queue(new InvoiceCreatedMail($invoice->fresh('items'))));

        return $invoice;
    }

    /**
     * Faturayı ödenmiş işaretle ve otomasyonu tetikle:
     * - sipariş faturasıysa hizmetleri kur (provision)
     * - yenileme faturasıysa hizmeti uzat
     */
    public function markPaid(Invoice $invoice, string $method = 'manual', ?string $reference = null): Invoice
    {
        if (! $invoice->isPayable()) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice, $method, $reference): Invoice {
            $invoice->forceFill([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => Carbon::now(),
                'payment_method' => $method,
                'transaction_ref' => $reference,
            ])->save();

            $provisioned = [];

            $order = $invoice->order_id ? Order::query()->with('items.hostingPackage', 'user')->find($invoice->order_id) : null;
            if ($order && $order->status === Order::STATUS_PENDING) {
                foreach ($order->items as $item) {
                    try {
                        if (($item->item_type ?? 'hosting') === 'domain_register') {
                            $this->domainRegistrar->registerFromOrderItem($order->user, $item);
                        } elseif (($item->item_type ?? '') === 'manual') {
                            $cycle = $item->billing_cycle === 'yearly' ? 'Yıllık' : 'Aylık';
                            $this->notifyManualProvisioning($order, $item, $cycle);
                        } else {
                            $sub = $this->provisioning->provisionFromOrderItem($order->user, $item);
                            $provisioned[] = $sub;
                        }
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
                $order->update(['status' => Order::STATUS_ACTIVE]);
                if ($provisioned !== [] && $invoice->subscription_id === null) {
                    $invoice->update(['subscription_id' => $provisioned[0]->id]);
                }
            } elseif ($invoice->subscription_id) {
                $sub = Subscription::query()->with('user', 'hostingPackage', 'domain')->find($invoice->subscription_id);
                if ($sub) {
                    $this->provisioning->renew($sub);
                }
            }

            SafeAuditLogger::info('panelze.billing.invoice_paid', [
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'method' => $method,
                'provisioned' => count($provisioned),
            ], request());

            $this->dispatchMail(function () use ($invoice, $provisioned) {
                Mail::to($invoice->user->email)->queue(new InvoicePaidMail($invoice->fresh('items')));
                foreach ($provisioned as $sub) {
                    Mail::to($invoice->user->email)->queue(new ServiceProvisionedMail($sub->fresh('hostingPackage', 'domain')));
                }
            });

            return $invoice->fresh('items');
        });
    }

    /**
     * Yönetici tarafından elle fatura oluşturma.
     *
     * @param  list<array{description:string, quantity?:int, unit_price:float}>  $lines
     */
    public function createManual(int $userId, array $lines, ?Carbon $dueAt = null, ?string $notes = null, bool $notify = true): Invoice
    {
        $gross = 0.0;
        foreach ($lines as $line) {
            $gross += (float) $line['unit_price'] * (int) ($line['quantity'] ?? 1);
        }

        $invoice = $this->createInvoice(
            userId: $userId,
            gross: $gross,
            lines: $lines,
            dueAt: $dueAt ?? Carbon::now()->addDays((int) $this->settings->get('due_days', 7)),
        );
        if ($notes !== null && $notes !== '') {
            $invoice->update(['notes' => $notes]);
        }

        if ($notify) {
            $invoice->loadMissing('user');
            $this->dispatchMail(fn () => Mail::to($invoice->user->email)->queue(new InvoiceCreatedMail($invoice->fresh('items'))));
        }

        return $invoice->fresh('items');
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_REFUNDED], true)) {
            return $invoice;
        }
        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

        return $invoice;
    }

    /**
     * @param  list<array{description:string, quantity?:int, unit_price:float, subscription_id?:int}>  $lines
     */
    private function createInvoice(int $userId, float $gross, array $lines, Carbon $dueAt, ?int $orderId = null, ?int $subscriptionId = null): Invoice
    {
        $totals = $this->computeTotals($gross, $this->settings->taxRate(), $this->settings->taxInclusive());

        $invoice = Invoice::create([
            'number' => 'TEMP',
            'user_id' => $userId,
            'order_id' => $orderId,
            'subscription_id' => $subscriptionId,
            'status' => Invoice::STATUS_UNPAID,
            'subtotal' => $totals['subtotal'],
            'tax_rate' => $this->settings->taxRate(),
            'tax_amount' => $totals['tax'],
            'total' => $totals['total'],
            'currency' => $this->settings->currency(),
            'due_at' => $dueAt,
        ]);
        $invoice->update(['number' => $this->numberFor('invoice_prefix', $invoice->id)]);

        foreach ($lines as $line) {
            $qty = (int) ($line['quantity'] ?? 1);
            $unit = (float) $line['unit_price'];
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'subscription_id' => $line['subscription_id'] ?? null,
                'description' => $line['description'],
                'quantity' => $qty,
                'unit_price' => $unit,
                'amount' => round($qty * $unit, 2),
            ]);
        }

        return $invoice;
    }

    /** @return array{subtotal: float, tax: float, total: float} */
    public function computeTotals(float $gross, float $rate, bool $inclusive): array
    {
        $gross = round($gross, 2);
        if ($rate <= 0) {
            return ['subtotal' => $gross, 'tax' => 0.0, 'total' => $gross];
        }
        if ($inclusive) {
            $subtotal = round($gross / (1 + $rate / 100), 2);

            return ['subtotal' => $subtotal, 'tax' => round($gross - $subtotal, 2), 'total' => $gross];
        }
        $tax = round($gross * $rate / 100, 2);

        return ['subtotal' => $gross, 'tax' => $tax, 'total' => round($gross + $tax, 2)];
    }

    public function numberFor(string $prefixKey, int $id): string
    {
        $prefix = (string) $this->settings->get($prefixKey, 'INV-');

        return $prefix.date('Y').'-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private function notifyManualProvisioning(Order $order, $item, string $cycle): void
    {
        $to = (string) $this->settings->get('support_email', '');
        if ($to === '') {
            return;
        }

        $subject = 'Manuel kurulum — '.($item->domain ?? 'Manuel hizmet').' ('.$order->number.')';
        $body = implode("\n", [
            'Panel sipariş no: '.$order->number,
            'Müşteri: '.$order->user->name.' <'.$order->user->email.'>',
            'Ürün: '.($item->domain ?? 'Manuel hizmet'),
            'Dönem: '.$cycle,
            'Fiyat: ₺'.number_format((float) $item->unit_price, 2, ',', '.'),
            '',
            'Ödeme alındı. VPS/VDS/dedicated kurulumu için manuel işlem gerekiyor.',
            'Destek talepleri hostvim.com satış sitesi admin panelinden yönetilir.',
        ]);

        $this->dispatchMail(static fn () => Mail::raw($body, static function ($message) use ($to, $subject): void {
            $message->to($to)->subject($subject);
        }));
    }

    private function dispatchMail(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
