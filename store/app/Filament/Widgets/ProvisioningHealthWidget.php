<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CloudServers\CloudServerResource;
use App\Filament\Resources\DomainNames\DomainNameResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\CloudServer;
use App\Models\DomainName;
use App\Models\Order;
use App\Services\AdminDashboardCache;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProvisioningHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Operasyon & Kurulum Sağlığı';

    protected ?string $description = 'Aksiyon gerektiren siparişler ve başarısız kurulumlar';

    protected function getStats(): array
    {
        return AdminDashboardCache::remember('provisioning_health', function () {
            return $this->buildStats();
        });
    }

    protected function buildStats(): array
    {
        $awaitingTransfer = Order::query()->where('payment_status', 'awaiting_transfer')->count();
        $panelFailed = Order::query()->where('panel_provision_status', 'failed')->count();
        $cloudFailed = Order::query()->where('cloud_provision_status', 'failed')->count();
        $domainFailed = DomainName::query()->where('status', 'failed')->count();
        $serverFailed = CloudServer::query()->where('status', CloudServer::STATUS_FAILED)->count();

        return [
            Stat::make('Havale Bekleyen', Number::format($awaitingTransfer))
                ->description('Onay bekleyen ödeme')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($awaitingTransfer > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Alan Adı Kaydı Başarısız', Number::format($domainFailed))
                ->description($domainFailed > 0 ? 'Spaceship bakiyesini kontrol edin' : 'Sorun yok')
                ->descriptionIcon(Heroicon::OutlinedGlobeAlt)
                ->color($domainFailed > 0 ? 'danger' : 'success')
                ->url(DomainNameResource::getUrl('index')),

            Stat::make('Hosting Kurulumu Başarısız', Number::format($panelFailed))
                ->description($panelFailed > 0 ? 'Panelde yeniden kurun' : 'Sorun yok')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($panelFailed > 0 ? 'danger' : 'success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Bulut Kurulumu Başarısız', Number::format($cloudFailed + $serverFailed))
                ->description($cloudFailed + $serverFailed > 0 ? 'Sunucu kurulumunu kontrol edin' : 'Sorun yok')
                ->descriptionIcon(Heroicon::OutlinedServer)
                ->color($cloudFailed + $serverFailed > 0 ? 'danger' : 'success')
                ->url(CloudServerResource::getUrl('index')),
        ];
    }
}
