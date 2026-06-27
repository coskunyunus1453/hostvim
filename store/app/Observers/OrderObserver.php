<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Cloud\CloudProvisioningService;
use App\Services\Domain\DomainProvisioningService;
use App\Services\Panel\PanelProvisioningService;

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
    }
}
