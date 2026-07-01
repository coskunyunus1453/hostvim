<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Campaign;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\HeroSection;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'coskunuygun@hotmail.com'],
            [
                'name' => 'Coşkun Uygun',
                'password' => '14531809',
                'company' => null,
            ]
        )->forceFill(['is_admin' => true])->save();

        User::where('email', 'admin@hostvim.com')->delete();

        $settings = [
            ['group' => 'general', 'key' => 'site_name', 'value' => 'HostVim', 'type' => 'text', 'label' => 'Site Adı'],
            ['group' => 'general', 'key' => 'site_logo_height', 'value' => '40', 'type' => 'number', 'label' => 'Logo Yüksekliği'],
            ['group' => 'general', 'key' => 'site_logo_mobile_height', 'value' => '36', 'type' => 'number', 'label' => 'Mobil Logo Yüksekliği'],
            ['group' => 'general', 'key' => 'site_logo_footer_height', 'value' => '32', 'type' => 'number', 'label' => 'Footer Logo Yüksekliği'],
            ['group' => 'general', 'key' => 'site_logo_show_name', 'value' => '1', 'type' => 'boolean', 'label' => 'Logo Yanında İsim'],
            ['group' => 'general', 'key' => 'footer_text', 'value' => 'Türkiye\'nin güvenilir hosting, VPS, VDS ve sunucu çözüm ortağı.', 'type' => 'textarea', 'label' => 'Footer Metni'],
            ['group' => 'integration', 'key' => 'panel_login_url', 'value' => config('panelze.panel_login_url', ''), 'type' => 'url', 'label' => 'Müşteri Paneli URL'],
            ['group' => 'contact', 'key' => 'contact_phone', 'value' => '+90 (212) 555 00 00', 'type' => 'text', 'label' => 'Telefon'],
            ['group' => 'contact', 'key' => 'contact_email', 'value' => 'destek@hostvim.com', 'type' => 'email', 'label' => 'E-posta'],
            ['group' => 'contact', 'key' => 'contact_address', 'value' => 'İstanbul, Türkiye', 'type' => 'text', 'label' => 'Adres'],
            ['group' => 'contact', 'key' => 'contact_whatsapp', 'value' => '', 'type' => 'text', 'label' => 'WhatsApp'],
            ['group' => 'contact', 'key' => 'contact_hours', 'value' => '7/24 Türkçe destek', 'type' => 'text', 'label' => 'Çalışma saatleri'],
            ['group' => 'contact', 'key' => 'contact_page_title', 'value' => 'Bize Ulaşın', 'type' => 'text', 'label' => 'Sayfa başlığı'],
            ['group' => 'contact', 'key' => 'contact_page_subtitle', 'value' => 'Sorularınız, teklif talepleriniz ve teknik destek için ekibimiz yanınızda.', 'type' => 'text', 'label' => 'Sayfa alt başlığı'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Hosting, VPS, VDS, dedicated sunucu ve domain hizmetleri. Yüksek performans, güvenli altyapı.', 'type' => 'textarea', 'label' => 'Meta Açıklama'],
            ['group' => 'seo', 'key' => 'seo_title_suffix', 'value' => ' | HostVim', 'type' => 'text', 'label' => 'Başlık Soneki'],
            ['group' => 'seo', 'key' => 'seo_default_keywords', 'value' => 'hosting, vps, vds, domain, sunucu kiralama, web hosting', 'type' => 'text', 'label' => 'Anahtar Kelimeler'],
            ['group' => 'seo', 'key' => 'seo_sitemap_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Sitemap Aktif'],
            ['group' => 'seo', 'key' => 'seo_home_title', 'value' => 'HostVim — Hosting, VPS, VDS & Domain', 'type' => 'text', 'label' => 'Ana Sayfa Başlığı'],
            ['group' => 'seo', 'key' => 'seo_home_description', 'value' => 'NVMe SSD hosting, VPS, VDS, dedicated sunucu ve domain. Kurumsal altyapı, 7/24 destek.', 'type' => 'textarea', 'label' => 'Ana Sayfa Açıklaması'],
            ['group' => 'seo', 'key' => 'seo_products_title', 'value' => 'Hosting & Sunucu Paketleri', 'type' => 'text', 'label' => 'Ürünler Başlığı'],
            ['group' => 'seo', 'key' => 'seo_products_description', 'value' => 'Web hosting, VPS, VDS ve dedicated sunucu paketleri. Şeffaf fiyatlandırma.', 'type' => 'textarea', 'label' => 'Ürünler Açıklaması'],
            ['group' => 'seo', 'key' => 'seo_blog_title', 'value' => 'Blog & Rehberler', 'type' => 'text', 'label' => 'Blog Başlığı'],
            ['group' => 'seo', 'key' => 'seo_blog_description', 'value' => 'Hosting, sunucu ve domain rehberleri. Uzman içerikler.', 'type' => 'textarea', 'label' => 'Blog Açıklaması'],
            ['group' => 'seo', 'key' => 'schema_org_name', 'value' => 'HostVim', 'type' => 'text', 'label' => 'Schema Kuruluş Adı'],
            ['group' => 'seo', 'key' => 'schema_org_url', 'value' => config('app.url'), 'type' => 'text', 'label' => 'Schema Kuruluş URL'],
            ['group' => 'design', 'key' => 'primary_color', 'value' => '#C2410C', 'type' => 'color', 'label' => 'Ana Renk'],
            ['group' => 'design', 'key' => 'secondary_color', 'value' => '#166534', 'type' => 'color', 'label' => 'İkincil Renk'],
            ['group' => 'design', 'key' => 'design_theme_mode', 'value' => 'system', 'type' => 'text', 'label' => 'Tema Modu'],
            ['group' => 'design', 'key' => 'design_theme_preset', 'value' => 'hostvim-main', 'type' => 'text', 'label' => 'Tema Paketi'],
            ['group' => 'design', 'key' => 'design_font_family', 'value' => 'Plus Jakarta Sans', 'type' => 'text', 'label' => 'Yazı Tipi'],
            ['group' => 'design', 'key' => 'design_theme_toggle', 'value' => '1', 'type' => 'boolean', 'label' => 'Tema Butonu'],
            ['group' => 'design', 'key' => 'design_header_style', 'value' => 'glass', 'type' => 'text', 'label' => 'Header Stili'],
            ['group' => 'design', 'key' => 'design_header_sticky', 'value' => '1', 'type' => 'boolean', 'label' => 'Yapışkan Header'],
            ['group' => 'design', 'key' => 'design_header_blur', 'value' => '1', 'type' => 'boolean', 'label' => 'Header Blur'],
            ['group' => 'design', 'key' => 'design_header_border', 'value' => '1', 'type' => 'boolean', 'label' => 'Header Border'],
            ['group' => 'design', 'key' => 'design_header_bg_light', 'value' => '#FFFFFF', 'type' => 'color', 'label' => 'Header BG Light'],
            ['group' => 'design', 'key' => 'design_header_bg_dark', 'value' => '#0C0A09', 'type' => 'color', 'label' => 'Header BG Dark'],
            ['group' => 'design', 'key' => 'design_footer_style', 'value' => 'default', 'type' => 'text', 'label' => 'Footer Stili'],
            ['group' => 'design', 'key' => 'design_footer_show_stats', 'value' => '1', 'type' => 'boolean', 'label' => 'Footer Stats'],
            ['group' => 'design', 'key' => 'design_footer_bg_light', 'value' => '#FAFAF9', 'type' => 'color', 'label' => 'Footer BG Light'],
            ['group' => 'design', 'key' => 'design_footer_bg_dark', 'value' => '#1C1917', 'type' => 'color', 'label' => 'Footer BG Dark'],
            ['group' => 'design', 'key' => 'design_light_primary', 'value' => '#C2410C', 'type' => 'color', 'label' => 'Light Primary'],
            ['group' => 'design', 'key' => 'design_light_primary_hover', 'value' => '#9A3412', 'type' => 'color', 'label' => 'Light Primary Hover'],
            ['group' => 'design', 'key' => 'design_light_secondary', 'value' => '#166534', 'type' => 'color', 'label' => 'Light Secondary'],
            ['group' => 'design', 'key' => 'design_light_bg', 'value' => '#FFFFFF', 'type' => 'color', 'label' => 'Light BG'],
            ['group' => 'design', 'key' => 'design_light_surface', 'value' => '#FAFAF9', 'type' => 'color', 'label' => 'Light Surface'],
            ['group' => 'design', 'key' => 'design_light_surface_elevated', 'value' => '#FFFFFF', 'type' => 'color', 'label' => 'Light Elevated'],
            ['group' => 'design', 'key' => 'design_light_text', 'value' => '#1C1917', 'type' => 'color', 'label' => 'Light Text'],
            ['group' => 'design', 'key' => 'design_light_text_muted', 'value' => '#57534E', 'type' => 'color', 'label' => 'Light Muted'],
            ['group' => 'design', 'key' => 'design_light_link', 'value' => '#C2410C', 'type' => 'color', 'label' => 'Light Link'],
            ['group' => 'design', 'key' => 'design_light_border', 'value' => '#E7E5E4', 'type' => 'color', 'label' => 'Light Border'],
            ['group' => 'design', 'key' => 'design_dark_primary', 'value' => '#EA580C', 'type' => 'color', 'label' => 'Dark Primary'],
            ['group' => 'design', 'key' => 'design_dark_primary_hover', 'value' => '#FB923C', 'type' => 'color', 'label' => 'Dark Primary Hover'],
            ['group' => 'design', 'key' => 'design_dark_secondary', 'value' => '#22C55E', 'type' => 'color', 'label' => 'Dark Secondary'],
            ['group' => 'design', 'key' => 'design_dark_bg', 'value' => '#0C0A09', 'type' => 'color', 'label' => 'Dark BG'],
            ['group' => 'design', 'key' => 'design_dark_surface', 'value' => '#1C1917', 'type' => 'color', 'label' => 'Dark Surface'],
            ['group' => 'design', 'key' => 'design_dark_surface_elevated', 'value' => '#292524', 'type' => 'color', 'label' => 'Dark Elevated'],
            ['group' => 'design', 'key' => 'design_dark_text', 'value' => '#FAFAF9', 'type' => 'color', 'label' => 'Dark Text'],
            ['group' => 'design', 'key' => 'design_dark_text_muted', 'value' => '#A8A29E', 'type' => 'color', 'label' => 'Dark Muted'],
            ['group' => 'design', 'key' => 'design_dark_link', 'value' => '#FB923C', 'type' => 'color', 'label' => 'Dark Link'],
            ['group' => 'design', 'key' => 'design_dark_border', 'value' => '#292524', 'type' => 'color', 'label' => 'Dark Border'],
            ['group' => 'cache', 'key' => 'cache_page_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Sayfa Önbelleği'],
            ['group' => 'cache', 'key' => 'cache_page_ttl', 'value' => '3600', 'type' => 'number', 'label' => 'Sayfa TTL (sn)'],
            ['group' => 'cache', 'key' => 'cache_query_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Sorgu Önbelleği'],
            ['group' => 'cache', 'key' => 'cache_query_ttl', 'value' => '1800', 'type' => 'number', 'label' => 'Sorgu TTL (sn)'],
            ['group' => 'cache', 'key' => 'cache_browser_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Tarayıcı Önbelleği'],
            ['group' => 'cache', 'key' => 'cache_browser_html_ttl', 'value' => '300', 'type' => 'number', 'label' => 'HTML Tarayıcı TTL'],
            ['group' => 'cache', 'key' => 'cache_browser_assets_ttl', 'value' => '31536000', 'type' => 'number', 'label' => 'Asset Tarayıcı TTL'],
            ['group' => 'cache', 'key' => 'cache_gzip_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Gzip Sıkıştırma'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        HeroSection::updateOrCreate(['page' => 'home'], [
            'layout_variant' => 'split',
            'title' => 'İşinizi <span class="text-hv-primary">güçlü altyapı</span> ile büyütün',
            'subtitle' => 'Kurumsal Hosting Çözümleri',
            'description' => 'NVMe SSD hosting, yüksek performanslı VPS/VDS, dedicated sunucu ve domain hizmetleri. Kurumsal güvenilirlik, sıcak destek.',
            'cta_text' => 'Paketleri Keşfet',
            'cta_url' => '/urunler',
            'secondary_cta_text' => 'Uzmanla Konuş',
            'secondary_cta_url' => '/iletisim',
            'stat_1_value' => '99.9%',
            'stat_1_label' => 'Uptime Garantisi',
            'stat_2_value' => '7/24',
            'stat_2_label' => 'Teknik Destek',
            'stat_3_value' => '15dk',
            'stat_3_label' => 'Ortalama Yanıt',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $categories = [
            ['name' => 'Web Hosting', 'slug' => 'web-hosting', 'description' => 'WordPress ve kurumsal siteler için hızlı paylaşımlı hosting', 'icon' => 'server', 'color' => '#C2410C', 'sort_order' => 1],
            ['name' => 'VPS Sunucu', 'slug' => 'vps', 'description' => 'Root erişimli, ölçeklenebilir sanal sunucular', 'icon' => 'cpu-chip', 'color' => '#166534', 'sort_order' => 2],
            ['name' => 'VDS Sunucu', 'slug' => 'vds', 'description' => 'Dedicated kaynaklarla güçlü sanal dedicated sunucu', 'icon' => 'cloud', 'color' => '#C2410C', 'sort_order' => 3],
            ['name' => 'Dedicated Sunucu', 'slug' => 'dedicated', 'description' => 'Fiziksel sunucu kiralama, tam kontrol', 'icon' => 'building-office', 'color' => '#166534', 'sort_order' => 4],
            ['name' => 'Domain', 'slug' => 'domain', 'description' => '.com, .com.tr ve yüzlerce domain uzantısı', 'icon' => 'globe-alt', 'color' => '#C2410C', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        $hosting = ProductCategory::where('slug', 'web-hosting')->first();
        $products = [
            ['name' => 'Başlangıç', 'slug' => 'baslangic', 'short_description' => 'Kişisel blog ve küçük siteler için', 'price_monthly' => 49.90, 'price_yearly' => 499.00, 'features' => ['10 GB NVMe SSD', '1 Web Sitesi', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Haftalık Yedek'], 'specs' => ['RAM' => '1 GB', 'CPU' => '1 Core', 'Trafik' => 'Sınırsız'], 'is_popular' => false, 'sort_order' => 1],
            ['name' => 'Profesyonel', 'slug' => 'profesyonel', 'short_description' => 'Büyüyen işletmeler için ideal', 'price_monthly' => 99.90, 'price_yearly' => 999.00, 'features' => ['50 GB NVMe SSD', '5 Web Sitesi', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Günlük Yedek', 'Nginx + OPcache'], 'specs' => ['RAM' => '2 GB', 'CPU' => '2 Core', 'Trafik' => 'Sınırsız'], 'is_popular' => true, 'sort_order' => 2],
            ['name' => 'Kurumsal', 'slug' => 'kurumsal', 'short_description' => 'Yüksek trafikli projeler için', 'price_monthly' => 199.90, 'price_yearly' => 1999.00, 'features' => ['100 GB NVMe SSD', 'Sınırsız Site', 'Panelze Panel', 'PanelKafes İzolasyon', 'Ücretsiz SSL', 'PHP 8.3', 'Saatlik Yedek', 'Öncelikli Destek'], 'specs' => ['RAM' => '4 GB', 'CPU' => '4 Core', 'Trafik' => 'Sınırsız'], 'is_popular' => false, 'sort_order' => 3],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, [
                    'product_category_id' => $hosting->id,
                    'provision_type' => 'hosting',
                    'currency' => 'TRY',
                    'is_active' => true,
                ])
            );
        }

        $vps = ProductCategory::where('slug', 'vps')->first();
        Product::updateOrCreate(['slug' => 'vps-starter'], [
            'product_category_id' => $vps->id,
            'provision_type' => 'cloud',
            'cloud_provider_api' => 'hetzner',
            'cloud_region' => 'fsn1',
            'cloud_plan' => 'cx22',
            'cloud_image' => 'ubuntu-22.04',
            'name' => 'VPS Starter',
            'short_description' => 'Giriş seviye VPS',
            'price_monthly' => 149.90,
            'price_yearly' => 1499.00,
            'features' => ['2 vCPU', '4 GB RAM', '60 GB NVMe', '1 Gbps Port', 'Tam Root', 'Opsiyonel Panelze'],
            'specs' => ['OS' => 'AlmaLinux / Ubuntu', 'Panel' => 'Panelze (Opsiyonel)'],
            'currency' => 'TRY', 'is_active' => true, 'is_popular' => true, 'sort_order' => 1,
        ]);

        PaymentMethod::updateOrCreate(['code' => 'paytr'], [
            'name' => 'Kredi / Banka Kartı (PayTR)',
            'description' => 'Visa, Mastercard ile güvenli ödeme',
            'is_active' => true, 'sort_order' => 1,
            'config' => ['merchant_id' => '', 'merchant_key' => '', 'merchant_salt' => '', 'test_mode' => true],
        ]);
        PaymentMethod::updateOrCreate(['code' => 'iyzico'], [
            'name' => 'iyzico ile Öde',
            'description' => 'iyzico güvenli ödeme altyapısı',
            'is_active' => true, 'sort_order' => 2,
            'config' => ['api_key' => '', 'secret_key' => '', 'test_mode' => true],
        ]);
        PaymentMethod::updateOrCreate(['code' => 'bank_transfer'], [
            'name' => 'Havale / EFT',
            'description' => 'Banka havalesi ile ödeme',
            'instructions' => "Ziraat Bankası\nIBAN: TR00 0000 0000 0000 0000 0000 00\nAlıcı: HostVim Bilişim A.Ş.\n\nAçıklama kısmına sipariş numaranızı yazınız.",
            'is_active' => true, 'sort_order' => 3,
            'config' => [],
        ]);
        PaymentMethod::updateOrCreate(['code' => 'stripe'], [
            'name' => 'Kredi Kartı (Stripe)',
            'description' => 'Visa, Mastercard, Amex — uluslararası güvenli ödeme',
            'is_active' => false, 'sort_order' => 4,
            'config' => ['publishable_key' => '', 'secret_key' => '', 'webhook_secret' => '', 'test_mode' => true],
        ]);
        PaymentMethod::updateOrCreate(['code' => 'paypal'], [
            'name' => 'PayPal',
            'description' => 'PayPal hesabı veya kart ile ödeme',
            'is_active' => false, 'sort_order' => 5,
            'config' => ['client_id' => '', 'client_secret' => '', 'test_mode' => true],
        ]);
        PaymentMethod::updateOrCreate(['code' => 'payoneer'], [
            'name' => 'Payoneer Checkout',
            'description' => 'Payoneer ile global kart ödemesi',
            'is_active' => false, 'sort_order' => 6,
            'config' => ['api_username' => '', 'api_token' => '', 'program_id' => '', 'default_currency' => 'USD', 'test_mode' => true],
        ]);

        $features = [
            ['title' => 'NVMe SSD Altyapı', 'description' => 'Geleneksel disklere göre 10 kata kadar daha hızlı okuma/yazma performansı.', 'sort_order' => 1],
            ['title' => 'Ücretsiz SSL', 'description' => 'Tüm paketlerde Let\'s Encrypt SSL sertifikası otomatik kurulum.', 'sort_order' => 2],
            ['title' => '7/24 Destek', 'description' => 'Gerçek uzmanlar tarafından telefon, ticket ve canlı destek.', 'sort_order' => 3],
            ['title' => 'DDoS Koruması', 'description' => 'Ağ seviyesinde saldırı önleme ile kesintisiz hizmet.', 'sort_order' => 4],
            ['title' => 'Otomatik Yedekleme', 'description' => 'Günlük yedekler ile verileriniz her zaman güvende.', 'sort_order' => 5],
            ['title' => 'Anında Aktivasyon', 'description' => 'Ödeme sonrası otomatik kurulum, dakikalar içinde online.', 'sort_order' => 6],
        ];
        foreach ($features as $f) {
            Feature::updateOrCreate(['title' => $f['title']], array_merge($f, ['is_active' => true]));
        }

        Testimonial::updateOrCreate(['name' => 'Mehmet Yılmaz'], [
            'role' => 'CTO', 'company' => 'TechStart A.Ş.',
            'content' => 'HostVim\'e geçtikten sonra site hızımız belirgin şekilde arttı. Destek ekibi gerçekten işinin ehli.',
            'rating' => 5, 'is_active' => true, 'sort_order' => 1,
        ]);
        Testimonial::updateOrCreate(['name' => 'Ayşe Kaya'], [
            'role' => 'Kurucu', 'company' => 'Dijital Ajans',
            'content' => 'Müşterilerimizin projelerini güvenle barındırıyoruz. Uptime ve performans beklentilerimizi aşıyor.',
            'rating' => 5, 'is_active' => true, 'sort_order' => 2,
        ]);

        Faq::updateOrCreate(['question' => 'Hosting paketimi nasıl yükseltebilirim?'], [
            'answer' => 'Müşteri panelinizden veya destek talebi açarak anında paket yükseltmesi yapabilirsiniz. Verileriniz korunur.',
            'category' => 'Genel', 'is_active' => true, 'sort_order' => 1,
        ]);
        Faq::updateOrCreate(['question' => 'Ücretsiz taşıma hizmeti var mı?'], [
            'answer' => 'Evet, tüm hosting paketlerinde ücretsiz site taşıma hizmeti sunuyoruz.',
            'category' => 'Genel', 'is_active' => true, 'sort_order' => 2,
        ]);

        $blogCat = BlogCategory::updateOrCreate(['slug' => 'rehberler'], ['name' => 'Rehberler', 'sort_order' => 1]);
        $admin = User::where('email', 'coskunuygun@hotmail.com')->first();
        BlogPost::updateOrCreate(['slug' => 'hosting-secerken-dikkat-edilmesi-gerekenler'], [
            'blog_category_id' => $blogCat->id,
            'user_id' => $admin->id,
            'title' => 'Hosting Seçerken Dikkat Edilmesi Gereken 7 Kriter',
            'excerpt' => 'Doğru hosting seçimi projenizin başarısını doğrudan etkiler.',
            'content' => '<p>Performans, güvenlik, destek kalitesi ve fiyat/performans dengesi hosting seçiminde en kritik faktörlerdir.</p><p>NVMe SSD, LiteSpeed veya benzeri cache teknolojileri, günlük yedekleme ve DDoS koruması mutlaka değerlendirilmelidir.</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        EmailTemplate::updateOrCreate(['slug' => 'order-confirmation'], [
            'name' => 'Sipariş Onayı',
            'subject' => 'Siparişiniz alındı — {order_number}',
            'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Sayın {customer_name},</p><p style="margin:0 0 16px;"><strong>{order_number}</strong> numaralı siparişiniz başarıyla alındı.</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;"><tr><td style="padding:16px 20px;"><p style="margin:0;font-size:14px;color:#64748b;">Toplam tutar</p><p style="margin:4px 0 0;font-size:22px;font-weight:800;color:#0f172a;">{total} TL</p></td></tr></table><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;"><tr><td align="center" style="border-radius:10px;background:{primary_color};"><a href="{panel_login_url}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Panele giriş yap</a></td></tr></table>{temporary_password_line}<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Kurulum tamamlandığında ayrıca bilgilendirileceksiniz.</p>',
            'variables' => ['customer_name', 'order_number', 'total', 'panel_login_url', 'temporary_password_line', 'panel_order_number', 'primary_color'],
            'is_active' => true,
        ]);

        EmailTemplate::updateOrCreate(['slug' => 'password-reset'], [
            'name' => 'Şifre Sıfırlama',
            'subject' => '{site_name} — Şifre sıfırlama',
            'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p><p style="margin:0 0 16px;">Hesabınız için şifre sıfırlama talebi aldık. Güvenliğiniz için bağlantı <strong>{expire_minutes} dakika</strong> geçerlidir.</p><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;"><tr><td align="center" style="border-radius:10px;background:{primary_color};"><a href="{reset_url}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Şifremi sıfırla</a></td></tr></table><p style="margin:0 0 8px;font-size:14px;color:#64748b;">Buton çalışmıyorsa bu adresi tarayıcınıza yapıştırın:</p><p style="margin:0;font-size:13px;word-break:break-all;color:#475569;">{reset_url}</p><p style="margin:24px 0 0;font-size:14px;color:#64748b;">Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>',
            'variables' => ['customer_name', 'reset_url', 'expire_minutes', 'site_name', 'primary_color'],
            'is_active' => true,
        ]);

        EmailTemplate::updateOrCreate(['slug' => 'welcome'], [
            'name' => 'Hoş Geldiniz',
            'subject' => '{site_name} — Hesabınız hazır',
            'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Hoş geldiniz, {customer_name}!</p><p style="margin:0 0 16px;">{site_name} ailesine katıldığınız için teşekkürler. Hesabınız başarıyla oluşturuldu.</p><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;"><tr><td align="center" style="border-radius:10px;background:{primary_color};"><a href="{account_url}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Hesabıma git</a></td></tr></table><p style="margin:16px 0 0;font-size:14px;color:#64748b;">Destek: <a href="mailto:{support_email}" style="color:{primary_color};text-decoration:none;">{support_email}</a></p>',
            'variables' => ['customer_name', 'site_name', 'login_url', 'account_url', 'support_email', 'primary_color'],
            'is_active' => true,
        ]);

        EmailTemplate::updateOrCreate(['slug' => 'bank-transfer-pending'], [
            'name' => 'Havale Talimatları',
            'subject' => 'Havale/EFT Talimatları — {order_number}',
            'body' => '<p>Sayın {customer_name},</p><p>{order_number} numaralı siparişiniz için ödeme bekleniyor.</p><p><strong>Tutar:</strong> {total} {currency}</p><p><strong>Açıklama / referans:</strong> {payment_reference}</p><p>{bank_instructions}</p><p>Ödemeniz onaylandığında kurulum otomatik başlayacaktır.</p>',
            'variables' => ['customer_name', 'order_number', 'total', 'currency', 'bank_instructions', 'payment_reference'],
            'is_active' => true,
        ]);

        EmailTemplate::updateOrCreate(['slug' => 'payment-received'], [
            'name' => 'Ödeme Alındı',
            'subject' => 'Ödemeniz alındı — {order_number}',
            'body' => '<p>Sayın {customer_name},</p><p>{order_number} numaralı siparişiniz için {total} TL tutarındaki ödemeniz onaylandı. Kurulum işlemi başlatıldı; tamamlandığında ayrıca bilgilendirileceksiniz.</p>',
            'variables' => ['customer_name', 'order_number', 'total'],
            'is_active' => true,
        ]);

        Campaign::updateOrCreate(['slug' => 'yaz-flash-indirim'], [
            'name' => 'Yaz Flaş İndirimi',
            'title' => 'Yaz Kampanyası — Tüm hosting paketlerinde %20 indirim!',
            'description' => 'Sınırlı süre. Hosting paketlerinde geçerli.',
            'badge_text' => '%20',
            'code' => 'YAZ20',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'applies_to' => 'category',
            'target_ids' => [ProductCategory::where('slug', 'web-hosting')->value('id')],
            'billing_cycles' => ['monthly', 'yearly'],
            'display_modes' => ['flash_bar', 'popup', 'pricing', 'checkout'],
            'requires_code' => false,
            'show_countdown' => true,
            'cta_text' => 'Paketleri İncele',
            'cta_url' => '/urunler',
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if (filter_var((string) env('HOSTVIM_SEED_E2E', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(E2EDemoStoreSeeder::class);
        }

        $this->call(StoreLegalPagesSeeder::class);

        // Menüler yasal/kurumsal sayfalar oluşturulduktan sonra seed edilir
        // (sayfa bağlantıları page_id ile çözülebilsin diye).
        $this->call(MenuSeeder::class);
    }
}
