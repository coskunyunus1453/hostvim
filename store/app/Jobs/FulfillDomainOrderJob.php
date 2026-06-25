<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Domain\DomainProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FulfillDomainOrderJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    public function __construct(
        public int $orderId,
    ) {}

    public function uniqueId(): string
    {
        return 'domain-provision:'.$this->orderId;
    }

    public function handle(DomainProvisioningService $provisioning): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        $provisioning->process($order);
    }
}
