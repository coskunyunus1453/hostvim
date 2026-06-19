<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Vadesi yaklaşan aktif hizmetler için yenileme faturası üretir (mükerrer faturayı önler).
 */
class BillingGenerateRenewalsCommand extends Command
{
    protected $signature = 'billing:generate-renewals';

    protected $description = 'Yaklaşan yenilemeler için otomatik fatura oluşturur';

    public function handle(BillingSettings $settings, InvoiceService $invoices): int
    {
        if (! $settings->get('enabled', true)) {
            return self::SUCCESS;
        }

        $daysBefore = (int) $settings->get('renew_generate_days_before', 10);
        $threshold = Carbon::now()->addDays($daysBefore);
        $created = 0;

        Subscription::query()
            ->where('payment_provider', 'manual')
            ->where('auto_renew', true)
            ->where('service_status', '!=', Subscription::SERVICE_TERMINATED)
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', $threshold)
            ->with('user', 'hostingPackage', 'domain')
            ->chunkById(100, function ($subs) use ($invoices, &$created) {
                foreach ($subs as $sub) {
                    if ($this->hasOpenRenewal($sub)) {
                        continue;
                    }
                    try {
                        $invoices->createRenewal($sub);
                        $created++;
                    } catch (Throwable $e) {
                        report($e);
                        $this->error('Yenileme faturası hatası #'.$sub->id.': '.$e->getMessage());
                    }
                }
            });

        $this->info("Oluşturulan yenileme faturası: {$created}");

        return self::SUCCESS;
    }

    private function hasOpenRenewal(Subscription $sub): bool
    {
        return Invoice::query()
            ->where('subscription_id', $sub->id)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_OVERDUE])
            ->exists();
    }
}
