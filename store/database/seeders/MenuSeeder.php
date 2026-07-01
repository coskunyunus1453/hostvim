<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Header, footer (sütunlu) ve footer alt şerit menülerinin kanonik varsayılanı.
 * Idempotent: ilgili konumun mevcut öğelerini temizleyip yeniden oluşturur.
 * Bu sayede menüler tamamen admin panelinden (Menü Yönetimi) düzenlenebilir.
 */
class MenuSeeder extends Seeder
{
    /** @var array<string,int> slug => page_id önbelleği */
    protected array $pageIds = [];

    public function run(): void
    {
        $this->seedMenu('header', 'Üst Menü', $this->headerItems());
        $this->seedMenu('footer', 'Alt Menü', $this->footerColumns());
        $this->seedMenu('footer_bottom', 'Alt Bilgi Şeridi', $this->footerBottomItems());
    }

    /** @param list<array<string,mixed>> $items */
    protected function seedMenu(string $location, string $name, array $items): void
    {
        $menu = Menu::firstOrCreate(['location' => $location], ['name' => $name]);
        $menu->items()->delete();

        foreach (array_values($items) as $order => $item) {
            $this->createItem($menu, $item, null, $order);
        }
    }

    /** @param array<string,mixed> $data */
    protected function createItem(Menu $menu, array $data, ?int $parentId, int $order): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        [$pageId, $url] = $this->resolveTarget($data);

        $created = $menu->items()->create([
            'parent_id' => $parentId,
            'label' => $data['label'],
            'page_id' => $pageId,
            'url' => $url,
            'target' => $data['target'] ?? '_self',
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $order,
            'dropdown_style' => $parentId === null ? ($data['dropdown_style'] ?? null) : null,
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
            'badge' => $data['badge'] ?? null,
            'panel_title' => $parentId === null ? ($data['panel_title'] ?? null) : null,
            'panel_text' => $parentId === null ? ($data['panel_text'] ?? null) : null,
            'panel_cta_label' => $parentId === null ? ($data['panel_cta_label'] ?? null) : null,
            'panel_cta_url' => $parentId === null ? ($data['panel_cta_url'] ?? null) : null,
        ]);

        foreach (array_values($children) as $childOrder => $child) {
            $this->createItem($menu, $child, $created->id, $childOrder);
        }
    }

    /**
     * Sayfa bağlantısı için varsa page_id, yoksa /sayfa/{slug} url'i kullan.
     *
     * @param array<string,mixed> $data
     * @return array{0: ?int, 1: ?string}
     */
    protected function resolveTarget(array $data): array
    {
        if (! empty($data['page'])) {
            $slug = $data['page'];
            $pageId = $this->pageIds[$slug] ??= (int) (Page::where('slug', $slug)->value('id') ?? 0);

            if ($pageId > 0) {
                return [$pageId, null];
            }

            return [null, '/sayfa/'.$slug];
        }

        return [null, $data['url'] ?? null];
    }

    /** @return list<array<string,mixed>> */
    protected function headerItems(): array
    {
        return [
            ['label' => 'Ana Sayfa', 'url' => '/', 'icon' => 'home'],
            [
                'label' => 'Hizmetler',
                'url' => '/urunler',
                'icon' => 'sparkles',
                'dropdown_style' => 'mega_wide',
                'panel_title' => 'Doğru paketi seçin',
                'panel_text' => '7/24 Türkçe destek, %99.9 uptime ve ücretsiz taşıma ile projeniz güvende.',
                'panel_cta_label' => 'Tüm paketleri gör',
                'panel_cta_url' => '/urunler',
                'children' => [
                    ['label' => 'Web Hosting', 'url' => '/web-hosting', 'icon' => 'server', 'description' => 'NVMe SSD, kolay yönetim paneli, ücretsiz SSL'],
                    ['label' => 'Bulut Sunucu (VPS)', 'url' => '/bulut-sunucu', 'icon' => 'cloud', 'description' => 'Tam yetkili, anında ölçeklenebilir kaynaklar'],
                    ['label' => 'VDS Sunucu', 'url' => '/iletisim?konu=vds', 'icon' => 'cpu', 'description' => 'Özel kaynak — teklif alın, anında kurulum'],
                    ['label' => 'Dedicated Sunucu', 'url' => '/iletisim?konu=dedicated', 'icon' => 'shield', 'description' => 'Fiziksel sunucu — size özel yapılandırma'],
                    ['label' => 'Domain / Alan Adı', 'url' => '/domain', 'icon' => 'globe', 'description' => 'Sorgula, kaydet, yönet'],
                ],
            ],
            ['label' => 'Domain', 'url' => '/domain', 'icon' => 'globe'],
            ['label' => 'Blog', 'url' => '/blog', 'icon' => 'document'],
            ['label' => 'Hakkımızda', 'page' => 'hakkimizda', 'icon' => 'sparkles'],
            ['label' => 'İletişim', 'url' => '/iletisim', 'icon' => 'phone'],
        ];
    }

    /** @return list<array<string,mixed>> */
    protected function footerColumns(): array
    {
        return [
            [
                'label' => 'Hizmetler',
                'children' => [
                    ['label' => 'Web Hosting', 'url' => '/web-hosting'],
                    ['label' => 'Bulut Sunucu (VPS)', 'url' => '/bulut-sunucu'],
                    ['label' => 'VDS Sunucu', 'url' => '/iletisim?konu=vds'],
                    ['label' => 'Dedicated Sunucu', 'url' => '/iletisim?konu=dedicated'],
                    ['label' => 'Domain Sorgulama', 'url' => '/domain'],
                    ['label' => 'Domain Değer Sorgulama', 'url' => '/domain-deger-sorgulama'],
                ],
            ],
            [
                'label' => 'Kurumsal',
                'children' => [
                    ['label' => 'Hakkımızda', 'page' => 'hakkimizda'],
                    ['label' => 'Blog', 'url' => '/blog'],
                    ['label' => 'Sıkça Sorulan Sorular', 'page' => 'sss'],
                    ['label' => 'İletişim & Destek', 'url' => '/iletisim'],
                ],
            ],
            [
                'label' => 'Yasal',
                'children' => [
                    ['label' => 'Gizlilik Politikası', 'page' => 'gizlilik'],
                    ['label' => 'KVKK Aydınlatma Metni', 'page' => 'kvkk'],
                    ['label' => 'Çerez Politikası', 'page' => 'cerez-politikasi'],
                    ['label' => 'Kullanım Şartları', 'page' => 'kullanim-sartlari'],
                    ['label' => 'Mesafeli Satış Sözleşmesi', 'page' => 'mesafeli-satis-sozlesmesi'],
                    ['label' => 'İade, İptal ve Cayma', 'page' => 'iade-iptal-ve-cayma-politikasi'],
                ],
            ],
        ];
    }

    /** @return list<array<string,mixed>> */
    protected function footerBottomItems(): array
    {
        return [
            ['label' => 'Gizlilik', 'page' => 'gizlilik'],
            ['label' => 'KVKK', 'page' => 'kvkk'],
            ['label' => 'Çerez', 'page' => 'cerez-politikasi'],
            ['label' => 'Kullanım Şartları', 'page' => 'kullanim-sartlari'],
            ['label' => 'Mesafeli Satış', 'page' => 'mesafeli-satis-sozlesmesi'],
        ];
    }
}
