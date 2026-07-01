<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Canlı ürün özelliklerini Panelze altyapısına göre günceller.
 */
class HostvimProductContentSeeder extends Seeder
{
    public function run(): void
    {
        $hosting = [
            'baslangic' => [
                'features' => ['10 GB NVMe SSD', '1 Web Sitesi', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Haftalık Yedek'],
            ],
            'profesyonel' => [
                'features' => ['50 GB NVMe SSD', '5 Web Sitesi', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Günlük Yedek', 'Nginx + OPcache'],
            ],
            'kurumsal' => [
                'features' => ['100 GB NVMe SSD', 'Sınırsız Site', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Saatlik Yedek', 'Öncelikli Destek'],
            ],
        ];

        foreach ($hosting as $slug => $data) {
            Product::where('slug', $slug)->update($data);
        }

        Product::where('slug', 'vps-starter')->update([
            'features' => ['2 vCPU', '4 GB RAM', '60 GB NVMe', '1 Gbps Port', 'Tam Root', 'Opsiyonel Panelze'],
            'specs' => ['OS' => 'AlmaLinux / Ubuntu', 'Panel' => 'Panelze (Opsiyonel)'],
        ]);

        $cloudSlugs = Product::where('provision_type', 'cloud')->pluck('slug');
        foreach ($cloudSlugs as $slug) {
            if ($slug === 'vps-starter') {
                continue;
            }
            $product = Product::where('slug', $slug)->first();
            if (! $product) {
                continue;
            }
            $specs = $product->specs ?? [];
            if (($specs['Panel'] ?? '') === 'Opsiyonel' || ($specs['Panel'] ?? '') === 'Linux/Windows') {
                $specs['Panel'] = 'Panelze (Opsiyonel)';
                $product->update(['specs' => $specs]);
            }
        }
    }
}
