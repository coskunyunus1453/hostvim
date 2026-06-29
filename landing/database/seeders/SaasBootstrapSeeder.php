<?php

namespace Database\Seeders;

use App\Models\SaasLicenseProduct;
use App\Models\SaasProductModule;
use App\Support\PanelFeatureCatalog;
use App\Support\SaasModuleDefaults;
use Illuminate\Database\Seeder;

class SaasBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $moduleDefs = PanelFeatureCatalog::proModuleDefs();

        $allKeys = [];
        foreach ($moduleDefs as $m) {
            $allKeys[] = $m['key'];
            $integration = SaasModuleDefaults::integration($m['key']);
            SaasProductModule::query()->updateOrCreate(
                ['key' => $m['key']],
                [
                    'label' => $m['label'],
                    'sort_order' => $m['sort_order'],
                    'is_paid' => true,
                    'is_active' => true,
                    'description' => $m['description'],
                    'ui_paths' => $integration['ui_paths'],
                    'api_route_prefixes' => $integration['api_route_prefixes'],
                ]
            );
        }

        $allOff = array_fill_keys($allKeys, false);
        $allOn = array_fill_keys($allKeys, true);

        SaasLicenseProduct::query()->updateOrCreate(
            ['code' => 'community'],
            [
                'name' => 'Panelze Community',
                'description' => 'Freemium — çekirdek hosting paneli; Pro modüller lisans ile açılır (en fazla 5 site).',
                'default_limits' => ['max_sites' => 5],
                'default_modules' => $allOff,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $proModules = $allOn;

        /*
         * Panelze Pro fiyatlandırması — 2026 (TR yerelleştirilmiş + global USD/EUR).
         * Sunucu başına lisans; tüm Pro modüller dahil, 500 siteye kadar.
         * minor = kuruş/cent (× 100). TR fiyatları PPP gereği global USD'den ucuzdur.
         */
        $proPricing = [
            'pro-monthly' => [
                'name' => 'Panelze Pro — Aylık',
                'try' => 49_900,   // ₺499,00 / ay
                'usd' => 1_499,    // $14.99 / mo
                'eur' => 1_399,    // €13.99 / mo
                'interval' => 'month',
                'sort_order' => 11,
            ],
            'pro-yearly' => [
                'name' => 'Panelze Pro — Yıllık',
                'try' => 499_000,  // ₺4.990,00 / yıl (~2 ay bedava)
                'usd' => 14_900,   // $149 / yr
                'eur' => 13_900,   // €139 / yr
                'interval' => 'year',
                'sort_order' => 12,
            ],
            'pro-lifetime' => [
                'name' => 'Panelze Pro — Ömür Boyu',
                'try' => 1_199_000, // ₺11.990,00 tek seferlik
                'usd' => 34_900,    // $349 one-time
                'eur' => 32_900,    // €329 one-time
                'interval' => null,
                'sort_order' => 13,
            ],
        ];

        foreach ($proPricing as $code => $p) {
            SaasLicenseProduct::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $p['name'],
                    'description' => 'Panelze v'.PanelFeatureCatalog::PANEL_VERSION.' — tüm Pro modüller dahil, sunucu başına 500 siteye kadar.',
                    'default_limits' => ['max_sites' => 500],
                    'default_modules' => $proModules,
                    'is_active' => true,
                    'sort_order' => $p['sort_order'],
                    'price_try_minor' => $p['try'],
                    'price_usd_minor' => $p['usd'],
                    'price_eur_minor' => $p['eur'],
                    'billing_interval' => $p['interval'],
                ]
            );
        }

        // Eski tek tip "pro" ürünü artık vitrinde gösterilmesin (aylık/yıllık/ömür boyu ile değiştirildi).
        SaasLicenseProduct::query()->where('code', 'pro')->update(['is_active' => false]);
    }
}
