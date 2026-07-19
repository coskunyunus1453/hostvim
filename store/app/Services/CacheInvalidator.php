<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Campaign;
use App\Models\HeroSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * İçerik/ayar değişikliklerinde yalnızca ilgili önbellek segmentlerini temizler.
 */
class CacheInvalidator
{
    public function __construct(
        protected CacheService $cache,
    ) {}

    public function forSiteSettingKey(string $key): void
    {
        if (str_starts_with($key, 'cache_') || str_starts_with($key, 'outbound_mail.')) {
            return;
        }

        if (str_starts_with($key, 'seo_')) {
            Cache::forget('sitemap_xml');
            $this->cache->clearPageCacheForPaths(['', 'blog']);

            return;
        }

        if (str_starts_with($key, 'design_') || str_starts_with($key, 'theme_')) {
            $this->cache->clearLayoutTheme();
            $this->cache->clearPageCache();

            return;
        }

        if (
            str_starts_with($key, 'footer_')
            || str_starts_with($key, 'contact_')
            || str_starts_with($key, 'social_')
            || in_array($key, ['site_name', 'panel_login_url'], true)
        ) {
            $this->cache->clearLayoutBranding();
            $this->cache->clearPageCache();

            return;
        }

        // Diğer genel ayarlar: layout sorguları, sayfa HTML tamamı değil
        $this->cache->clearLayoutBranding();
    }

    public function forContentModel(Model $model): void
    {
        Cache::forget('sitemap_xml');

        if ($model instanceof Menu || $model instanceof MenuItem) {
            $this->cache->clearLayoutMenus();
            $this->cache->clearPageCache();

            return;
        }

        if ($model instanceof ProductCategory) {
            $this->cache->clearLayoutCategories();
            $this->cache->clearHomeCache();
            $paths = ['', 'urunler'];
            if ($model->slug) {
                $paths[] = 'urunler/'.$model->slug;
            }
            $this->cache->clearPageCacheForPaths($paths);

            return;
        }

        if ($model instanceof Product) {
            $this->cache->clearLayoutCategories();
            $paths = ['', 'urunler'];
            $categorySlug = null;
            if ($model->relationLoaded('category') && $model->category?->slug) {
                $categorySlug = $model->category->slug;
            } elseif ($model->product_category_id) {
                $categorySlug = ProductCategory::query()->whereKey($model->product_category_id)->value('slug');
            }
            if ($categorySlug && $model->slug) {
                $paths[] = 'urunler/'.$categorySlug.'/'.$model->slug;
                $paths[] = 'urunler/'.$categorySlug;
            }
            $this->cache->clearHomeCache();
            $this->cache->clearPageCacheForPaths(array_values(array_unique($paths)));

            return;
        }

        if ($model instanceof Page) {
            if ($model->slug) {
                $this->cache->clearPageCacheForPaths(['sayfa/'.$model->slug]);
            }

            return;
        }

        if ($model instanceof BlogPost) {
            $paths = ['', 'blog'];
            if ($model->slug) {
                $paths[] = 'blog/'.$model->slug;
            }
            $this->cache->clearHomePosts();
            $this->cache->clearPageCacheForPaths($paths);

            return;
        }

        if ($model instanceof HeroSection) {
            $this->cache->clearHomeHero();
            $this->cache->clearPageCacheForPaths(['']);

            return;
        }

        if ($model instanceof Campaign) {
            $this->cache->clearLayoutCampaigns();

            return;
        }

        // Bilinmeyen modeller: yalnızca ana sayfa fragmentleri
        $this->cache->clearHomeCache();
    }

    public function forMenusSaved(): void
    {
        $this->cache->clearLayoutMenus();
        $this->cache->clearPageCache();
    }

    public function forDesignSaved(): void
    {
        $this->cache->clearLayoutTheme();
        $this->cache->clearPageCache();
    }

    public function forGeneralBrandingSaved(): void
    {
        $this->cache->clearLayoutBranding();
        $this->cache->clearPageCache();
    }

    public function forSeoSettingsSaved(): void
    {
        Cache::forget('sitemap_xml');
        $this->cache->clearPageCacheForPaths(['', 'blog']);
    }

    public function forCampaignSaved(): void
    {
        $this->cache->clearLayoutCampaigns();
    }

    public function forThemePresetApplied(): void
    {
        $this->cache->clearLayoutTheme();
        $this->cache->clearPageCache();
    }
}
