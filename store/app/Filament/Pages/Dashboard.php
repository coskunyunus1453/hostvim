<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestBlogPosts;
use App\Filament\Widgets\LatestMessages;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersStatusChart;
use App\Filament\Widgets\OrdersTrendChart;
use App\Filament\Widgets\ProfitOverviewWidget;
use App\Filament\Widgets\ProvisioningHealthWidget;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Kontrol Paneli';

    protected static ?string $title = 'Kontrol Paneli';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ProvisioningHealthWidget::class,
            ProfitOverviewWidget::class,
            RevenueChart::class,
            OrdersStatusChart::class,
            OrdersTrendChart::class,
            LatestOrders::class,
            LatestBlogPosts::class,
            LatestMessages::class,
        ];
    }
}
