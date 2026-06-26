<?php

namespace Database\Seeders;

use App\Models\DocPage;
use App\Models\Plan;
use App\Support\PanelFeatureCatalog;
use Illuminate\Database\Seeder;

/**
 * Canlıda pazarlama içeriğini panel sürümüne hizalar (mevcut veriyi ezmeden updateOrCreate).
 *   php artisan db:seed --class=SyncPanelMarketingSeeder
 */
class SyncPanelMarketingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SaasBootstrapSeeder::class);

        Plan::query()->updateOrCreate(
            ['slug' => 'freemium'],
            [
                'name' => 'Community (Freemium)',
                'subtitle' => 'Panelze v'.PanelFeatureCatalog::PANEL_VERSION.' — tek sunucuda çekirdek hosting',
                'features' => PanelFeatureCatalog::communityPlanFeatures('tr'),
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'pro-lisans'],
            [
                'name' => 'Pro Lisans',
                'subtitle' => 'Tüm Pro modüller — panelde lisans anahtarı ile açılır',
                'features' => PanelFeatureCatalog::proPlanFeatures('tr'),
            ]
        );

        foreach (['tr' => 'platform-features', 'en' => 'platform-features'] as $locale => $slug) {
            DocPage::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->update(['content' => PanelFeatureCatalog::platformFeaturesMarkdown($locale)]);
        }
    }
}
