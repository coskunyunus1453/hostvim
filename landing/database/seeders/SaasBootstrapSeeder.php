<?php

namespace Database\Seeders;

use App\Models\SaasLicenseProduct;
use App\Models\SaasProductModule;
use App\Support\SaasModuleDefaults;
use Illuminate\Database\Seeder;

class SaasBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $moduleDefs = [
            ['key' => 'vendor_panel', 'label' => 'Vendor kontrol düzlemi', 'sort_order' => 10],
            ['key' => 'backups_pro', 'label' => 'Gelişmiş yedekleme (Drive / uzak)', 'sort_order' => 20],
            ['key' => 'monitoring_advanced', 'label' => 'Gelişmiş izleme', 'sort_order' => 30],
            ['key' => 'ai_advisor', 'label' => 'PanelZeka / AI', 'sort_order' => 40],
            ['key' => 'curious_tools', 'label' => 'Meraklısına', 'sort_order' => 45],
            ['key' => 'stripe_billing', 'label' => 'Stripe faturalama', 'sort_order' => 50],
            ['key' => 'phpmyadmin_sso', 'label' => 'phpMyAdmin tek tık giriş', 'sort_order' => 55],
        ];

        $allKeys = [];
        foreach ($moduleDefs as $m) {
            $allKeys[] = $m['key'];
            $integration = SaasModuleDefaults::integration($m['key']);
            SaasProductModule::query()->updateOrCreate(
                ['key' => $m['key']],
                array_merge($m, [
                    'is_paid' => true,
                    'is_active' => true,
                    'description' => null,
                    'ui_paths' => $integration['ui_paths'],
                    'api_route_prefixes' => $integration['api_route_prefixes'],
                ])
            );
        }

        $allOff = array_fill_keys($allKeys, false);
        $allOn = array_fill_keys($allKeys, true);

        SaasLicenseProduct::query()->updateOrCreate(
            ['code' => 'community'],
            [
                'name' => 'Hostvim Community',
                'description' => 'Freemium — Pro modüller görünür, lisans ile açılır',
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
                        'pro-monthly' => 'Hostvim Pro (Aylık)',
                        'pro-yearly' => 'Hostvim Pro (Yıllık)',
                        'pro-lifetime' => 'Hostvim Pro (Sınırsız)',
                        default => 'Hostvim Pro',
                    },
                    'description' => 'Tüm Pro modüller',
                    'default_limits' => ['max_sites' => 500],
                    'default_modules' => $proModules,
                    'is_active' => true,
                    'sort_order' => 10 + $i,
                    'price_try_minor' => $code === 'pro-yearly' ? 1_999_000 : ($code === 'pro-lifetime' ? 4_999_000 : 199_900),
                    'price_usd_minor' => $code === 'pro-yearly' ? 199_000 : ($code === 'pro-lifetime' ? 499_000 : 19_900),
                    'price_eur_minor' => $code === 'pro-yearly' ? 185_000 : ($code === 'pro-lifetime' ? 459_000 : 18_500),
                ]
            );
        }
    }
}
