<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\AdminDashboardCache;
use Filament\Widgets\ChartWidget;

class OrdersTrendChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Sipariş Trendi (14 Gün)';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return AdminDashboardCache::remember('orders_trend', function () {
            $days = collect(range(13, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

            $labels = $days->map(fn ($day) => $day->format('d.m'))->all();

            $orders = $days->map(fn ($day) => Order::query()->whereDate('created_at', $day)->count())->all();

            $paid = $days->map(fn ($day) => Order::query()
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $day)
                ->count())->all();

            return [
                'datasets' => [
                    [
                        'label' => 'Tüm siparişler',
                        'data' => $orders,
                        'backgroundColor' => 'rgba(194, 65, 12, 0.7)',
                    ],
                    [
                        'label' => 'Ödenen',
                        'data' => $paid,
                        'backgroundColor' => 'rgba(22, 101, 52, 0.8)',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }
}
