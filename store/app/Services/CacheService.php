<?php

namespace App\Services;

use App\View\Support\LayoutViewData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public const PAGE_PREFIX = 'page_html:';

    public const PAGE_KEYS_LIST = 'page_cache_keys';

    public const PAGE_PATHS_INDEX = 'page_cache_paths';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function isPageCacheEnabled(): bool
    {
        return $this->boolSetting('cache_page_enabled', true);
    }

    public function isQueryCacheEnabled(): bool
    {
        return $this->boolSetting('cache_query_enabled', true);
    }

    public function isGzipEnabled(): bool
    {
        return $this->boolSetting('cache_gzip_enabled', false);
    }

    public function isBrowserCacheEnabled(): bool
    {
        return $this->boolSetting('cache_browser_enabled', true);
    }

    public function pageTtl(): int
    {
        return max(60, (int) $this->settings->get('cache_page_ttl', 3600));
    }

    public function queryTtl(): int
    {
        return max(60, (int) $this->settings->get('cache_query_ttl', 1800));
    }

    public function browserHtmlTtl(): int
    {
        return max(0, (int) $this->settings->get('cache_browser_html_ttl', 300));
    }

    public function browserAssetsTtl(): int
    {
        return max(3600, (int) $this->settings->get('cache_browser_assets_ttl', 31536000));
    }

    public function pageCacheKey(Request|string $source): string
    {
        $normalized = $this->normalizePageCacheSource($source);

        return self::PAGE_PREFIX.hash('xxh128', $normalized);
    }

    protected function normalizePageCacheSource(Request|string $source): string
    {
        if ($source instanceof Request) {
            $path = trim($source->path(), '/');

            return strtolower($source->getHost())
                .'|'.$path
                .($source->getQueryString() ? '?'.$source->getQueryString() : '');
        }

        $parsed = parse_url($source);
        $host = strtolower((string) ($parsed['host'] ?? ''));
        $path = trim((string) ($parsed['path'] ?? ''), '/');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        return $host.'|'.$path.$query;
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (! $this->isQueryCacheEnabled()) {
            return $callback();
        }

        return Cache::remember($key, $ttl ?? $this->queryTtl(), $callback);
    }

    public function clearLayoutCache(): void
    {
        $this->clearLayoutMenus();
        $this->clearLayoutCategories();
        $this->clearLayoutCampaigns();
        $this->clearLayoutBranding();
        $this->clearHomeCache();
    }

    public function clearLayoutMenus(): void
    {
        Cache::forget('layout:header_menu');
        Cache::forget('layout:footer_menu');
        Cache::forget('layout:footer_bottom_menu');
    }

    public function clearLayoutCategories(): void
    {
        Cache::forget('layout:nav_categories');
    }

    public function clearLayoutCampaigns(): void
    {
        Cache::forget('layout:flash_campaign');
        Cache::forget('layout:popup_campaign');
        \App\Services\CampaignService::clearCache();
    }

    public function clearLayoutBranding(): void
    {
        LayoutViewData::reset();
    }

    public function clearLayoutTheme(): void
    {
        $this->clearLayoutBranding();
        $this->clearLayoutCampaigns();
    }

    public function clearHomeHero(): void
    {
        Cache::forget('home:hero');
    }

    public function clearHomePosts(): void
    {
        Cache::forget('home:posts');
    }

    public function clearHomeCache(): void
    {
        foreach (['home:hero', 'home:categories', 'home:features', 'home:testimonials', 'home:faqs', 'home:posts'] as $key) {
            Cache::forget($key);
        }
    }

    public function clearAll(): array
    {
        $cleared = [];

        $this->clearPageCache();
        $cleared[] = 'Sayfa önbelleği';

        $this->clearLayoutCache();
        $cleared[] = 'Layout sorgu önbelleği';

        Cache::flush();
        $cleared[] = 'Uygulama önbelleği';

        SettingsService::clearCache();
        $cleared[] = 'Site ayarları önbelleği';

        AdminDashboardCache::forgetAll();
        $cleared[] = 'Admin dashboard önbelleği';

        Cache::forget('sitemap_xml');
        $cleared[] = 'Sitemap önbelleği';

        try {
            Artisan::call('view:clear');
            $cleared[] = 'View önbelleği';
        } catch (\Throwable) {
        }

        LayoutViewData::reset();

        return $cleared;
    }

    public function storePageHtml(string $key, string $html, ?int $ttl = null, ?string $path = null): void
    {
        Cache::put($key, $html, $ttl ?? $this->pageTtl());

        $keys = Cache::get(self::PAGE_KEYS_LIST, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put(self::PAGE_KEYS_LIST, $keys, max($this->pageTtl() * 24, 86400));
        }

        if ($path !== null) {
            $paths = Cache::get(self::PAGE_PATHS_INDEX, []);
            $paths[$this->normalizePagePath($path)] = $key;
            Cache::put(self::PAGE_PATHS_INDEX, $paths, max($this->pageTtl() * 24, 86400));
        }
    }

    /**
     * @param  list<string>  $paths  Örn: '', 'blog', 'urun/hosting'
     */
    public function clearPageCacheForPaths(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $index = Cache::get(self::PAGE_PATHS_INDEX, []);
        $keys = Cache::get(self::PAGE_KEYS_LIST, []);
        $changed = false;

        foreach ($paths as $path) {
            $normalized = $this->normalizePagePath($path);
            if (! isset($index[$normalized])) {
                continue;
            }

            $key = $index[$normalized];
            Cache::forget($key);
            unset($index[$normalized]);
            $keys = array_values(array_filter($keys, fn (string $k): bool => $k !== $key));
            $changed = true;
        }

        if ($changed) {
            Cache::put(self::PAGE_PATHS_INDEX, $index, max($this->pageTtl() * 24, 86400));
            Cache::put(self::PAGE_KEYS_LIST, $keys, max($this->pageTtl() * 24, 86400));
        }
    }

    public function clearPageCacheForPrefix(string $prefix): void
    {
        $prefix = $this->normalizePagePath($prefix);
        $index = Cache::get(self::PAGE_PATHS_INDEX, []);
        $paths = array_keys(array_filter(
            $index,
            fn (string $cacheKey, string $path): bool => $prefix === '' ? $path === '' : str_starts_with($path, $prefix),
            ARRAY_FILTER_USE_BOTH,
        ));

        $this->clearPageCacheForPaths($paths);
    }

    protected function normalizePagePath(string $path): string
    {
        return strtolower(trim($path, '/'));
    }

    public function clearPageCache(): void
    {
        foreach (Cache::get(self::PAGE_KEYS_LIST, []) as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::PAGE_KEYS_LIST);
        Cache::forget(self::PAGE_PATHS_INDEX);
    }

    public function warmSettings(): void
    {
        $this->settings->all();
    }

    protected function boolSetting(string $key, bool $default): bool
    {
        $value = $this->settings->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
