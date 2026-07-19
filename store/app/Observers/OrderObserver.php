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

        $this->consumeCouponIfNeeded($order);

        app(PanelProvisioningService::class)->dispatchIfNeeded($order);
        app(CloudProvisioningService::class)->dispatchIfNeeded($order);
        app(DomainProvisioningService::class)->dispatchIfNeeded($order);

        $this->generateInvoice($order);
    }

    private function consumeCouponIfNeeded(Order $order): void
    {
        if (! $order->campaign_id) {
            return;
        }

        $meta = is_array($order->payment_data) ? $order->payment_data : [];
        if (! empty($meta['coupon_usage_recorded'])) {
            return;
        }

        $campaign = \App\Models\Campaign::query()->find($order->campaign_id);
        if ($campaign) {
            app(\App\Services\CampaignService::class)->incrementUsage($campaign);
        }

        $order->forceFill([
            'payment_data' => array_merge($meta, ['coupon_usage_recorded' => true]),
        ])->saveQuietly();
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
