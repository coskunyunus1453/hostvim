<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Services\AdminNotificationService;
use App\Services\Domain\DomainProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

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

    public function failed(Throwable $e): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        app(AdminNotificationService::class)->notify(
            type: AdminNotification::TYPE_PROVISION_DOMAIN_FAILED,
            title: 'Alan adı kaydı (otomatik) başarısız',
            body: $order->order_number.' · Otomatik domain kaydı tamamlanamadı: '.$e->getMessage(),
            actionUrl: \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $order]),
            notifiable: $order,
            dedupeKey: 'provision_domain_job_failed:'.$order->id,
            icon: 'heroicon-o-globe-alt',
            color: 'danger',
        );
    }
}
