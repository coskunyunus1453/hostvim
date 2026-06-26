<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Panel\PanelProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FulfillPanelOrderJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public int $orderId,
    ) {}

    public function uniqueId(): string
    {
        return 'panel-provision:'.$this->orderId;
    }

    public function handle(PanelProvisioningService $provisioning): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        $provisioning->process($order);
    }
}
