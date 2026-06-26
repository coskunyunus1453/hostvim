<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Cloud\CloudProvisioningService;
use App\Services\Domain\DomainProvisioningService;
use App\Services\EInvoice\EInvoiceSettings;
use App\Services\Invoice\InvoiceService;
use App\Services\Panel\PanelProvisioningService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('payment_status')) {
            return;
        }

        if ($order->payment_status !== 'paid') {
            return;
        }

        app(PanelProvisioningService::class)->dispatchIfNeeded($order);
        app(CloudProvisioningService::class)->dispatchIfNeeded($order);
        app(DomainProvisioningService::class)->dispatchIfNeeded($order);

        $this->generateInvoice($order);
    }

    private function generateInvoice(Order $order): void
    {
        if (! EInvoiceSettings::autoCreateDraft()) {
            return;
        }

        try {
            $invoice = app(InvoiceService::class)->createForOrder($order);

            if (EInvoiceSettings::isEnabled() && EInvoiceSettings::autoIssue()) {
                app(InvoiceService::class)->issue($invoice);
            }
        } catch (\Throwable $e) {
            Log::error('order.invoice_generation_failed', [
                'order' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
