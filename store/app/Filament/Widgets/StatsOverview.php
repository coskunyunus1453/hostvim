<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminDashboardCache;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Genel Bakış';

    protected function getStats(): array
    {
        return AdminDashboardCache::remember('stats_overview', function () {
            return $this->buildStats();
        });
    }

    protected function buildStats(): array
    {
        $paidRevenue = (float) Order::query()->where('payment_status', 'paid')->sum('total');
        $monthRevenue = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        $lastMonthRevenue = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($monthRevenue > 0 ? 100 : 0);

        $customers = Order::query()->distinct()->count('customer_email');
        $pendingOrders = Order::query()->whereIn('status', ['pending', 'processing'])->count();
        $unreadMessages = ContactMessage::query()->where('is_read', false)->count();
        $publishedPosts = BlogPost::query()->where('is_published', true)->count();
        $activeProducts = Product::query()->where('is_active', true)->count();

        $last7DaysRevenue = collect(range(6, 0))->map(fn (int $i) => (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', now()->subDays($i))
            ->sum('total'))->all();

        $last7DaysOrders = collect(range(6, 0))->map(fn (int $i) => Order::query()
            ->whereDate('created_at', now()->subDays($i))
            ->count())->all();

        return [
            Stat::make('Toplam Gelir', $this->money($paidRevenue))
                ->description('Ödenen siparişler')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success')
                ->chart($last7DaysRevenue)
                ->url(OrderResource::getUrl('index')),

            Stat::make('Bu Ay Gelir', $this->money($monthRevenue))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% geçen aya göre" : "{$revenueChange}% geçen aya göre")
                ->descriptionIcon($revenueChange >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
                ->descriptionColor($revenueChange >= 0 ? 'success' : 'danger')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('primary')
                ->chart($last7DaysRevenue),

            Stat::make('Müşteriler', Number::format($customers))
                ->description('Benzersiz sipariş veren')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('info')
                ->chart($last7DaysOrders),

            Stat::make('Aktif Siparişler', Number::format($pendingOrders))
                ->description('Bekleyen / işlenen')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color($pendingOrders > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Blog Yazıları', Number::format($publishedPosts))
                ->description("{$activeProducts} aktif ürün paketi")
                ->descriptionIcon(Heroicon::OutlinedCube)
                ->icon(Heroicon::OutlinedNewspaper)
                ->color('gray')
                ->url(BlogPostResource::getUrl('index')),

            Stat::make('Yeni Mesajlar', Number::format($unreadMessages))
                ->description('Okunmamış iletişim')
                ->descriptionIcon(Heroicon::OutlinedEnvelope)
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color($unreadMessages > 0 ? 'danger' : 'success')
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }

    protected function money(float $amount): string
    {
        return Number::currency($amount, 'TRY', 'tr');
    }
}
