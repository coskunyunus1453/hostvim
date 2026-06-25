<?php

namespace App\Console\Commands;

use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Services\CacheService;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishSeoFiles extends Command
{
    protected $signature = 'seo:publish-static';

    protected $description = 'robots.txt ve sitemap.xml dosyalarını public/ altına yazar (nginx uyumu)';

    public function handle(
        RobotsController $robots,
        SitemapController $sitemap,
        SettingsService $settings,
        CacheService $cache,
    ): int {
        $robotsResponse = $robots->index($settings);
        File::put(public_path('robots.txt'), $robotsResponse->getContent());

        try {
            $sitemapResponse = $sitemap->index($settings, $cache);
            File::put(public_path('sitemap.xml'), $sitemapResponse->getContent());
        } catch (\Throwable) {
            if (File::exists(public_path('sitemap.xml'))) {
                File::delete(public_path('sitemap.xml'));
            }
            $this->warn('Sitemap devre dışı — sitemap.xml kaldırıldı.');
        }

        $this->info('SEO dosyaları yayınlandı: public/robots.txt, public/sitemap.xml');

        return self::SUCCESS;
    }
}
