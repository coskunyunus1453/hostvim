<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\AdminDashboardCache;
use Filament\Widgets\ChartWidget;

class OrdersStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Sipariş Durumları';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        return AdminDashboardCache::remember('orders_status', function () {
            $statuses = [
                'pending' => 'Beklemede',
                'processing' => 'İşleniyor',
                'completed' => 'Tamamlandı',
                'cancelled' => 'İptal',
            ];

            $counts = [];
            foreach (array_keys($statuses) as $status) {
                $counts[] = Order::query()->where('status', $status)->count();
            }

            return [
                'datasets' => [
                    [
                        'data' => $counts,
                        'backgroundColor' => ['#F59E0B', '#3B82F6', '#166534', '#EF4444'],
                    ],
                ],
                'labels' => array_values($statuses),
            ];
        });
    }
}
