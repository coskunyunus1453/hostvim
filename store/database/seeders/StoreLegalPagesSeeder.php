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
        $date = date('d.m.Y');

        $pages = [
            [
                'slug' => 'hakkimizda',
                'title' => 'Hakkımızda',
                'meta_title' => "Hakkımızda — {$site}",
                'meta_description' => "{$site} — Türkiye'nin güvenilir hosting, VPS, VDS, dedicated sunucu ve domain çözüm ortağı. NVMe SSD altyapı, 7/24 Türkçe destek.",
                'sort_order' => 1,
                'content' => <<<HTML
<p class="lead">{$site}, işletmelerin ve bireysel kullanıcıların dijital projelerini güvenle büyütebilmeleri için yüksek performanslı barındırma ve alan adı hizmetleri sunan bir teknoloji şirketidir.</p>

<h2>Biz Kimiz?</h2>
<p>{$site} olarak; web hosting, VPS (sanal sunucu), VDS, dedicated (fiziksel) sunucu ve domain (alan adı) tescil hizmetlerini tek çatı altında, şeffaf fiyatlandırma ve uzman destek anlayışıyla sunuyoruz. Altyapımız NVMe SSD diskler, güçlü işlemciler ve yedekli ağ bağlantısı üzerine kurulmuştur. Amacımız; küçük ölçekli kişisel sitelerden kurumsal e-ticaret platformlarına kadar her ölçekteki projeye kesintisiz, hızlı ve güvenli bir barındırma deneyimi yaşatmaktır.</p>

<h2>Misyonumuz</h2>
<p>Her ölçekteki müşteriye; gizli maliyet içermeyen şeffaf fiyatlandırma, dakikalar içinde otomatik kurulum ve gerçekten ulaşılabilir bir teknik destek sunmak. Teknolojiyi karmaşıklıktan arındırıp herkes için erişilebilir kılmak.</p>

<h2>Vizyonumuz</h2>
<p>Türkiye'nin ve bölgenin en çok güvenilen barındırma markalarından biri olmak; performans, güvenlik ve müşteri memnuniyetinde sektör standardını belirlemek.</p>

<h2>Neden {$site}?</h2>
<ul>
<li><strong>Yüksek performans:</strong> NVMe SSD depolama, güncel PHP sürümleri ve optimize edilmiş sunucu yapılandırması.</li>
<li><strong>%99.9 uptime hedefi:</strong> Yedekli altyapı ve sürekli izleme ile kesintisiz erişim.</li>
<li><strong>7/24 Türkçe destek:</strong> Uzman ekibimize her an ulaşabilirsiniz.</li>
<li><strong>Ücretsiz taşıma:</strong> Uygun paketlerde sitenizi biz taşıyoruz, kesinti yaşamazsınız.</li>
<li><strong>Otomatik yedekleme:</strong> Verileriniz düzenli olarak yedeklenir.</li>
<li><strong>Ücretsiz SSL:</strong> Tüm sitelerde güvenli HTTPS bağlantısı.</li>
<li><strong>KVKK uyumu:</strong> Kişisel verilerinizin güvenliğine önem veriyoruz.</li>
</ul>

<h2>Değerlerimiz</h2>
<ul>
<li>Şeffaflık ve dürüstlük</li>
<li>Müşteri odaklılık</li>
<li>Sürekli iyileştirme ve teknolojik yenilik</li>
<li>Veri güvenliği ve gizliliğe saygı</li>
</ul>

<h2>İletişim</h2>
<p>Sorularınız, iş birliği talepleriniz veya teknik destek için <a href="/iletisim">İletişim</a> sayfamızdan bize ulaşabilirsiniz. Ekibimiz size yardımcı olmaktan memnuniyet duyar.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'sss',
                'title' => 'Sıkça Sorulan Sorular',
                'meta_title' => "Sıkça Sorulan Sorular (SSS) — {$site}",
                'meta_description' => 'Hosting, sunucu, domain, ödeme ve destek hizmetleri hakkında en çok sorulan sorular ve yanıtları.',
                'sort_order' => 2,
                'content' => <<<HTML
<h2>Genel</h2>
<h3>{$site} hangi hizmetleri sunuyor?</h3>
<p>Web hosting, VPS, VDS, dedicated sunucu ve domain tescil hizmetleri sunuyoruz. Tüm hizmetlerimiz NVMe SSD altyapısı ve 7/24 destek ile gelir.</p>

<h3>Hesabımı nasıl oluşturabilirim?</h3>
<p>Sitemizden bir paket seçip sipariş adımlarını tamamlayarak saniyeler içinde hesabınızı oluşturabilirsiniz. Ödeme onaylandıktan sonra hizmetiniz otomatik olarak aktive edilir.</p>

<h2>Hosting</h2>
<h3>Hosting paketimi nasıl yükseltebilirim?</h3>
<p>Müşteri panelinizden veya bir destek talebi açarak dilediğiniz zaman paket yükseltmesi yapabilirsiniz. Yükseltme sırasında verileriniz korunur ve kesinti yaşanmaz.</p>

<h3>Ücretsiz site taşıma hizmeti var mı?</h3>
<p>Evet. Uygun hosting paketlerinde, mevcut sitenizi başka bir sağlayıcıdan {$site}'e ücretsiz olarak taşıyoruz. Taşıma talebinizi destek ekibimize iletmeniz yeterlidir.</p>

<h3>Hangi kontrol panelini kullanıyorsunuz?</h3>
<p>Modern, Türkçe ve kullanımı kolay bir kontrol paneli sunuyoruz; dosya yönetimi, e-posta, veritabanı ve yedekleme işlemlerini tek ekrandan yönetebilirsiniz.</p>

<h3>SSL sertifikası dahil mi?</h3>
<p>Evet, tüm sitelerinizde ücretsiz SSL sertifikası ile güvenli HTTPS bağlantısı sağlanır.</p>

<h2>Domain</h2>
<h3>Alan adımı başka firmadan taşıyabilir miyim?</h3>
<p>Evet, transfer koşullarını sağlayan alan adlarınızı {$site}'e taşıyabilirsiniz. Transfer süreci uzantıya göre değişmekle birlikte genellikle kısa sürede tamamlanır.</p>

<h3>Domain tescili ne kadar sürede aktif olur?</h3>
<p>Ödeme onayının ardından alan adı tescili çoğunlukla birkaç dakika içinde tamamlanır.</p>

<h2>Ödeme ve Faturalandırma</h2>
<h3>Hangi ödeme yöntemlerini kabul ediyorsunuz?</h3>
<p>Kredi/banka kartı, banka havalesi/EFT ve desteklenen online ödeme yöntemleri ile ödeme yapabilirsiniz.</p>

<h3>Faturamı nasıl alırım?</h3>
<p>Ödemeniz tamamlandığında faturanız elektronik ortamda düzenlenir ve hesabınıza/eposta adresinize iletilir.</p>

<h3>İade alabilir miyim?</h3>
<p>İade koşulları için <a href="/sayfa/iade-iptal-ve-cayma-politikasi">İade, İptal ve Cayma Politikası</a> sayfamızı inceleyebilirsiniz.</p>

<h2>Destek</h2>
<h3>Destek saatleriniz nedir?</h3>
<p>Teknik destek ekibimiz 7/24 hizmetinizdedir. Destek talebi, e-posta veya telefon ile bize ulaşabilirsiniz.</p>

<h3>Verilerim yedekleniyor mu?</h3>
<p>Evet, verileriniz düzenli olarak yedeklenir. Yine de kritik verileriniz için kendi yedeklerinizi de almanızı öneririz.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'gizlilik',
                'title' => 'Gizlilik Politikası',
                'meta_title' => "Gizlilik Politikası — {$site}",
                'meta_description' => 'Kişisel verilerinizin nasıl toplandığı, işlendiği, saklandığı ve korunduğuna ilişkin gizlilik politikamız.',
                'sort_order' => 3,
                'content' => <<<HTML
<p class="lead">{$site} ("Şirket", "biz") olarak kişisel verilerinizin gizliliğine önem veriyoruz. Bu Gizlilik Politikası, web sitemizi ve hizmetlerimizi kullandığınızda hangi verileri topladığımızı, bu verileri nasıl kullandığımızı ve haklarınızı açıklar.</p>

<h2>1. Veri Sorumlusu</h2>
<p>İşbu politika kapsamında kişisel verileriniz, veri sorumlusu sıfatıyla [[firma_unvan]] tarafından, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") ve ilgili mevzuata uygun olarak işlenmektedir.</p>
<p>Adres: [[firma_adres]]<br>E-posta: [[firma_eposta]] — Telefon: [[firma_telefon]]</p>

<h2>2. Topladığımız Veriler</h2>
<ul>
<li><strong>Kimlik ve iletişim verileri:</strong> Ad, soyad, e-posta, telefon, fatura adresi.</li>
<li><strong>Müşteri işlem verileri:</strong> Sipariş geçmişi, hizmet kullanım kayıtları, destek talepleri.</li>
<li><strong>Finansal veriler:</strong> Fatura bilgileri ve ödeme işlem kayıtları (kart bilgileriniz tarafımızca saklanmaz; ödeme kuruluşları tarafından işlenir).</li>
<li><strong>İşlem güvenliği verileri:</strong> IP adresi, oturum bilgileri, erişim ve log kayıtları.</li>
<li><strong>Çerez verileri:</strong> Tarayıcı çerezleri aracılığıyla toplanan tercih ve kullanım bilgileri (bkz. <a href="/sayfa/cerez-politikasi">Çerez Politikası</a>).</li>
</ul>

<h2>3. Verilerin İşlenme Amaçları</h2>
<ul>
<li>Hizmetlerin sunulması ve sözleşmenin ifası</li>
<li>Sipariş, fatura ve ödeme süreçlerinin yürütülmesi</li>
<li>Teknik destek ve müşteri ilişkileri yönetimi</li>
<li>Bilgi güvenliğinin ve hizmet sürekliliğinin sağlanması</li>
<li>Yasal yükümlülüklerin yerine getirilmesi</li>
<li>İzin vermeniz halinde tanıtım ve bilgilendirme iletişimi</li>
</ul>

<h2>4. Verilerin Aktarılması</h2>
<p>Kişisel verileriniz; yalnızca hizmetin ifası, yasal zorunluluklar veya açık rızanız kapsamında, gerekli güvenlik tedbirleri alınarak iş ortaklarımıza ve hizmet sağlayıcılarımıza (ödeme kuruluşları, altyapı/veri merkezi sağlayıcıları, alan adı tescil kurumları, e-posta/SMS gönderim hizmetleri) aktarılabilir. Verileriniz pazarlama amacıyla üçüncü taraflara satılmaz.</p>

<h2>5. Saklama Süresi</h2>
<p>Kişisel verileriniz, işlenme amacının gerektirdiği süre boyunca ve ilgili mevzuatta öngörülen yasal saklama süreleri (örneğin vergi mevzuatı kapsamında fatura kayıtları) boyunca saklanır. Süre sonunda verileriniz silinir, yok edilir veya anonim hale getirilir.</p>

<h2>6. Veri Güvenliği</h2>
<p>Verilerinizin yetkisiz erişime, kayba veya kötüye kullanıma karşı korunması için güvenlik duvarları, şifreleme (SSL/TLS), erişim yetkilendirmesi ve düzenli güvenlik denetimleri gibi teknik ve idari tedbirler uygulanmaktadır.</p>

<h2>7. Haklarınız</h2>
<p>KVKK'nın 11. maddesi kapsamında; kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, işlenme amacını öğrenme, düzeltilmesini/silinmesini isteme ve diğer haklarınızı kullanabilirsiniz. Talepleriniz için <a href="/iletisim">İletişim</a> sayfamızdan veya [[firma_eposta]] adresinden bize ulaşabilirsiniz.</p>

<h2>8. Değişiklikler</h2>
<p>Bu Gizlilik Politikası gerektiğinde güncellenebilir. Güncel sürüm her zaman bu sayfada yayımlanır.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'kvkk',
                'title' => 'KVKK Aydınlatma Metni',
                'meta_title' => "KVKK Aydınlatma Metni — {$site}",
                'meta_description' => '6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verilerin işlenmesine ilişkin aydınlatma metni.',
                'sort_order' => 4,
                'content' => <<<HTML
<p class="lead">İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu'nun ("KVKK") 10. maddesi kapsamında, veri sorumlusu sıfatıyla [[firma_unvan]] tarafından hazırlanmıştır.</p>

<h2>1. Veri Sorumlusunun Kimliği</h2>
<p><strong>Unvan:</strong> [[firma_unvan]]<br>
<strong>Adres:</strong> [[firma_adres]]<br>
<strong>Vergi Dairesi / No:</strong> [[firma_vergi_dairesi]] / [[firma_vergi_no]]<br>
<strong>Telefon:</strong> [[firma_telefon]] — <strong>E-posta:</strong> [[firma_eposta]]</p>

<h2>2. İşlenen Kişisel Veri Kategorileri</h2>
<ul>
<li>Kimlik verileri (ad, soyad)</li>
<li>İletişim verileri (e-posta, telefon, adres)</li>
<li>Müşteri işlem verileri (sipariş, hizmet kullanımı, talepler)</li>
<li>Finans verileri (fatura, ödeme kayıtları)</li>
<li>İşlem güvenliği verileri (IP, log kayıtları, oturum bilgileri)</li>
</ul>

<h2>3. Kişisel Verilerin İşlenme Amaçları</h2>
<p>Kişisel verileriniz; hizmetlerin sunulması, sözleşmenin kurulması ve ifası, fatura ve ödeme işlemleri, müşteri destek süreçleri, bilgi güvenliğinin sağlanması ve yasal yükümlülüklerin yerine getirilmesi amaçlarıyla işlenmektedir.</p>

<h2>4. Kişisel Veri İşlemenin Hukuki Sebepleri</h2>
<p>Verileriniz KVKK m.5 uyarınca; bir sözleşmenin kurulması veya ifası için gerekli olması, hukuki yükümlülüğün yerine getirilmesi, meşru menfaat ve gerekli hallerde açık rızanız hukuki sebeplerine dayanılarak işlenir.</p>

<h2>5. Kişisel Verilerin Aktarılması</h2>
<p>Verileriniz, hizmetin ifası ve yasal yükümlülükler çerçevesinde; ödeme kuruluşları, altyapı/veri merkezi sağlayıcıları, alan adı tescil kurumları, yetkili kamu kurum ve kuruluşları ile gerekli güvenlik tedbirleri alınarak paylaşılabilir.</p>

<h2>6. Kişisel Veri Toplamanın Yöntemi</h2>
<p>Verileriniz; web sitesi, sipariş formları, destek kanalları, çerezler ve elektronik iletişim araçları üzerinden, otomatik ve otomatik olmayan yöntemlerle toplanmaktadır.</p>

<h2>7. İlgili Kişinin Hakları (KVKK m.11)</h2>
<p>Kişisel veri sahibi olarak;</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
<li>İşlenmişse buna ilişkin bilgi talep etme,</li>
<li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
<li>Yurt içinde/dışında aktarıldığı üçüncü kişileri bilme,</li>
<li>Eksik veya yanlış işlenmişse düzeltilmesini isteme,</li>
<li>Şartları oluştuğunda silinmesini/yok edilmesini isteme,</li>
<li>Düzeltme/silme işlemlerinin aktarıldığı üçüncü kişilere bildirilmesini isteme,</li>
<li>Münhasıran otomatik sistemlerle analiz sonucu aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
<li>Kanuna aykırı işleme nedeniyle zarara uğramanız halinde zararın giderilmesini talep etme</li>
</ul>
<p>haklarına sahipsiniz.</p>

<h2>8. Başvuru Yöntemi</h2>
<p>Yukarıdaki haklarınıza ilişkin taleplerinizi yazılı olarak veya kayıtlı elektronik posta (KEP) ile [[firma_eposta]] adresine iletebilirsiniz. Talepleriniz, niteliğine göre en kısa sürede ve en geç 30 gün içinde sonuçlandırılır.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'kullanim-sartlari',
                'title' => 'Kullanım Şartları',
                'meta_title' => "Kullanım Şartları — {$site}",
                'meta_description' => 'Web sitesi ve hosting/sunucu/domain hizmetlerinin kullanımına ilişkin koşullar ve kurallar.',
                'sort_order' => 5,
                'content' => <<<HTML
<p class="lead">Bu Kullanım Şartları, {$site} web sitesini ve sunduğumuz hizmetleri kullanımınıza ilişkin koşulları düzenler. Hizmetlerimizi kullanarak bu şartları kabul etmiş sayılırsınız.</p>

<h2>1. Kapsam</h2>
<p>İşbu şartlar; {$site} tarafından sunulan web hosting, VPS, VDS, dedicated sunucu, domain ve ilgili tüm hizmetlerin kullanımını kapsar.</p>

<h2>2. Hizmet Kullanım Kuralları</h2>
<p>Hizmetler yalnızca yasalara uygun amaçlarla kullanılabilir. Aşağıdaki faaliyetler kesinlikle yasaktır:</p>
<ul>
<li>İstenmeyen toplu e-posta (spam) gönderimi,</li>
<li>Zararlı yazılım, virüs, phishing veya dolandırıcılık içeriği barındırma,</li>
<li>Telif hakkı veya fikri mülkiyet haklarını ihlal eden içerik yayınlama,</li>
<li>Sunucu kaynaklarını kötüye kullanma veya diğer kullanıcıların hizmetini olumsuz etkileme,</li>
<li>Yetkisiz erişim, ağ saldırısı veya güvenlik sistemlerini atlatma girişimleri,</li>
<li>Yürürlükteki mevzuata aykırı her türlü faaliyet.</li>
</ul>

<h2>3. Hesap Güvenliği</h2>
<p>Hesabınıza ait kullanıcı adı ve şifrenin gizliliğinden ve hesabınız üzerinden gerçekleştirilen tüm işlemlerden siz sorumlusunuz. Yetkisiz bir kullanım fark etmeniz halinde derhal bizi bilgilendirmelisiniz.</p>

<h2>4. Ödeme ve Faturalandırma</h2>
<p>Hizmet bedelleri, seçilen dönem için peşin olarak veya sipariş sırasında belirtilen şekilde tahsil edilir. Yenileme tarihinde ödemesi yapılmayan hizmetler askıya alınabilir veya sonlandırılabilir. Tüm fiyatlara aksi belirtilmedikçe KDV dahildir.</p>

<h2>5. Hizmet Sürekliliği ve SLA</h2>
<p>%99.9 erişilebilirlik hedefiyle çalışırız. Planlı bakımlar önceden duyurulur. Mücbir sebepler, üçüncü taraf ağ kesintileri ve müşteri kaynaklı sorunlar bu kapsam dışındadır.</p>

<h2>6. Yedekleme</h2>
<p>Düzenli yedekleme yapmamıza rağmen, verilerinizin nihai sorumluluğu size aittir. Kritik verileriniz için kendi yedeklerinizi de almanızı önemle tavsiye ederiz.</p>

<h2>7. Sorumluluğun Sınırlandırılması</h2>
<p>{$site}, makul teknik önlemleri alır; ancak hizmet kullanımından doğan dolaylı zararlardan, veri kaybından veya kâr kaybından, ilgili mevzuatın izin verdiği ölçüde sorumlu tutulamaz.</p>

<h2>8. Hizmetin Askıya Alınması ve Fesih</h2>
<p>Bu şartların ihlali halinde {$site}, hizmeti önceden bildirimde bulunarak veya gerekli hallerde derhal askıya alabilir ya da sonlandırabilir. Taraflar, sözleşme koşullarına uygun olarak hizmeti sonlandırma hakkına sahiptir.</p>

<h2>9. Değişiklikler</h2>
<p>{$site}, bu şartları gerektiğinde güncelleyebilir. Güncel sürüm bu sayfada yayımlandığı anda yürürlüğe girer.</p>

<h2>10. Uygulanacak Hukuk</h2>
<p>Bu şartların yorumunda ve uygulanmasında Türkiye Cumhuriyeti hukuku geçerlidir. Uyuşmazlıklarda yetkili merciler hakkında <a href="/sayfa/mesafeli-satis-sozlesmesi">Mesafeli Satış Sözleşmesi</a> hükümleri uygulanır.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'mesafeli-satis-sozlesmesi',
                'title' => 'Mesafeli Satış Sözleşmesi',
                'meta_title' => "Mesafeli Satış Sözleşmesi — {$site}",
                'meta_description' => 'Mesafeli satış sözleşmesi: taraflar, sözleşmenin konusu, hizmet bilgileri, ödeme, ifa, cayma hakkı ve uyuşmazlık çözümü.',
                'sort_order' => 6,
                'content' => <<<HTML
<p class="lead">İşbu Mesafeli Satış Sözleşmesi ("Sözleşme"), 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri uyarınca, aşağıda bilgileri yer alan taraflar arasında elektronik ortamda kurulmuştur.</p>

<h2>MADDE 1 — TARAFLAR</h2>
<h3>1.1. SATICI</h3>
<p><strong>Unvan:</strong> [[firma_unvan]]<br>
<strong>Adres:</strong> [[firma_adres]]<br>
<strong>Vergi Dairesi / No:</strong> [[firma_vergi_dairesi]] / [[firma_vergi_no]]<br>
<strong>Telefon:</strong> [[firma_telefon]]<br>
<strong>E-posta:</strong> [[firma_eposta]]</p>
<h3>1.2. ALICI</h3>
<p>Sipariş sırasında belirtilen ad-soyad/unvan, adres ve iletişim bilgilerine sahip müşteri. Sipariş onayı ile ALICI, bu Sözleşme'nin tüm koşullarını okuyup kabul ettiğini beyan eder.</p>

<h2>MADDE 2 — TANIMLAR</h2>
<p><strong>Hizmet:</strong> SATICI tarafından sunulan web hosting, VPS, VDS, dedicated sunucu, domain (alan adı) tescili ve benzeri dijital hizmetler.<br>
<strong>Web Sitesi:</strong> {$site} ({$site} alan adı üzerinden yayın yapan elektronik satış platformu).<br>
<strong>Yönetmelik:</strong> Mesafeli Sözleşmeler Yönetmeliği.</p>

<h2>MADDE 3 — SÖZLEŞMENİN KONUSU</h2>
<p>İşbu Sözleşme'nin konusu, ALICI'nın Web Sitesi üzerinden elektronik ortamda siparişini verdiği, nitelikleri ve satış bedeli aşağıda ve sipariş özetinde belirtilen Hizmet'in satışı ve ifası ile ilgili olarak tarafların hak ve yükümlülüklerinin, 6502 sayılı Kanun ve Yönetmelik hükümleri uyarınca belirlenmesidir.</p>

<h2>MADDE 4 — SÖZLEŞME KONUSU HİZMET BİLGİLERİ</h2>
<p>Hizmet'in türü, kapsamı, süresi (aylık/yıllık vb.), KDV dahil toplam satış bedeli, ödeme şekli ve diğer tüm bilgiler, sipariş sırasında ALICI'ya gösterilen sipariş özetinde ve düzenlenen faturada yer alır. Sipariş özeti işbu Sözleşme'nin ayrılmaz bir parçasıdır.</p>

<h2>MADDE 5 — GENEL HÜKÜMLER</h2>
<p>5.1. ALICI, Web Sitesi'nde Hizmet'in temel nitelikleri, satış fiyatı, ödeme şekli ve ifaya ilişkin ön bilgileri okuyup doğru ve eksiksiz bilgi sahibi olduğunu, elektronik ortamda gerekli teyidi verdiğini kabul ve beyan eder.</p>
<p>5.2. Hizmet, ALICI'dan başka bir kişi/kuruluşa teslim edilecekse, teslim edilecek kişi/kuruluşun teslimatı kabul etmemesinden SATICI sorumlu tutulamaz.</p>
<p>5.3. SATICI, Sözleşme konusu Hizmet'in sağlam, eksiksiz ve sipariş özetinde belirtilen niteliklere uygun olarak ifasından sorumludur.</p>
<p>5.4. Hizmet bedelinin ALICI tarafından ödenmemesi veya banka/finans kuruluşu kayıtlarında iptal edilmesi halinde, SATICI'nın Hizmet'i ifa yükümlülüğü sona erer.</p>

<h2>MADDE 6 — ÖDEME</h2>
<p>Hizmet bedeli; kredi/banka kartı, banka havalesi/EFT veya Web Sitesi'nde sunulan diğer ödeme yöntemleriyle peşin olarak tahsil edilir. Kart bilgileri SATICI tarafından saklanmaz; ödeme işlemleri lisanslı ödeme kuruluşları üzerinden güvenli şekilde gerçekleştirilir.</p>

<h2>MADDE 7 — İFA VE TESLİM</h2>
<p>Dijital nitelikteki Hizmet'ler, ödeme onayının alınmasının ardından makul süre içinde (çoğunlukla anında veya kısa süre içinde) elektronik ortamda aktive edilerek ifa edilir. Alan adı tescili gibi üçüncü taraf kurumlara bağlı işlemlerde süre, ilgili kurumun işleyişine göre değişebilir.</p>

<h2>MADDE 8 — CAYMA HAKKI</h2>
<p>8.1. ALICI, sözleşmenin kurulmasından itibaren 14 (on dört) gün içinde herhangi bir gerekçe göstermeksizin ve cezai şart ödemeksizin cayma hakkına sahiptir.</p>
<p>8.2. Ancak Mesafeli Sözleşmeler Yönetmeliği'nin 15. maddesi uyarınca; <strong>elektronik ortamda anında ifa edilen hizmetler ile ALICI'nın onayı ile ifasına başlanan hizmetlerde cayma hakkı kullanılamaz.</strong> ALICI, Hizmet'in ifasına onayı ile başlanmasını talep ettiğinde, ifaya başlanmasının ardından cayma hakkını kaybedeceğini kabul eder.</p>
<p>8.3. Cayma hakkının geçerli olduğu hallerde, talebin SATICI'ya ulaşmasından itibaren 14 gün içinde ödeme iadesi yapılır. Detaylar için <a href="/sayfa/iade-iptal-ve-cayma-politikasi">İade, İptal ve Cayma Politikası</a> sayfasına bakınız.</p>

<h2>MADDE 9 — CAYMA HAKKININ KULLANILAMAYACAĞI HALLER</h2>
<ul>
<li>ALICI'nın onayıyla ifasına başlanan ve elektronik olarak anında ifa edilen hizmetler,</li>
<li>Alan adı (domain) tescili gibi üçüncü taraf kurumlara devredilen ve geri alınamayan işlemler,</li>
<li>Niteliği itibarıyla iade edilemeyecek dijital ürün ve hizmetler.</li>
</ul>

<h2>MADDE 10 — TEMERRÜT HÜKÜMLERİ</h2>
<p>ALICI'nın kredi kartı ile yaptığı ödemelerde temerrüde düşmesi halinde, kart sahibi banka ile yapmış olduğu sözleşme çerçevesinde faiz ödeyeceğini ve bankaya karşı sorumlu olacağını kabul eder.</p>

<h2>MADDE 11 — UYUŞMAZLIKLARIN ÇÖZÜMÜ</h2>
<p>İşbu Sözleşme'den doğabilecek uyuşmazlıklarda, Ticaret Bakanlığı'nca ilan edilen değer sınırları çerçevesinde ALICI'nın yerleşim yerindeki Tüketici Hakem Heyetleri ile Tüketici Mahkemeleri yetkilidir.</p>

<h2>MADDE 12 — YÜRÜRLÜK</h2>
<p>ALICI'nın elektronik ortamda siparişini onaylaması ile işbu Sözleşme yürürlüğe girer. ALICI, Sözleşme'nin tüm koşullarını okuduğunu, anladığını ve kabul ettiğini beyan eder.</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'iade-iptal-ve-cayma-politikasi',
                'title' => 'İade, İptal ve Cayma Politikası',
                'meta_title' => "İade, İptal ve Cayma Politikası — {$site}",
                'meta_description' => 'Hizmet iadesi, sipariş iptali ve cayma hakkının kullanımına ilişkin koşullar ve süreçler.',
                'sort_order' => 7,
                'content' => <<<HTML
<p class="lead">{$site} tarafından sunulan hizmetlere ilişkin iade, iptal ve cayma koşulları aşağıda düzenlenmiştir. İşbu politika, <a href="/sayfa/mesafeli-satis-sozlesmesi">Mesafeli Satış Sözleşmesi</a> ile bir bütün olarak değerlendirilir.</p>

<h2>1. Cayma Hakkı</h2>
<p>Tüketici, sözleşmenin kurulmasından itibaren 14 (on dört) gün içinde herhangi bir gerekçe göstermeksizin cayma hakkına sahiptir. Ancak aşağıda belirtilen hallerde, mevzuat gereği cayma hakkı kullanılamaz.</p>

<h2>2. Cayma Hakkının Kullanılamayacağı Haller</h2>
<ul>
<li>Müşterinin onayı ile ifasına başlanan ve elektronik ortamda anında ifa edilen hizmetler (örn. hosting/sunucu aktivasyonu yapılmışsa),</li>
<li>Alan adı (domain) tescili gibi üçüncü taraf kurumlara devredilen ve geri alınması mümkün olmayan işlemler,</li>
<li>Niteliği gereği iadesi mümkün olmayan dijital içerik ve hizmetler.</li>
</ul>

<h2>3. İptal ve İade Koşulları</h2>
<ul>
<li><strong>Hosting / Sunucu hizmetleri:</strong> Hizmet'in ifasına henüz başlanmamış (aktive edilmemiş) olması halinde iptal ve iade talebiniz değerlendirilir. Aktivasyon sonrası, kullanılmaya başlanan dönem için iade yapılamayabilir.</li>
<li><strong>Domain hizmetleri:</strong> Alan adı tescili tamamlandıktan sonra, işlem üçüncü taraf tescil kurumuna devredildiğinden iade yapılamaz.</li>
<li><strong>Yıllık ödemeler:</strong> İade uygun görülürse, kullanılan döneme ve varsa kampanya/indirim koşullarına göre orantılı kesinti yapılabilir.</li>
</ul>

<h2>4. İade Süreci ve Süresi</h2>
<p>Onaylanan iadeler, ALICI'nın ödeme yaptığı yöntem üzerinden gerçekleştirilir. İade tutarı, talebin onaylanmasından itibaren mevzuatta öngörülen süre içinde (en geç 14 gün) ilgili ödeme kuruluşuna iletilir. Kart iadelerinde bankanın işlem süresi tarafımızdan bağımsızdır.</p>

<h2>5. Başvuru</h2>
<p>İptal ve iade talepleriniz için [[firma_eposta]] adresine e-posta gönderebilir, <a href="/iletisim">İletişim</a> sayfamızı kullanabilir veya müşteri panelinizden destek talebi açabilirsiniz. Başvurunuzda sipariş numaranızı ve talebinizin gerekçesini belirtmeniz süreci hızlandırır.</p>

<h2>6. İletişim</h2>
<p><strong>Unvan:</strong> [[firma_unvan]]<br>
<strong>E-posta:</strong> [[firma_eposta]] — <strong>Telefon:</strong> [[firma_telefon]]</p>

<p><em>Son güncelleme: {$date}</em></p>
HTML,
            ],
            [
                'slug' => 'cerez-politikasi',
                'title' => 'Çerez (Cookie) Politikası',
                'meta_title' => "Çerez Politikası — {$site}",
                'meta_description' => 'Web sitemizde kullanılan çerez türleri, kullanım amaçları ve çerez tercihlerinizi yönetme yöntemleri.',
                'sort_order' => 8,
                'content' => <<<HTML
<p class="lead">Bu Çerez Politikası, {$site} web sitesinde çerezlerin nasıl kullanıldığını ve tercihlerinizi nasıl yönetebileceğinizi açıklar.</p>

<h2>1. Çerez Nedir?</h2>
<p>Çerezler (cookies), bir web sitesini ziyaret ettiğinizde tarayıcınız aracılığıyla cihazınıza kaydedilen küçük metin dosyalarıdır. Çerezler, sitenin düzgün çalışmasını sağlar ve kullanıcı deneyimini iyileştirir.</p>

<h2>2. Kullandığımız Çerez Türleri</h2>
<ul>
<li><strong>Zorunlu çerezler:</strong> Oturum yönetimi, güvenlik ve temel site işlevleri için gereklidir. Bu çerezler olmadan site düzgün çalışmaz.</li>
<li><strong>İşlevsel çerezler:</strong> Dil, tema (açık/koyu) gibi tercihlerinizi hatırlar.</li>
<li><strong>Performans/analitik çerezler:</strong> Ziyaretçilerin siteyi nasıl kullandığını anlamamıza yardımcı olur; bu sayede hizmetimizi iyileştiririz.</li>
<li><strong>Sepet/işlem çerezleri:</strong> Alışveriş sepetinizi ve sipariş sürecinizi sürdürebilmeniz için kullanılır.</li>
</ul>

<h2>3. Çerezlerin Kullanım Amaçları</h2>
<ul>
<li>Oturumunuzun ve verilerinizin güvenliğini sağlamak,</li>
<li>Tercihlerinizi hatırlayarak deneyiminizi kişiselleştirmek,</li>
<li>Site trafiğini ve performansını ölçmek,</li>
<li>Hizmet kalitesini sürekli iyileştirmek.</li>
</ul>

<h2>4. Çerez Tercihlerinin Yönetimi</h2>
<p>Tarayıcınızın ayarlarından çerezleri silebilir, engelleyebilir veya çerez yerleştirilmeden önce uyarı verilmesini sağlayabilirsiniz. Çerezleri engellemeniz halinde sitenin bazı bölümleri düzgün çalışmayabilir. Popüler tarayıcıların çerez ayarlarına, ilgili tarayıcının yardım/destek sayfalarından ulaşabilirsiniz.</p>

<h2>5. Üçüncü Taraf Çerezleri</h2>
<p>Sitemizde, analiz ve ödeme süreçleri gibi amaçlarla üçüncü taraf hizmet sağlayıcılarına ait çerezler kullanılabilir. Bu çerezler ilgili sağlayıcıların gizlilik politikalarına tabidir.</p>

<h2>6. İletişim</h2>
<p>Çerez kullanımına ilişkin sorularınız için [[firma_eposta]] adresinden bize ulaşabilir; kişisel verilerinizle ilgili detaylar için <a href="/sayfa/gizlilik">Gizlilik Politikası</a> ve <a href="/sayfa/kvkk">KVKK Aydınlatma Metni</a> sayfalarımızı inceleyebilirsiniz.</p>

<p><em>Son güncelleme: {$date}</em></p>
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
