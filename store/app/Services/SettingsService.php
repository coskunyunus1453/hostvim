<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /** @var array<string, mixed>|null */
    protected static ?array $memory = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function all(): array
    {
        if (self::$memory !== null) {
            return self::$memory;
        }

        self::$memory = Cache::remember('site_settings', 3600, function () {
            return SiteSetting::query()
                ->pluck('value', 'key')
                ->toArray();
        });

        return self::$memory;
    }

    public function group(string $group): array
    {
        return Cache::remember("site_settings.{$group}", 3600, function () use ($group) {
            return SiteSetting::query()
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        self::$memory = null;
        Cache::forget('site_settings');
        foreach (['general', 'design', 'contact', 'seo', 'social', 'mail', 'cache'] as $group) {
            Cache::forget("site_settings.{$group}");
        }
    }
}
