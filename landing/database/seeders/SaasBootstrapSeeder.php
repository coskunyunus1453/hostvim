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
        foreach (['pro', 'pro-monthly', 'pro-yearly', 'pro-lifetime'] as $i => $code) {
            SaasLicenseProduct::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => match ($code) {
                        'pro-monthly' => 'Panelze Pro (Aylık)',
                        'pro-yearly' => 'Panelze Pro (Yıllık)',
                        'pro-lifetime' => 'Panelze Pro (Sınırsız)',
                        default => 'Panelze Pro',
                    },
                    'description' => 'Panelze v'.PanelFeatureCatalog::PANEL_VERSION.' — tüm Pro modüller dahil',
                    'default_limits' => ['max_sites' => 500],
                    'default_modules' => $proModules,
                    'is_active' => true,
                    'sort_order' => 10 + $i,
                    'price_try_minor' => $code === 'pro-yearly' ? 1_999_000 : ($code === 'pro-lifetime' ? 4_999_000 : 199_900),
                    'price_usd_minor' => $code === 'pro-yearly' ? 199_000 : ($code === 'pro-lifetime' ? 499_000 : 19_900),
                    'price_eur_minor' => $code === 'pro-yearly' ? 185_000 : ($code === 'pro-lifetime' ? 459_000 : 18_500),
                    'billing_interval' => match ($code) {
                        'pro-monthly' => 'month',
                        'pro-yearly' => 'year',
                        default => null,
                    },
                ]
            );
        }
    }
}
