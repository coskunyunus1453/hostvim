<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AccountingReportsPage;
use App\Services\AccountingReportService;
use App\Services\AdminDashboardCache;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Kârlılık (bu ay)';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return AdminDashboardCache::remember('profit_overview', function () {
            $reports = app(AccountingReportService::class);
            $summary = $reports->summary(now()->startOfMonth(), now()->endOfDay());
            $trend = collect($reports->dailyTrend(14))->pluck('profit')->all();

            $netColor = $summary['net_profit'] >= 0 ? 'success' : 'danger';

            return [
                Stat::make('Brüt kâr', AccountingReportsPage::money((float) $summary['gross_profit']))
                    ->description('Marj: '.AccountingReportsPage::percent($summary['gross_margin']))
                    ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                    ->color('primary')
                    ->chart($trend)
                    ->url(AccountingReportsPage::getUrl()),

                Stat::make('Net kâr', AccountingReportsPage::money((float) $summary['net_profit']))
                    ->description('Giderler düşülmüş')
                    ->descriptionIcon(Heroicon::OutlinedBanknotes)
                    ->color($netColor)
                    ->url(AccountingReportsPage::getUrl()),

                Stat::make('Maliyet (COGS)', AccountingReportsPage::money((float) $summary['cogs']))
                    ->description($summary['unknown_cost_lines'].' satırda maliyet eksik')
                    ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                    ->descriptionColor($summary['unknown_cost_lines'] > 0 ? 'warning' : 'gray')
                    ->color('warning'),
            ];
        });
    }
}
