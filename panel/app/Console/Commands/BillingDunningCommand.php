<?php

namespace App\Console\Commands;

use App\Mail\InvoiceReminderMail;
use App\Mail\ServiceSuspendedMail;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\BillingSettings;
use App\Services\Provisioning\ProvisioningService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tahsilat takibi (dunning): vade durumunu günceller, hatırlatma gönderir,
 * süresi geçen hizmetleri askıya alır ve gerekirse sonlandırır.
 */
class BillingDunningCommand extends Command
{
    protected $signature = 'billing:dunning';

    protected $description = 'Ödeme hatırlatmaları + gecikmiş hizmet askı/fesih otomasyonu';

    public function handle(BillingSettings $settings, ProvisioningService $provisioning): int
    {
        if (! $settings->get('enabled', true)) {
            return self::SUCCESS;
        }

        $today = Carbon::today();
        $beforeDays = $settings->intList('reminder_days_before');
        $overdueDays = $settings->intList('overdue_reminder_days');
        $suspendAfter = (int) $settings->get('suspend_after_days', 3);
        $terminateAfter = (int) $settings->get('terminate_after_days', 15);
        $autoSuspend = (bool) $settings->get('auto_suspend', true);
        $autoTerminate = (bool) $settings->get('auto_terminate', false);

        $reminders = 0;
        $suspended = 0;
        $terminated = 0;

        Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_OVERDUE])
            ->whereNotNull('due_at')
            ->with('user', 'subscription.domain', 'subscription.hostingPackage', 'subscription.user')
            ->chunkById(100, function ($invoiceList) use (
                $today, $beforeDays, $overdueDays, $suspendAfter, $terminateAfter,
                $autoSuspend, $autoTerminate, $provisioning,
                &$reminders, &$suspended, &$terminated
            ) {
                foreach ($invoiceList as $invoice) {
                    $due = $invoice->due_at->copy()->startOfDay();
                    $diff = (int) floor(($due->getTimestamp() - $today->getTimestamp()) / 86400);

                    if ($diff < 0 && $invoice->status === Invoice::STATUS_UNPAID) {
                        $invoice->update(['status' => Invoice::STATUS_OVERDUE]);
                    }

                    $this->maybeSendReminder($invoice, $diff, $beforeDays, $overdueDays, $reminders);

                    $sub = $invoice->subscription;
                    if ($sub === null || $diff >= 0) {
                        continue;
                    }
                    $overdue = abs($diff);

                    if ($autoTerminate && $overdue >= $terminateAfter && $sub->service_status === Subscription::SERVICE_SUSPENDED) {
                        try {
                            $provisioning->terminate($sub, deleteSite: false);
                            $terminated++;
                        } catch (Throwable $e) {
                            report($e);
                        }
                    } elseif ($autoSuspend && $overdue >= $suspendAfter && $sub->service_status === Subscription::SERVICE_ACTIVE) {
                        try {
                            $provisioning->suspend($sub, reason: 'overdue_invoice');
                            $this->safeMail(fn () => Mail::to($sub->user->email)->queue(new ServiceSuspendedMail($sub->fresh('hostingPackage', 'domain', 'user'))));
                            $suspended++;
                        } catch (Throwable $e) {
                            report($e);
                        }
                    }
                }
            });

        $this->info("Hatırlatma: {$reminders}, askı: {$suspended}, fesih: {$terminated}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, int>  $beforeDays
     * @param  array<int, int>  $overdueDays
     */
    private function maybeSendReminder(Invoice $invoice, int $diff, array $beforeDays, array $overdueDays, int &$reminders): void
    {
        if ($invoice->last_reminder_at && $invoice->last_reminder_at->isToday()) {
            return;
        }

        $sendBefore = $diff > 0 && in_array($diff, $beforeDays, true);
        $sendOverdue = $diff < 0 && in_array(abs($diff), $overdueDays, true);
        if (! $sendBefore && ! $sendOverdue) {
            return;
        }

        $this->safeMail(function () use ($invoice, $sendOverdue) {
            Mail::to($invoice->user->email)->queue(new InvoiceReminderMail($invoice, overdue: $sendOverdue));
        });
        $invoice->forceFill([
            'reminders_sent' => (int) $invoice->reminders_sent + 1,
            'last_reminder_at' => now(),
        ])->save();
        $reminders++;
    }

    private function safeMail(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
