<?php

namespace App\Observers;

use App\Models\SiteSetting;
use App\Services\CacheInvalidator;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;

class SiteSettingObserver
{
    public function saved(SiteSetting $setting): void
    {
        SettingsService::clearCache();
        Cache::forget('sitemap_xml');
        app(CacheInvalidator::class)->forSiteSettingKey($setting->key);

        if (str_starts_with($setting->key, 'seo_') || $setting->key === 'site_name') {
            \Illuminate\Support\Facades\Artisan::call('seo:publish-static');
        }
    }

    public function deleted(SiteSetting $setting): void
    {
        $this->saved($setting);
    }
}
