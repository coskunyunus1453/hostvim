<?php

namespace Database\Seeders;

use App\Models\NavMenuItem;
use Illuminate\Database\Seeder;

/**
 * İlk kurulumda menüyü doldurur; her çalıştırmada /demo satırını garanti eder.
 */
class NavMenuSeeder extends Seeder
{
    public function run(): void
    {
        if (! NavMenuItem::query()->exists()) {
            $this->seedDefaults();
        }

        $this->ensureDemoNavItem();
    }

    private function seedDefaults(): void
    {
        $header = [
            ['label' => 'Özellikler', 'label_en' => 'Features', 'href' => '/#features'],
            ['label' => 'Demo', 'label_en' => 'Demo', 'href' => '/demo'],
            ['label' => 'Fiyatlandırma', 'label_en' => 'Pricing', 'href' => '/pricing'],
            ['label' => 'Kurulum', 'label_en' => 'Installation', 'href' => '/setup'],
            ['label' => 'Dokümantasyon', 'label_en' => 'Documentation', 'href' => '/docs'],
            ['label' => 'Blog', 'label_en' => 'Blog', 'href' => '/blog'],
            ['label' => 'SSS', 'label_en' => 'FAQ', 'href' => '/p/sss'],
        ];

        foreach ($header as $i => $row) {
            NavMenuItem::query()->create([
                'zone' => NavMenuItem::ZONE_HEADER,
                'label' => $row['label'],
                'label_en' => $row['label_en'],
                'href' => $row['href'],
                'sort_order' => $i,
                'is_active' => true,
                'open_in_new_tab' => false,
            ]);
        }

        $footer = [
            ['label' => 'Dokümantasyon', 'label_en' => 'Documentation', 'href' => '/docs'],
            ['label' => 'Blog', 'label_en' => 'Blog', 'href' => '/blog'],
            ['label' => 'SSS', 'label_en' => 'FAQ', 'href' => '/p/sss'],
            ['label' => 'Demo', 'label_en' => 'Demo', 'href' => '/demo'],
        ];

        foreach ($footer as $i => $row) {
            NavMenuItem::query()->create([
                'zone' => NavMenuItem::ZONE_FOOTER,
                'label' => $row['label'],
                'label_en' => $row['label_en'],
                'href' => $row['href'],
                'sort_order' => $i,
                'is_active' => true,
                'open_in_new_tab' => false,
            ]);
        }
    }

    private function ensureDemoNavItem(): void
    {
        foreach ([NavMenuItem::ZONE_HEADER, NavMenuItem::ZONE_FOOTER] as $zone) {
            $existing = NavMenuItem::query()
                ->where('zone', $zone)
                ->where('href', '/demo')
                ->first();

            if ($existing) {
                $existing->update([
                    'label' => 'Demo',
                    'label_en' => 'Demo',
                    'is_active' => true,
                ]);

                continue;
            }

            $featuresSort = NavMenuItem::query()
                ->where('zone', $zone)
                ->where('href', '/#features')
                ->value('sort_order');

            NavMenuItem::query()->create([
                'zone' => $zone,
                'label' => 'Demo',
                'label_en' => 'Demo',
                'href' => '/demo',
                'sort_order' => $featuresSort !== null ? ((int) $featuresSort + 1) : 1,
                'is_active' => true,
                'open_in_new_tab' => false,
            ]);
        }
    }
}
