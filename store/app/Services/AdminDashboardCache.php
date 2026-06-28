<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AdminDashboardCache
{
    public const TTL_SECONDS = 120;

    public static function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('admin_dashboard:'.$key, self::TTL_SECONDS, $callback);
    }

    public static function forgetAll(): void
    {
        foreach ([
            'stats_overview',
            'provisioning_health',
            'profit_overview',
            'revenue_chart',
            'orders_trend',
            'orders_status',
            'latest_orders',
            'latest_posts',
            'latest_messages',
        ] as $key) {
            Cache::forget('admin_dashboard:'.$key);
        }
    }
}
