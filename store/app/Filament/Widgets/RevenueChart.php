<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\AdminDashboardCache;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Gelir Grafiği (Son 30 Gün)';

    protected ?string $description = 'Ödenen siparişlerin günlük toplamı';

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        return AdminDashboardCache::remember('revenue_chart', function () {
            $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

            $labels = $days->map(fn ($day) => $day->format('d.m'))->all();

            $revenue = $days->map(fn ($day) => (float) Order::query()
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $day)
                ->sum('total'))->all();

            return [
                'datasets' => [
                    [
                        'label' => 'Gelir (TRY)',
                        'data' => $revenue,
                        'borderColor' => '#C2410C',
                        'backgroundColor' => 'rgba(194, 65, 12, 0.12)',
                        'fill' => true,
                        'tension' => 0.35,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }
}
