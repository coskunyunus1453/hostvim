<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class StoreLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $site = config('app.name', 'HostVim');
        $year = date('Y');

        $pages = [
            [
                'slug' => 'hakkimizda',
                'title' => 'Hakkımızda',
                'meta_description' => "{$site} — güvenilir hosting, VPS, VDS ve domain hizmetleri.",
                'sort_order' => 1,
                'content' => <<<HTML
<h2>{$site} Kimdir?</h2>
<p>{$site}, işletmeler ve bireyler için yüksek performanslı web hosting, VPS, VDS, dedicated sunucu ve domain hizmetleri sunan bir teknoloji firmasıdır. NVMe SSD altyapısı, güvenli veri merkezi ortamı ve 7/24 teknik destek ile projelerinizi kesintisiz büyütmenize yardımcı oluruz.</p>
<h3>Misyonumuz</h3>
<p>Her ölçekteki müşteriye şeffaf fiyatlandırma, hızlı kurulum ve uzman destek sunmak.</p>
<h3>Değerlerimiz</h3>
<ul>
<li>Güvenilir altyapı ve yedekleme</li>
<li>Şeffaf fiyatlandırma, gizli maliyet yok</li>
<li>Türkçe 7/24 destek</li>
<li>KVKK ve veri güvenliğine uyum</li>
</ul>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'sss',
                'title' => 'Sık Sorulan Sorular',
                'meta_description' => 'Hosting, sunucu ve domain hizmetleri hakkında sık sorulan sorular.',
                'sort_order' => 2,
                'content' => <<<HTML
<h2>Genel</h2>
<h3>Hosting paketimi nasıl yükseltebilirim?</h3>
<p>Müşteri panelinizden veya destek talebi açarak anında paket yükseltmesi yapabilirsiniz. Verileriniz korunur.</p>
<h3>Ücretsiz site taşıma var mı?</h3>
<p>Evet, uygun hosting paketlerinde ücretsiz site taşıma hizmeti sunuyoruz.</p>
<h3>Ödeme yöntemleri nelerdir?</h3>
<p>Kredi kartı, banka havalesi ve desteklenen online ödeme yöntemleri ile ödeme yapabilirsiniz.</p>
<h3>Destek saatleri nedir?</h3>
<p>Teknik destek ekibimiz 7/24 ulaşılabilir durumdadır.</p>
HTML,
            ],
            [
                'slug' => 'gizlilik',
                'title' => 'Gizlilik Politikası',
                'meta_description' => 'Kişisel verilerinizin korunması ve gizlilik uygulamalarımız.',
                'sort_order' => 3,
                'content' => <<<HTML
<h2>1. Veri Sorumlusu</h2>
<p>{$site} olarak kişisel verileriniz 6698 sayılı KVKK kapsamında işlenmektedir.</p>
<h2>2. Toplanan Veriler</h2>
<p>Ad, soyad, e-posta, telefon, fatura adresi, IP adresi ve hizmet kullanım kayıtları sipariş ve destek süreçleri için toplanabilir.</p>
<h2>3. İşleme Amaçları</h2>
<ul>
<li>Hizmet sunumu ve sözleşme yükümlülükleri</li>
<li>Fatura ve ödeme işlemleri</li>
<li>Teknik destek ve güvenlik</li>
<li>Yasal yükümlülükler</li>
</ul>
<h2>4. Saklama ve Güvenlik</h2>
<p>Verileriniz güvenli sunucularda saklanır; yetkisiz erişime karşı teknik ve idari önlemler alınır.</p>
<h2>5. Haklarınız</h2>
<p>KVKK kapsamındaki haklarınız için <a href="/iletisim">iletişim</a> sayfamızdan bize ulaşabilirsiniz.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'kvkk',
                'title' => 'KVKK Aydınlatma Metni',
                'meta_description' => '6698 sayılı KVKK kapsamında kişisel verilerin işlenmesine ilişkin aydınlatma.',
                'sort_order' => 4,
                'content' => <<<HTML
<h2>Aydınlatma Metni</h2>
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca, {$site} tarafından veri sorumlusu sıfatıyla kişisel verileriniz aşağıda açıklanan çerçevede işlenmektedir.</p>
<h3>İşlenen Veri Kategorileri</h3>
<p>Kimlik, iletişim, müşteri işlem, finans, işlem güvenliği ve hizmet kullanım verileri.</p>
<h3>Aktarım</h3>
<p>Yalnızca hizmetin ifası, yasal zorunluluklar veya açık rızanız kapsamında üçüncü taraflara aktarım yapılabilir (ödeme kuruluşları, altyapı sağlayıcıları).</p>
<h3>Başvuru</h3>
<p>KVKK m.11 kapsamındaki taleplerinizi yazılı veya kayıtlı elektronik posta ile iletebilirsiniz.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'kullanim-sartlari',
                'title' => 'Kullanım Şartları',
                'meta_description' => 'Web sitesi ve hosting hizmetlerinin kullanım koşulları.',
                'sort_order' => 5,
                'content' => <<<HTML
<h2>1. Kapsam</h2>
<p>Bu şartlar {$site} web sitesi ve sunulan hosting, sunucu ve domain hizmetlerinin kullanımını düzenler.</p>
<h2>2. Hizmet Kullanımı</h2>
<p>Hizmetler yalnızca yasal amaçlarla kullanılabilir. Spam, zararlı yazılım barındırma ve kaynak kötüye kullanımı yasaktır.</p>
<h2>2. Ödeme ve Faturalandırma</h2>
<p>Hizmet bedelleri seçilen dönem için peşin veya sözleşmede belirtilen şekilde tahsil edilir.</p>
<h2>3. Sorumluluk Sınırı</h2>
<p>Mücbir sebep halleri ve üçüncü taraf ağ kesintileri dışında makul teknik önlemler alınır; detaylar SLA metninde yer alır.</p>
<h2>4. Fesih</h2>
<p>Taraflar sözleşme koşullarına uygun şekilde hizmeti sonlandırabilir.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'mesafeli-satis-sozlesmesi',
                'title' => 'Mesafeli Satış Sözleşmesi',
                'meta_description' => 'Mesafeli satış sözleşmesi — taraflar, hizmet konusu, ödeme ve teslim koşulları.',
                'sort_order' => 6,
                'content' => <<<HTML
<h2>1. Taraflar</h2>
<p><strong>SATICI:</strong> [[firma_unvan]]<br>Adres: [[firma_adres]]<br>Vergi Dairesi/No: [[firma_vergi_dairesi]] / [[firma_vergi_no]]<br>Telefon: [[firma_telefon]] — E-posta: [[firma_eposta]]</p>
<p><strong>ALICI:</strong> Sipariş sırasında belirtilen ad, adres ve iletişim bilgilerine sahip müşteri.</p>
<h2>2. Sözleşmenin Konusu</h2>
<p>İşbu sözleşme, ALICI'nın {$site} web sitesi üzerinden elektronik ortamda sipariş verdiği, aşağıda nitelikleri ve satış fiyatı belirtilen dijital hizmetlerin (hosting, sunucu, domain vb.) satışı ve ifası ile ilgili 6502 sayılı Tüketicinin Korunması Hakkında Kanun hükümleri uyarınca tarafların hak ve yükümlülüklerini düzenler.</p>
<h2>3. Hizmet ve Ödeme</h2>
<p>Hizmetin türü, süresi ve toplam bedeli (KDV dahil) sipariş özeti ve faturada belirtilir. Ödeme, seçilen ödeme yöntemi ile peşin tahsil edilir.</p>
<h2>4. İfa ve Teslim</h2>
<p>Dijital hizmetler, ödeme onayının ardından makul süre içinde elektronik ortamda aktive edilerek ifa edilir.</p>
<h2>5. Cayma Hakkı</h2>
<p>Dijital içerik ve hizmetlerde, ifaya ALICI'nın onayı ile başlanması halinde cayma hakkı mevzuat çerçevesinde sınırlanabilir. Detaylar için <a href="/sayfa/iade-iptal-ve-cayma-politikasi">İade, İptal ve Cayma Politikası</a> sayfasına bakınız.</p>
<h2>6. Uyuşmazlık</h2>
<p>Uyuşmazlıklarda Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'iade-iptal-ve-cayma-politikasi',
                'title' => 'İade, İptal ve Cayma Politikası',
                'meta_description' => 'Hizmet iadesi, sipariş iptali ve cayma hakkına ilişkin koşullar.',
                'sort_order' => 7,
                'content' => <<<HTML
<h2>1. Genel</h2>
<p>{$site} tarafından sunulan hizmetlere ilişkin iade, iptal ve cayma koşulları aşağıda düzenlenmiştir.</p>
<h2>2. Cayma Hakkı</h2>
<p>Mevzuat gereği, elektronik ortamda anında ifa edilen dijital hizmetlerde ve ALICI'nın onayıyla ifasına başlanan hizmetlerde cayma hakkı kullanılamayabilir. Henüz ifasına başlanmamış hizmetlerde ALICI cayma hakkını kullanabilir.</p>
<h2>3. İptal ve İade</h2>
<ul>
<li>Domain tescili gibi üçüncü taraf kurumlara devredilen ve geri alınamayan işlemlerde iade yapılamaz.</li>
<li>Hosting/sunucu hizmetlerinde, kullanılmaya başlanmamış dönemler için talep değerlendirilir.</li>
<li>Onaylı iadeler, ödeme yapılan yöntem üzerinden makul süre içinde gerçekleştirilir.</li>
</ul>
<h2>4. Başvuru</h2>
<p>İptal/iade talepleri için [[firma_eposta]] adresinden veya destek talebi açarak bize ulaşabilirsiniz.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'cerez-politikasi',
                'title' => 'Çerez (Cookie) Politikası',
                'meta_description' => 'Web sitemizde kullanılan çerezler ve tercih yönetimi hakkında bilgilendirme.',
                'sort_order' => 8,
                'content' => <<<HTML
<h2>1. Çerez Nedir?</h2>
<p>Çerezler, web sitesini ziyaret ettiğinizde cihazınıza kaydedilen küçük metin dosyalarıdır.</p>
<h2>2. Kullanılan Çerezler</h2>
<ul>
<li><strong>Zorunlu çerezler:</strong> Oturum ve güvenlik için gereklidir.</li>
<li><strong>İşlevsel çerezler:</strong> Tercihlerinizi hatırlar.</li>
<li><strong>Analitik çerezler:</strong> Site kullanımını anlamamıza yardımcı olur.</li>
</ul>
<h2>3. Tercih Yönetimi</h2>
<p>Tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz; bu durumda bazı özellikler düzgün çalışmayabilir.</p>
<h2>4. İletişim</h2>
<p>Sorularınız için [[firma_eposta]] adresinden bize ulaşabilirsiniz.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, [
                    'is_published' => true,
                    'show_in_menu' => false,
                    'no_index' => false,
                ])
            );
        }
    }
}
