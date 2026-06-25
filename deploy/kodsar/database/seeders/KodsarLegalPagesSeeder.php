<?php

namespace Database\Seeders;

use App\Helpers\CacheHelper;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KodsarLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'hakkimizda' => [
                'title' => 'Hakkımızda',
                'meta_title' => 'Hakkımızda | Kodsar',
                'meta_description' => 'Kodsar; mobil uygulama kaynak kodları, yazılım lisansları ve dijital ürünler sunan teknoloji platformudur.',
                'content' => <<<'HTML'
<p><strong>Kodsar</strong>, yazılım geliştiricilere ve girişimcilere hazır mobil uygulama kaynak kodları, dijital ürünler ve teknik çözümler sunan bir teknoloji markasıdır. Web sitemiz <a href="https://kodsar.com">kodsar.com</a> üzerinden güvenli ödeme altyapısı ile lisanslı yazılım satışı yapılmaktadır.</p>
<h2>Misyonumuz</h2>
<p>Fikirden yayına giden yolu kısaltmak: kaliteli, dokümante edilmiş ve güncellenebilir yazılım ürünleri ile geliştiricilerin zamanını tasarruf ettirmek.</p>
<h2>Hizmetlerimiz</h2>
<ul>
<li>Flutter / mobil uygulama kaynak kodları</li>
<li>Dijital ürün satışı ve lisans yönetimi</li>
<li>Play Store ve App Store uyumlu gizlilik &amp; hesap silme sayfaları</li>
<li>Teknik destek ve kurulum rehberleri</li>
</ul>
<h2>İletişim</h2>
<p>E-posta: <a href="mailto:info@kodsar.com">info@kodsar.com</a><br>Web: <a href="https://kodsar.com">https://kodsar.com</a></p>
HTML,
            ],
            'iletisim' => [
                'title' => 'İletişim',
                'meta_title' => 'İletişim | Kodsar',
                'meta_description' => 'Kodsar ile iletişime geçin. Satış öncesi sorular, teknik destek ve iş birliği talepleri.',
                'content' => <<<'HTML'
<p>Sorularınız, sipariş desteği veya iş birliği teklifleri için bizimle iletişime geçebilirsiniz.</p>
<h2>E-posta</h2>
<p><a href="mailto:info@kodsar.com">info@kodsar.com</a></p>
<h2>Destek konuları</h2>
<ul>
<li>Sipariş ve indirme sorunları</li>
<li>Lisans aktivasyonu</li>
<li>Fatura ve ödeme</li>
<li>Gizlilik ve KVKK talepleri</li>
<li>Hesap silme talepleri</li>
</ul>
<h2>Yanıt süresi</h2>
<p>Hafta içi mesai saatlerinde (09:00–18:00, GMT+3) gelen taleplere genellikle 1–2 iş günü içinde dönüş yapılır.</p>
HTML,
            ],
            'gizlilik-politikasi' => [
                'title' => 'Gizlilik Politikası',
                'meta_title' => 'Gizlilik Politikası | Kodsar',
                'meta_description' => 'Kodsar web sitesi ve mobil uygulamaları için gizlilik politikası. Kişisel verilerin toplanması, kullanımı ve haklarınız.',
                'content' => $this->privacyContent(),
            ],
            'kullanim-sartlari' => [
                'title' => 'Kullanım Şartları',
                'meta_title' => 'Kullanım Şartları | Kodsar',
                'meta_description' => 'Kodsar web sitesi, müşteri paneli ve dijital ürün lisansları için kullanım şartları.',
                'content' => $this->termsContent(),
            ],
            'kvkk' => [
                'title' => 'KVKK Aydınlatma Metni',
                'meta_title' => 'KVKK Aydınlatma Metni | Kodsar',
                'meta_description' => '6698 sayılı KVKK kapsamında Kodsar veri sorumlusu aydınlatma metni.',
                'content' => $this->kvkkContent(),
            ],
            'hesap-silme' => [
                'title' => 'Hesap Silme',
                'meta_title' => 'Hesap Silme | Kodsar',
                'meta_description' => 'Kodsar web sitesi ve mobil uygulamalarında hesap ve veri silme talimatları.',
                'content' => $this->accountDeletionContent(),
            ],
        ];

        DB::transaction(function () use ($pages) {
            Page::where('slug', 'asdadasd')->delete();

            $pageIds = [];
            foreach ($pages as $slug => $data) {
                $page = Page::updateOrCreate(
                    ['slug' => $slug],
                    array_merge($data, ['is_active' => true, 'sort_order' => 0])
                );
                $pageIds[$slug] = $page->id;
            }

            $header = Menu::updateOrCreate(
                ['location' => 'header_main'],
                ['name' => 'Ana Menü', 'is_active' => true]
            );
            $header->allItems()->delete();
            $header->allItems()->createMany([
                ['label' => 'Ana Sayfa', 'url' => '/', 'type' => 'link', 'sort_order' => 1, 'is_active' => true, 'target' => '_self'],
                ['label' => 'Yazılım', 'type' => 'category', 'type_id' => 4, 'sort_order' => 2, 'is_active' => true, 'target' => '_self'],
                ['label' => 'Sepet', 'url' => '/sepet', 'type' => 'link', 'sort_order' => 3, 'is_active' => true, 'target' => '_self'],
                ['label' => 'İletişim', 'type' => 'page', 'type_id' => $pageIds['iletisim'], 'sort_order' => 4, 'is_active' => true, 'target' => '_self'],
            ]);

            $footerLinks = Menu::updateOrCreate(
                ['location' => 'footer_links'],
                ['name' => 'Footer Hızlı Linkler', 'is_active' => true]
            );
            $footerLinks->allItems()->delete();
            $footerLinks->allItems()->createMany([
                ['label' => 'Ana Sayfa', 'url' => '/', 'type' => 'link', 'sort_order' => 1, 'is_active' => true, 'target' => '_self'],
                ['label' => 'Yazılım Kataloğu', 'type' => 'category', 'type_id' => 4, 'sort_order' => 2, 'is_active' => true, 'target' => '_self'],
                ['label' => 'Hesabım', 'url' => '/hesabim', 'type' => 'link', 'sort_order' => 3, 'is_active' => true, 'target' => '_self'],
                ['label' => 'Giriş Yap', 'url' => '/login', 'type' => 'link', 'sort_order' => 4, 'is_active' => true, 'target' => '_self'],
            ]);

            $footerLegal = Menu::updateOrCreate(
                ['location' => 'footer_legal'],
                ['name' => 'Footer Yasal', 'is_active' => true]
            );
            $footerLegal->allItems()->delete();
            $legalItems = [
                ['slug' => 'hakkimizda', 'label' => 'Hakkımızda', 'order' => 1],
                ['slug' => 'gizlilik-politikasi', 'label' => 'Gizlilik Politikası', 'order' => 2],
                ['slug' => 'kullanim-sartlari', 'label' => 'Kullanım Şartları', 'order' => 3],
                ['slug' => 'kvkk', 'label' => 'KVKK Aydınlatma Metni', 'order' => 4],
                ['slug' => 'hesap-silme', 'label' => 'Hesap Silme', 'order' => 5],
                ['slug' => 'iletisim', 'label' => 'İletişim', 'order' => 6],
            ];
            foreach ($legalItems as $item) {
                $footerLegal->allItems()->create([
                    'label' => $item['label'],
                    'type' => 'page',
                    'type_id' => $pageIds[$item['slug']],
                    'sort_order' => $item['order'],
                    'is_active' => true,
                    'target' => '_self',
                ]);
            }
        });

        CacheHelper::clearMenuCache();
    }

    private function privacyContent(): string
    {
        return <<<'HTML'
<p>Bu Gizlilik Politikası; <strong>Kodsar</strong> (<a href="https://kodsar.com">kodsar.com</a>) web sitesi, müşteri paneli, satın alınan dijital ürünlerin teslimi ve Kodsar markasıyla yayınlanan <strong>mobil uygulamalar</strong> için geçerlidir. Veri sorumlusu: Kodsar — <a href="mailto:info@kodsar.com">info@kodsar.com</a></p>
<p><em>Son güncelleme: Haziran 2026</em></p>

<h2>1. Kapsam</h2>
<p>Bu metin; web sitesi ziyaretçileri, kayıtlı müşteriler, mobil uygulama kullanıcıları ve bülten abonelerini kapsar. Uygulama mağazalarında (Google Play, Apple App Store) ayrıca mağaza politikaları geçerlidir.</p>

<h2>2. Toplanan veriler</h2>
<h3>2.1 Web sitesi ve müşteri paneli</h3>
<ul>
<li><strong>Hesap bilgileri:</strong> ad, soyad, e-posta, telefon (isteğe bağlı), şifre (hashlenmiş)</li>
<li><strong>Sipariş bilgileri:</strong> ürün, tutar, fatura/adres bilgileri, ödeme durumu (kart bilgileri ödeme kuruluşunda işlenir, Kodsar kart numarası saklamaz)</li>
<li><strong>Teknik veriler:</strong> IP adresi, tarayıcı türü, cihaz bilgisi, oturum çerezleri, erişim logları</li>
<li><strong>İletişim:</strong> destek talepleri ve e-posta yazışmaları</li>
</ul>
<h3>2.2 Mobil uygulamalar</h3>
<p>Kodsar tarafından geliştirilen veya lisanslanan mobil uygulamalarda, uygulamanın işlevine bağlı olarak aşağıdaki veriler işlenebilir:</p>
<ul>
<li>Cihaz modeli, işletim sistemi sürümü, uygulama sürümü, çökme/performans logları</li>
<li>Kullanıcının uygulama içi tercihleri (bildirim ayarları, tema, dil vb.) — çoğunlukla cihazda yerel olarak</li>
<li>Bildirim izinleri (Android: POST_NOTIFICATIONS; zamanlama: SCHEDULE_EXACT_ALARM vb.)</li>
<li>Reklam/analitik modülleri varsa: reklam kimliği, anonim kullanım istatistikleri (uygulama bazında ayrıca belirtilir)</li>
<li>Kamera, galeri, konum, mikrofon gibi izinler yalnızca ilgili özellik kullanıldığında ve açık rıza/izin ile</li>
</ul>

<h2>3. Verilerin kullanım amaçları</h2>
<ul>
<li>Hesap oluşturma, giriş ve sipariş süreçlerinin yürütülmesi</li>
<li>Dijital ürün teslimi, lisans anahtarı üretimi ve indirme linklerinin sağlanması</li>
<li>Ödeme doğrulama, fatura/muhasebe yükümlülükleri</li>
<li>Müşteri desteği ve talep yönetimi</li>
<li>Web sitesi güvenliği, dolandırıcılık önleme ve yasal yükümlülükler</li>
<li>Mobil uygulamalarda temel işlevlerin sağlanması (bildirim, senkronizasyon, yedekleme vb.)</li>
<li>Açık rızanız varsa pazarlama iletişimi ve bülten gönderimi</li>
</ul>

<h2>4. Hukuki sebepler (KVKK m.5–6)</h2>
<p>Veriler; sözleşmenin kurulması/ifası, hukuki yükümlülük, meşru menfaat ve gerektiğinde açık rıza hukuki sebeplerine dayanılarak işlenir.</p>

<h2>5. Çerezler (Cookies)</h2>
<p>Web sitemiz oturum yönetimi, sepet, güvenlik ve tercih hatırlama için zorunlu çerezler kullanır. Analitik/pazarlama çerezleri kullanıldığında tarayıcı banner’ı üzerinden tercih yönetimi sunulur.</p>

<h2>6. Üçüncü taraflar</h2>
<p>Veriler yalnızca hizmetin gerektirdiği ölçüde paylaşılır:</p>
<ul>
<li>Ödeme kuruluşları (PayTR, iyzico vb.)</li>
<li>Barındırma / altyapı sağlayıcıları</li>
<li>E-posta ve bildirim servisleri</li>
<li>Google Play / Apple App Store (uygulama dağıtımı)</li>
<li>AdMob, Firebase Analytics vb. — yalnızca ilgili uygulamada etkinse</li>
</ul>
<p>Üçüncü taraflar kendi gizlilik politikalarına tabidir.</p>

<h2>7. Saklama süreleri</h2>
<ul>
<li>Hesap verileri: hesap aktif olduğu sürece; silme talebinden sonra makul süre içinde silinir veya anonimleştirilir</li>
<li>Sipariş/fatura kayıtları: mevzuat gereği en az 10 yıl</li>
<li>Log kayıtları: genellikle 6–24 ay</li>
<li>Mobil uygulama yerel verileri: kullanıcı uygulamayı kaldırana veya uygulama içi “verileri sil” seçeneğini kullanana kadar cihazda kalabilir</li>
</ul>

<h2>8. Güvenlik</h2>
<p>SSL/TLS şifreleme, erişim kontrolü, güvenli sunucu altyapısı ve düzenli yedekleme uygulanır. Hiçbir sistem %100 güvenli garanti edilemez; şüpheli durumları <a href="mailto:info@kodsar.com">info@kodsar.com</a> adresine bildirin.</p>

<h2>9. Haklarınız</h2>
<p>KVKK kapsamında; verilerinize erişme, düzeltme, silme, işlemeyi kısıtlama, itiraz etme ve taşınabilirlik taleplerinde bulunabilirsiniz. Talepler: <a href="mailto:info@kodsar.com">info@kodsar.com</a> — en geç 30 gün içinde yanıtlanır. Ayrıntılar için <a href="/sayfa/kvkk">KVKK Aydınlatma Metni</a> sayfamıza bakın.</p>

<h2>10. Çocukların gizliliği</h2>
<p>Hizmetlerimiz 18 yaş altına yönelik değildir. Bilerek çocuklardan kişisel veri toplamayız.</p>

<h2>11. Değişiklikler</h2>
<p>Bu politika güncellenebilir. Önemli değişiklikler web sitesinde ve uygulama mağazası sayfalarında duyurulur.</p>

<h2>12. İletişim</h2>
<p><strong>Kodsar</strong><br>E-posta: <a href="mailto:info@kodsar.com">info@kodsar.com</a><br>Web: <a href="https://kodsar.com">https://kodsar.com</a></p>
HTML;
    }

    private function termsContent(): string
    {
        return <<<'HTML'
<p>Bu Kullanım Şartları, <strong>kodsar.com</strong> web sitesini, müşteri panelini ve Kodsar üzerinden satın alınan dijital ürünleri kullanımınızı düzenler. Siteyi kullanarak bu şartları kabul etmiş sayılırsınız.</p>

<h2>1. Tanımlar</h2>
<ul>
<li><strong>Platform:</strong> kodsar.com web sitesi ve ilişkili paneller</li>
<li><strong>Dijital ürün:</strong> kaynak kod, lisans, indirilebilir dosya veya yazılım paketi</li>
<li><strong>Kullanıcı / Müşteri:</strong> siteye kayıt olan veya alışveriş yapan gerçek/tüzel kişi</li>
</ul>

<h2>2. Hizmet kapsamı</h2>
<p>Kodsar, yazılım ve dijital ürünlerin listelenmesi, satışı, lisanslanması ve teslimini sağlar. Ürün açıklamalarındaki teknik özellikler bağlayıcıdır; stok/lisans durumu sipariş anında doğrulanır.</p>

<h2>3. Hesap güvenliği</h2>
<p>Hesap bilgilerinizin gizliliğinden siz sorumlusunuz. Yetkisiz kullanım şüphesinde derhal bize bildirin. Sahte/yanıltıcı bilgi ile açılan hesaplar kapatılabilir.</p>

<h2>4. Sipariş ve ödeme</h2>
<ul>
<li>Fiyatlar TRY cinsinden gösterilir; KDV dahil/hariç durumu ödeme ekranında belirtilir</li>
<li>Ödeme onayından sonra dijital teslimat e-posta ve/veya hesabım bölümünden yapılır</li>
<li>Hatalı fiyatlandırma tespitinde sipariş iptal edilebilir ve ücret iade edilir</li>
</ul>

<h2>5. Lisans ve kullanım hakları</h2>
<p>Her ürünün lisans kapsamı ürün sayfasında belirtilir. Genel olarak:</p>
<ul>
<li>Kaynak kodlar belirtilen proje sayısında kullanıma izin verir; yeniden satış yasaktır (aksi belirtilmedikçe)</li>
<li>Marka, logo ve Kodsar ismi izinsiz kullanılamaz</li>
<li>Tersine mühendislik yalnızca yasal izinler ve lisans kapsamında mümkündür</li>
</ul>

<h2>6. İade politikası</h2>
<p>Dijital ürünlerin doğası gereği, indirme/teslimat sonrası iade kural olarak kabul edilmez; yasal zorunluluklar ve teknik teslim edilememe durumları saklıdır. Destek için <a href="mailto:info@kodsar.com">info@kodsar.com</a>.</p>

<h2>7. Yasaklı kullanımlar</h2>
<ul>
<li>Yasa dışı, zararlı veya üçüncü kişi haklarını ihlal eden faaliyetler</li>
<li>Sisteme yetkisiz müdahale, scraping, DDoS</li>
<li>Sahte sipariş, chargeback kötüye kullanımı</li>
</ul>

<h2>8. Fikri mülkiyet</h2>
<p>Site tasarımı, marka ve içerikler Kodsar’a aittir. Ürün lisansları ilgili ürün sözleşmesine tabidir.</p>

<h2>9. Sorumluluk sınırı</h2>
<p>Hizmet “olduğu gibi” sunulur. Dolaylı zarar, veri kaybı veya iş kesintisinden doğan sorumluluk, yasaların izin verdiği ölçüde sınırlıdır.</p>

<h2>10. Mobil uygulamalar</h2>
<p>Kodsar mobil uygulamaları ilgili mağaza kurallarına ve uygulama içi gizlilik metnine tabidir. Uygulama güncellemeleri mağaza üzerinden dağıtılır.</p>

<h2>11. Uyuşmazlık</h2>
<p>Türkiye Cumhuriyeti kanunları uygulanır. Tüketici sıfatıyla haklarınız 6502 sayılı Kanun kapsamında saklıdır.</p>

<h2>12. İletişim</h2>
<p><a href="mailto:info@kodsar.com">info@kodsar.com</a> — <a href="/sayfa/iletisim">İletişim sayfası</a></p>
HTML;
    }

    private function kvkkContent(): string
    {
        return <<<'HTML'
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca veri sorumlusu sıfatıyla <strong>Kodsar</strong> tarafından hazırlanmış aydınlatma metnidir.</p>

<h2>1. Veri sorumlusu</h2>
<p><strong>Unvan:</strong> Kodsar<br><strong>Web:</strong> <a href="https://kodsar.com">kodsar.com</a><br><strong>E-posta:</strong> <a href="mailto:info@kodsar.com">info@kodsar.com</a></p>

<h2>2. İşlenen kişisel veri kategorileri</h2>
<ul>
<li>Kimlik ve iletişim (ad, e-posta, telefon)</li>
<li>Müşteri işlem (sipariş, ödeme durumu, lisans)</li>
<li>Finans (fatura bilgileri — kart verisi ödeme kuruluşunda)</li>
<li>İşlem güvenliği (IP, log, çerez)</li>
<li>Pazarlama (açık rıza ile bülten)</li>
</ul>

<h2>3. Toplama yöntemi ve hukuki sebep</h2>
<p>Veriler; web formu, sipariş, e-posta, mobil uygulama ve otomatik loglar aracılığıyla toplanır. Hukuki sebepler: sözleşme, hukuki yükümlülük, meşru menfaat, açık rıza.</p>

<h2>4. Aktarım</h2>
<p>Barındırma, ödeme, e-posta ve yasal mercilerle sınırlı aktarım yapılabilir. Yurt dışına aktarım varsa KVKK m.9 hükümlerine uyulur.</p>

<h2>5. Haklar (KVKK m.11)</h2>
<p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltme, silme, anonimleştirme, aktarılan üçüncü kişileri bilme, otomatik sistemlere itiraz ve zarar giderimi talep etme haklarına sahipsiniz.</p>
<p><strong>Başvuru:</strong> <a href="mailto:info@kodsar.com">info@kodsar.com</a> — kimliğinizi doğrulayıcı bilgi ekleyin. Yanıt süresi en geç 30 gün.</p>

<h2>6. İlgili kişi başvuru formu</h2>
<p>E-posta konusuna “KVKK Başvurusu” yazmanız yeterlidir. Talep türünü (erişim, silme, düzeltme vb.) açıkça belirtin.</p>

<p>Detaylı gizlilik uygulamaları için <a href="/sayfa/gizlilik-politikasi">Gizlilik Politikası</a> sayfasına bakın.</p>
HTML;
    }

    private function accountDeletionContent(): string
    {
        return <<<'HTML'
<p>Bu sayfa; <strong>Kodsar web sitesi</strong>, <strong>müşteri paneli</strong> ve <strong>Kodsar markalı mobil uygulamalar</strong> için hesap ve kişisel veri silme talimatlarını içerir. Google Play ve Apple App Store veri silme gereksinimleriyle uyumludur.</p>

<h2>1. Web sitesi / müşteri paneli hesabı silme</h2>
<h3>Yöntem A — Panel üzerinden (önerilen)</h3>
<ol>
<li><a href="/login">Giriş yapın</a></li>
<li><strong>Profil</strong> sayfasına gidin (<a href="/profile">/profile</a>)</li>
<li><strong>Hesabı sil</strong> bölümünden kalıcı silme işlemini onaylayın</li>
</ol>
<h3>Yöntem B — E-posta ile</h3>
<p>Kayıtlı e-posta adresinizden <a href="mailto:info@kodsar.com?subject=Hesap%20Silme%20Talebi">info@kodsar.com</a> adresine konu: <strong>Hesap Silme Talebi</strong> ile yazın. Ad soyad ve kayıtlı e-postayı belirtin. Kimlik doğrulaması sonrası en geç 30 gün içinde işlem yapılır.</p>

<h2>2. Silinen / saklanan veriler</h2>
<ul>
<li><strong>Silinir:</strong> profil bilgileri, adresler (varsa), oturum verileri, pazarlama tercihleri</li>
<li><strong>Saklanabilir (yasal zorunluluk):</strong> sipariş, fatura ve muhasebe kayıtları (vergi mevzuatı süresi boyunca)</li>
<li><strong>Lisans/indirme geçmişi:</strong> yasal uyuşmazlık ve dolandırıcılık önleme amacıyla sınırlı süre loglanabilir</li>
</ul>

<h2>3. Mobil uygulama hesabı / veri silme</h2>
<p>Kodsar tarafından yayınlanan mobil uygulamalarda hesap sistemi varsa:</p>
<ol>
<li>Uygulama içi <strong>Ayarlar → Hesabım → Hesabı Sil</strong> (varsa) yolunu kullanın</li>
<li>Veya uygulama adını, kayıtlı e-postayı ve cihaz türünü belirterek <a href="mailto:info@kodsar.com?subject=Mobil%20Uygulama%20Hesap%20Silme">info@kodsar.com</a> adresine yazın</li>
</ol>
<p>Hesap gerektirmeyen uygulamalarda veriler çoğunlukla cihazda yerel tutulur; uygulamayı kaldırmak veya uygulama içi “verileri sıfırla” seçeneği verileri siler.</p>

<h2>4. Bildirim izinleri</h2>
<p>Bildirimleri cihaz ayarlarından kapatabilir; bu hesap silme yerine geçmez ancak push bildirim veri akışını durdurur.</p>

<h2>5. İşlem süresi</h2>
<p>Doğrulanmış talepler genellikle <strong>7–30 iş günü</strong> içinde tamamlanır. Tamamlanınca onay e-postası gönderilir.</p>

<h2>6. Sorular</h2>
<p><a href="/sayfa/gizlilik-politikasi">Gizlilik Politikası</a> · <a href="/sayfa/kvkk">KVKK</a> · <a href="/sayfa/iletisim">İletişim</a></p>
HTML;
    }
}
