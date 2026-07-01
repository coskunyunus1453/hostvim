<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\CacheService;
use Illuminate\Database\Seeder;

/**
 * Footer menüsüne Domain Değer Sorgulama linkini ekler (mevcut menüyü silmez).
 */
class HostvimMenuDomainValueSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Menu::where('location', 'footer')->first();
        if (! $menu) {
            return;
        }

        $exists = MenuItem::where('menu_id', $menu->id)
            ->where('url', '/domain-deger-sorgulama')
            ->exists();

        if ($exists) {
            return;
        }

        $hizmetler = MenuItem::where('menu_id', $menu->id)
            ->whereNull('parent_id')
            ->where('label', 'Hizmetler')
            ->first();

        if (! $hizmetler) {
            return;
        }

        $maxOrder = MenuItem::where('parent_id', $hizmetler->id)->max('sort_order') ?? 0;

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $hizmetler->id,
            'label' => 'Domain Değer Sorgulama',
            'url' => '/domain-deger-sorgulama',
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        app(CacheService::class)->clearLayoutMenus();
    }
}
