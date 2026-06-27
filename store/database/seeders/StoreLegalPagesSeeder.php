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
<p>Bu şartlar {$site} web sitesi ve sunulan hosting, sunucu ve domain hizmetlerinin kullanımını düzenler. Hizmetleri kullanarak bu şartları kabul etmiş sayılırsınız.</p>
<h2>2. Hizmet Kullanımı</h2>
<p>Hizmetler yalnızca yasal amaçlarla kullanılabilir. Spam, zararlı yazılım barındırma, telif ihlali, yasa dışı içerik ve sunucu kaynaklarının kötüye kullanımı (aşırı CPU/RAM tüketimi, kötüye kullanım amaçlı script çalıştırma) yasaktır.</p>
<h2>3. Ödeme ve Faturalandırma</h2>
<p>Hizmet bedelleri seçilen dönem için peşin veya sözleşmede belirtilen şekilde tahsil edilir. Yenileme bedelleri, dönem sonunda geçerli liste fiyatları üzerinden faturalandırılır.</p>
<h2>4. Sorumluluk Sınırı</h2>
<p>Mücbir sebep halleri ve üçüncü taraf ağ kesintileri dışında makul teknik önlemler alınır; hizmet sürekliliği ve yedekleme detayları SLA metninde yer alır. Müşteri, kendi verilerinin yedeğini almakla da yükümlüdür.</p>
<h2>5. Fesih</h2>
<p>Taraflar sözleşme koşullarına uygun şekilde hizmeti sonlandırabilir. Bu şartların ihlali halinde hizmet, bildirim yapılarak askıya alınabilir veya sonlandırılabilir.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'mesafeli-satis-sozlesmesi',
                'title' => 'Mesafeli Satış Sözleşmesi',
                'meta_description' => '6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamında mesafeli satış sözleşmesi.',
                'sort_order' => 6,
                'content' => <<<HTML
<h2>1. Taraflar</h2>
<p><strong>SATICI</strong><br>
Unvan: [[firma_unvan]]<br>
Adres: [[firma_adres]]<br>
Vergi Dairesi / No: [[firma_vergi_dairesi]] / [[firma_vergi_no]]<br>
Telefon: [[firma_telefon]]<br>
E-posta: [[firma_eposta]]</p>
<p><strong>ALICI</strong><br>
Sipariş sırasında belirtilen ad-soyad, adres, e-posta ve iletişim bilgilerine sahip müşteri.</p>
<h2>2. Konu</h2>
<p>İşbu sözleşmenin konusu, ALICI'nın SATICI'ya ait [[site_adi]] web sitesi üzerinden elektronik ortamda sipariş verdiği, aşağıda nitelik ve satış fiyatı belirtilen hizmetlerin (web hosting, sunucu, alan adı vb.) satışı ve ifası ile ilgili olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>
<h2>3. Sözleşme Konusu Hizmet Bilgileri</h2>
<p>Hizmetin türü, adedi, vergiler dahil satış bedeli ve ödeme şekli, sipariş onayı ve fatura ekranında/e-postasında belirtildiği gibidir. Bu bilgiler işbu sözleşmenin ayrılmaz parçasıdır.</p>
<h2>4. Genel Hükümler</h2>
<ul>
<li>ALICI, sipariş öncesi hizmetin temel nitelikleri, satış fiyatı ve ödeme şekli ile ifaya ilişkin ön bilgileri okuyup elektronik ortamda onayladığını kabul eder.</li>
<li>Hizmet, ödemenin onaylanmasının ardından elektronik ortamda derhal veya belirtilen süre içinde sağlanmaya başlanır.</li>
<li>Hizmetin sağlanması için gerekli kullanıcı bilgileri (panel adresi, kullanıcı adı, şifre) ALICI'nın sipariş sırasında verdiği e-posta adresine iletilir.</li>
</ul>
<h2>5. Cayma Hakkı</h2>
<p>ALICI, hizmet sunumuna ilişkin mesafeli sözleşmelerde, sözleşmenin kurulduğu tarihten itibaren <strong>14 (on dört) gün</strong> içinde herhangi bir gerekçe göstermeksizin ve cezai şart ödemeksizin cayma hakkına sahiptir. Cayma bildirimi [[firma_eposta]] adresine yazılı olarak iletilebilir.</p>
<h2>6. Cayma Hakkının İstisnaları</h2>
<p>Mesafeli Sözleşmeler Yönetmeliği m.15 uyarınca, ALICI'nın onayı ile <strong>ifasına başlanan ve elektronik ortamda anında ifa edilen hizmetler ile tüketiciye anında teslim edilen gayrimaddi mallar</strong> (örneğin alan adı tescili, anında kurulan hosting/sunucu hizmetleri) ile niteliği itibarıyla iade edilemeyecek hizmetlerde cayma hakkı kullanılamaz. Alan adı tescili, üçüncü taraf tescil kuruluşları nezdinde geri alınamaz şekilde gerçekleştiğinden iade kapsamı dışındadır.</p>
<h2>7. Ödeme ve İfa</h2>
<p>Ödeme, ALICI'nın seçtiği ödeme yöntemi (kredi kartı, banka havalesi/EFT veya desteklenen online ödeme yöntemleri) ile yapılır. Ödemenin onaylanmaması veya bankaca iade edilmesi halinde SATICI hizmeti ifa yükümlülüğünden kurtulur.</p>
<h2>8. Temerrüt Hükümleri</h2>
<p>ALICI'nın temerrüde düşmesi halinde, ALICI borcun gecikmeli ifasından doğan zararı ilgili mevzuat çerçevesinde ödemeyi kabul eder.</p>
<h2>9. Uyuşmazlıkların Çözümü</h2>
<p>İşbu sözleşmeden doğan uyuşmazlıklarda, Ticaret Bakanlığı'nca ilan edilen parasal sınırlar dahilinde ALICI'nın yerleşim yerindeki Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</p>
<h2>10. Yürürlük</h2>
<p>ALICI'nın siparişi elektronik ortamda onaylaması ile işbu sözleşme yürürlüğe girer ve ALICI tüm koşulları kabul etmiş sayılır.</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'iade-iptal-politikasi',
                'title' => 'İade, İptal ve Cayma Politikası',
                'meta_description' => 'Hizmet iadesi, iptali ve cayma hakkına ilişkin koşullar.',
                'sort_order' => 7,
                'content' => <<<HTML
<h2>1. Cayma Hakkı</h2>
<p>{$site} üzerinden satın alınan hizmetlerde, Mesafeli Sözleşmeler Yönetmeliği uyarınca sözleşmenin kurulduğu tarihten itibaren <strong>14 (on dört) gün</strong> içinde cayma hakkı bulunmaktadır. Cayma talepleri [[firma_eposta]] adresine veya müşteri paneli üzerinden iletilebilir.</p>
<h2>2. Cayma Hakkının Kullanılamayacağı Durumlar</h2>
<ul>
<li><strong>Alan adı (domain) tescilleri:</strong> Tescil işlemi üçüncü taraf kuruluşlar nezdinde anında ve geri alınamaz şekilde gerçekleştiğinden iade edilemez.</li>
<li><strong>İfasına başlanan anlık dijital hizmetler:</strong> ALICI'nın onayıyla kurulumu yapılan ve kullanıma açılan hosting/sunucu hizmetlerinde, tüketicinin onayı ile ifaya başlanması nedeniyle cayma hakkı sınırlanabilir.</li>
<li>SSL sertifikaları, lisanslar ve üçüncü taraf yazılım/eklenti bedelleri.</li>
</ul>
<h2>3. İade Süreci ve Süresi</h2>
<p>Cayma hakkının geçerli olduğu hallerde, onaylanan iade tutarı, ALICI'nın ödeme yaptığı yönteme uygun olarak <strong>en geç 14 gün</strong> içinde iade edilir. Kredi kartı iadelerinde bankaya bağlı olarak hesaba yansıma süresi değişebilir.</p>
<h2>4. Hizmet İptali ve Yenileme</h2>
<p>Periyodik (aylık/yıllık) hizmetler, yenileme tarihinden önce müşteri panelinden veya destek talebiyle iptal edilebilir. İptal edilmeyen hizmetler dönem sonunda otomatik yenilenebilir; yenileme sonrası kullanılmaya başlanan dönemler için iade yapılmayabilir.</p>
<h2>5. İletişim</h2>
<p>İade ve iptal talepleriniz için: [[firma_eposta]] — [[firma_telefon]]</p>
<p><em>Son güncelleme: {$year}</em></p>
HTML,
            ],
            [
                'slug' => 'cerez-politikasi',
                'title' => 'Çerez (Cookie) Politikası',
                'meta_description' => 'Web sitemizde kullanılan çerezler ve yönetimi hakkında bilgilendirme.',
                'sort_order' => 8,
                'content' => <<<HTML
<h2>1. Çerez Nedir?</h2>
<p>Çerezler, ziyaret ettiğiniz web siteleri tarafından tarayıcınıza kaydedilen küçük metin dosyalarıdır. {$site} olarak deneyiminizi geliştirmek ve hizmetlerimizi sunmak için çerezlerden yararlanıyoruz.</p>
<h2>2. Kullandığımız Çerez Türleri</h2>
<ul>
<li><strong>Zorunlu Çerezler:</strong> Oturum açma, sepet ve güvenlik gibi sitenin temel işlevleri için gereklidir; devre dışı bırakılamaz.</li>
<li><strong>Performans / Analitik Çerezler:</strong> Ziyaretçi davranışını anonim olarak ölçerek siteyi iyileştirmemize yardımcı olur.</li>
<li><strong>İşlevsel Çerezler:</strong> Dil, tema gibi tercihlerinizi hatırlar.</li>
<li><strong>Pazarlama Çerezleri:</strong> İlgi alanlarınıza uygun içerik/kampanya sunmak için kullanılabilir.</li>
</ul>
<h2>3. Çerezlerin Yönetimi</h2>
<p>Tarayıcınızın ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezlerin engellenmesi durumunda sitenin bazı bölümleri düzgün çalışmayabilir.</p>
<h2>4. Kişisel Veriler</h2>
<p>Çerezler aracılığıyla işlenen kişisel verileriniz, <a href="/sayfa/kvkk">KVKK Aydınlatma Metni</a> ve <a href="/sayfa/gizlilik">Gizlilik Politikası</a> kapsamında işlenir.</p>
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
