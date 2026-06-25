<?php

namespace Database\Seeders;

/**
 * Panelze yasal sayfa metinleri — panelze.com ürün ve altyapısına göre.
 */
final class PanelzeLegalContent
{
  /** @return object{sirket: string, adres: string, eposta: string, web: string, bolge: string} */
    public static function metaTr(): object
    {
        return (object) [
            'sirket' => 'Panelze',
            'adres' => 'İstanbul, Türkiye — ayrıntılı ticari unvan ve adres fatura/sipariş belgelerinde yer alır.',
            'eposta' => 'destek@panelze.com',
            'web' => 'https://panelze.com',
            'bolge' => 'Almanya (AB / GDPR uyumlu bölge, Contabo GmbH altyapısı)',
        ];
    }

    /** @return object{company: string, addr: string, mail: string, web: string, region: string} */
    public static function metaEn(): object
    {
        return (object) [
            'company' => 'Panelze',
            'addr' => 'Istanbul, Türkiye — full legal entity and address appear on invoices and order documents.',
            'mail' => 'destek@panelze.com',
            'web' => 'https://panelze.com',
            'region' => 'Germany (EU / GDPR-aligned region, Contabo GmbH infrastructure)',
        ];
    }

    /**
     * @return object{kvkk: string, gizlilik: string, cerez: string, mesafeli: string, kullanim: string, sla: string, iade: string, veri: string, musteri: string, sss: string}
     */
    public static function tr(): object
    {
        $m = self::metaTr();
        $s = $m->sirket;
        $a = $m->adres;
        $e = $m->eposta;
        $w = $m->web;
        $b = $m->bolge;

        return (object) [
            'kvkk' => <<<MD
## Veri sorumlusu

**{$s}** (“Şirket”), [panelze.com]({$w}) üzerinden sunulan hosting kontrol paneli yazılımı, lisans yönetimi, dokümantasyon ve müşteri destek hizmetleri kapsamında **6698 sayılı Kişisel Verilerin Korunması Kanunu** (“KVKK”) uyarınca veri sorumlusudur.

| Alan | Bilgi |
| --- | --- |
| Marka / hizmet | Panelze hosting kontrol paneli (Community & Pro) |
| Web | {$w} |
| İletişim | [{$e}](mailto:{$e}) |
| Adres | {$a} |

## Kapsam

Bu aydınlatma metni; web sitemizi ziyaret etmeniz, demo veya kurulum talep etmeniz, **panelze.com** üzerinden lisans satın almanız, müşteri paneline giriş yapmanız, destek bileti açmanız ve yazılımın lisans doğrulama API’si aracılığıyla sunucunuzdan hub’a yapılan teknik çağrıları kapsar.

## İşlenen kişisel veri kategorileri

- **Kimlik ve iletişim:** Ad, soyad, unvan, e-posta, telefon (varsa), fatura adresi.
- **Müşteri işlem:** Sipariş numarası, lisans anahtarı özeti, ödeme durumu (kart verisi **Stripe** üzerinde işlenir; tam kart numarası Panelze’de tutulmaz), fatura kayıtları.
- **Hesap ve güvenlik:** Müşteri paneli oturum bilgileri, parola özeti (hash), iki adımlı doğrulama kayıtları (etkinse).
- **Teknik veriler:** IP adresi, tarayıcı türü, cihaz bilgisi, erişim tarih-saati, hata ve güvenlik logları.
- **Destek:** Ticket içeriği, ek dosyalar, yazışma geçmişi.
- **Pazarlama (yalnızca açık rıza ile):** Bülten aboneliği, kampanya tercihleri.

Kurulu panel yazılımı **müşterinin kendi sunucusunda** çalışır; müşterinin son kullanıcılarına ait veriler için veri sorumlusu genellikle paneli işleten müşteridir. Panelze, hub ve lisans hizmetleri için yalnızca sınırlı teknik metadata işleyebilir.

## İşleme amaçları

1. Lisans ve abonelik sözleşmesinin kurulması ve ifası.
2. Ödeme, faturalandırma ve muhasebe yükümlülükleri.
3. Teknik destek ve güvenlik (kötüye kullanım, sahte lisans tespiti).
4. Ürün geliştirme ve anonim/istatistiksel analiz.
5. Yasal yükümlülüklerin yerine getirilmesi.
6. Açık rızanız halinde pazarlama iletişimi.

## Hukuki sebepler

KVKK m.5/2: (c) sözleşmenin kurulması veya ifası; (ç) veri sorumlusunun hukuki yükümlülüğü; (e) bir hakkın tesisi, kullanılması veya korunması; (f) meşru menfaat (güvenlik, dolandırıcılık önleme); (a) açık rıza (pazarlama çerezleri ve bülten).

## Aktarım ve alt işlemciler

Hizmetin gereği ile sınırlı olarak:

| Alt işlemci | Amaç |
| --- | --- |
| **Contabo GmbH** ({$b}) | Web, API, veritabanı barındırma |
| **Stripe** | Ödeme işleme |
| E-posta sağlayıcısı | Fatura ve destek bildirimleri |

Yurt dışına aktarımda KVKK m.9 kapsamındaki şartlar (açık rıza, yeterlilik kararı veya taahhütname) uygulanır.

## Saklama süreleri

- Sözleşme ve fatura kayıtları: ilgili vergi ve ticaret mevzuatı süreleri (genelde **10 yıl**).
- Destek kayıtları: talebin kapanmasından itibaren **3 yıl** (uyuşmazlık riski için).
- Güvenlik logları: **12 ay** (veya mevzuatın öngördüğü süre).
- Pazarlama izinleri: rıza geri çekilene kadar.

Süre sonunda veriler silinir, yok edilir veya anonimleştirilir.

## Haklarınız (KVKK m.11)

Kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltme, silme, anonimleştirme, aktarılan üçüncü kişileri bilme, otomatik sistemlere itiraz ve zararın giderilmesini talep etme haklarına sahipsiniz.

**Başvuru:** [{$e}](mailto:{$e}) — kimliğinizi doğrulamak için ek bilgi istenebilir. Başvurular en geç **30 gün** içinde sonuçlandırılır. Şikâyet için **Kişisel Verileri Koruma Kurulu**’na başvuru hakkınız saklıdır.

**Son güncelleme:** {DATE}
MD,
            'gizlilik' => <<<MD
Bu Gizlilik Politikası, **Panelze** markası altında [panelze.com]({$w}) sitesi, müşteri paneli, lisans hub API’si ve ilişkili dijital hizmetler için geçerlidir.

## Topladığımız bilgiler

**Doğrudan sizden:** Kayıt formları, sipariş, destek talepleri, iletişim formları, bülten aboneliği.

**Otomatik olarak:** Sunucu erişim logları, çerezler (ayrıntı için [Çerez Politikası](/p/cerez-politikasi)), cihaz ve tarayıcı bilgisi, sayfa görüntüleme istatistikleri.

**Ödeme:** Kredi/banka kartı işlemleri **Stripe** altyapısı üzerinden gerçekleştirilir. Panelze tam kart numaranızı saklamaz.

## Bilgileri nasıl kullanıyoruz?

- Panelze Community/Pro lisansının sağlanması ve doğrulanması.
- Hesap yönetimi, faturalandırma ve müşteri desteği.
- Güvenlik izleme, kötüye kullanım ve sahte lisans önleme.
- Ürün iyileştirme (mümkün olduğunda anonim/aggregate veri).
- Yasal yükümlülükler ve resmi makam talepleri.

## Paylaşım

Kişisel verileriniz yalnızca hizmetin sunulması için gerekli ölçüde barındırma, ödeme, e-posta ve analitik sağlayıcılarıyla paylaşılır. Veriler üçüncü taraflara **satılmaz**.

## Güvenlik

TLS şifreleme, erişim kontrolü, parola hash’leme ve düzenli yedekleme uygulanır. İnternet üzerinden hiçbir iletim veya depolama yöntemi %100 güvenli değildir; makul önlemler alınır.

## Çocuklar

Hizmetlerimiz 18 yaş altına yönelik değildir; bilerek çocuklardan veri toplamıyoruz.

## Uluslararası aktarım

Birincil barındırma **{$b}** bölgesindedir. AB dışından erişimde geçerli koruma mekanizmaları uygulanır.

## Haklarınız ve iletişim

KVKK kapsamındaki talepleriniz için [{$e}](mailto:{$e}). Politika güncellenebilir; önemli değişiklikler sitede duyurulur.

**Son güncelleme:** {DATE}
MD,
            'cerez' => <<<MD
## Çerez nedir?

Çerezler, ziyaret ettiğiniz web sitesi tarafından tarayıcınıza kaydedilen küçük metin dosyalarıdır. Oturumunuzu sürdürmek, tercihlerinizi hatırlamak ve site kullanımını anlamak için kullanılır.

## Panelze’de kullandığımız çerezler

| Tür | Amaç | Süre | Zorunlu |
| --- | --- | --- | --- |
| Oturum (`session`) | Müşteri paneli ve admin girişi | Oturum | Evet |
| CSRF | Form güvenliği | Oturum | Evet |
| Dil (`locale`) | TR/EN tercihi | 1 yıl | İşlevsel |
| Tema (`theme`) | Açık/koyu mod | 1 yıl | İşlevsel |
| Analitik (varsa) | Anonim trafik istatistikleri | 13 ay | Hayır — rıza |

**Pazarlama çerezleri** şu an varsayılan olarak kullanılmamaktadır. İleride eklenirse yalnızca açık rıza ile etkinleştirilir.

## Üçüncü taraf çerezleri

- **Stripe** (ödeme sayfası): dolandırıcılık önleme ve ödeme oturumu.
- Barındırma/CDN sağlayıcıları: teknik performans.

## Çerezleri yönetme

Tarayıcı ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezleri devre dışı bırakmak giriş ve ödeme işlemlerini engelleyebilir.

**İletişim:** [{$e}](mailto:{$e})

**Son güncelleme:** {DATE}
MD,
            'mesafeli' => <<<MD
## 1. Taraflar

**SATICI**

| | |
| --- | --- |
| Unvan / marka | {$s} |
| Adres | {$a} |
| E-posta | [{$e}](mailto:{$e}) |
| Web | {$w} |

**ALICI:** Sipariş sırasında bildirdiği bilgilerle tanımlanan gerçek veya tüzel kişi (“Müşteri”).

## 2. Konu

İşbu sözleşme, ALICI’nın SATICI’ya ait internet sitesi üzerinden elektronik ortamda siparişini verdiği **Panelze hosting kontrol paneli yazılım lisansı** (Community ücretsiz kullanım veya Pro ücretli lisans/abonelik) ve ilişkili dijital hizmetlerin satışına ilişkin **6502 sayılı Tüketicinin Korunması Hakkında Kanun** ve **Mesafeli Sözleşmeler Yönetmeliği** hükümleri uyarınca tarafların hak ve yükümlülüklerini düzenler.

## 3. Ürün ve hizmet özellikleri

- **Community:** Sunucu başına en fazla 5 site; temel panel özellikleri; ücretsiz.
- **Pro:** Yükseltilmiş site limiti ve ücretli modüller (phpMyAdmin SSO, gelişmiş güvenlik, Drive yedek, izleme, AI danışman vb. — planda belirtilenler).
- Lisans anahtarı e-posta veya müşteri panelinde teslim edilir; kurulum müşterinin kendi VPS’inde gerçekleştirilir.

## 4. Fiyat ve ödeme

Fiyatlar sipariş anında sitede gösterilen tutarlardır; **KDV dahil** veya hariç olduğu ödeme ekranında açıkça belirtilir. Ödeme **Stripe** veya duyurulan diğer yöntemlerle alınır.

## 5. Teslimat

Dijital ürün; ödeme onayı sonrası lisans anahtarı ve kurulum talimatları **anında** elektronik ortamda teslim edilir. Fiziksel gönderim yapılmaz.

## 6. Cayma hakkı

Tüketici işlemlerinde, Mesafeli Sözleşmeler Yönetmeliği m.15/ğ uyarınca **elektronik ortamda anında ifa edilen** dijital içeriklerde, tüketicinin onayı ile ifaya başlandığında cayma hakkı **kullanılamayabilir**. Sipariş öncesi bu durum onay kutusu ile açıkça kabul ettirilir.

Kurumsal (B2B) siparişlerde tarafların yazılı sözleşmesi geçerlidir.

## 7. Garanti ve destek

Yazılım “olduğu gibi” sunulur; SLA ve destek kapsamı [Hizmet Seviyesi](/p/sla) sayfasında özetlenir. Kritik güvenlik yamaları makul sürede yayınlanır.

## 8. Uyuşmazlık

Tüketici işlemlerinde parasal sınırlara göre **Tüketici Hakem Heyeti** veya **Tüketici Mahkemeleri** yetkilidir.

**Son güncelleme:** {DATE}
MD,
            'kullanim' => <<<MD
## 1. Kabul

[panelze.com]({$w}) sitesini, dokümantasyonu, müşteri panelini ve **Panelze** hosting kontrol paneli yazılımını kullanarak bu Kullanım Koşullarını kabul etmiş sayılırsınız.

## 2. Hizmet tanımı

Panelze; Linux sunucularda web sitesi, DNS, SSL, veritabanı, e-posta ve ilgili servisleri yönetmek için **Panel (Laravel)** ve **Engine (Go)** bileşenlerinden oluşan bir kontrol panelidir. Community sürümü ücretsizdir; Pro sürümü lisans anahtarı ile ek modüller açar.

## 3. Lisans hakları

- Yazılım, satın alınan veya Community kapsamındaki lisans türüne göre **sunucu başına** kullanılır.
- Kaynak kodun izinsiz kopyalanması, dağıtılması, tersine mühendisliği veya lisans kontrolünün atlatılması **yasaktır**.
- Community limitleri (ör. site sayısı) aşılamaz; aşım tespitinde lisans uyarısı veya kısıtlama uygulanabilir.

## 4. Kabul edilebilir kullanım

Aşağıdakiler kesinlikle yasaktır:

- Yasa dışı içerik barındırma, spam, phishing, kötü amaçlı yazılım dağıtımı.
- İzinsiz güvenlik taraması veya başkalarının sistemlerine saldırı.
- Panelze altyapısına veya lisans hub’ına aşırı yük veya otomatik kötüye kullanım.
- Sahte veya paylaşılmış lisans anahtarı kullanımı.

İhlal halinde hizmet askıya alınabilir, lisans iptal edilebilir ve gerekirse hukuki yollara başvurulur.

## 5. Hesap güvenliği

Müşteri paneli kimlik bilgilerinizin gizliliğinden siz sorumlusunuz. Şüpheli erişimde derhal [{$e}](mailto:{$e}) ile iletişime geçin.

## 6. Fikri mülkiyet

Panelze adı, logosu, arayüz tasarımı ve dokümantasyonu Şirket’e aittir. Açık kaynak bileşenler kendi lisanslarına tabidir.

## 7. Sorumluluk sınırı

Yazılım ve hub hizmetleri mevzuatın izin verdiği ölçüde “olduğu gibi” sunulur. Dolaylı zarar, veri kaybı (müşteri yedekleme yükümlülüğü hariç) ve üçüncü taraf kesintilerinden doğan zararlarda sorumluluk, ödenen lisans bedeli ile sınırlı olabilir.

## 8. Değişiklikler

Koşullar güncellenebilir; yürürlük tarihi sayfa altında belirtilir. Önemli değişiklikler e-posta veya site bildirimi ile duyurulur.

**Son güncelleme:** {DATE}
MD,
            'sla' => <<<MD
Bu belge, **Panelze** lisans hub’ı ([panelze.com]({$w})) ve resmi destek kanalları için hedeflenen hizmet seviyesini özetler. Müşterinin kendi sunucusunda kurulu panelin erişilebilirliği müşterinin barındırma sağlayıcısına bağlıdır.

## 1. Kapsam

| Hizmet | Dahil | Hariç |
| --- | --- | --- |
| panelze.com web sitesi | Evet | — |
| Lisans doğrulama API | Evet | Müşteri sunucusu internet kesintisi |
| Müşteri paneli / ödeme | Evet | Stripe kesintileri |
| Kurulu panel (VPS) | — | Müşteri altyapısı |

## 2. Erişilebilirlik hedefi

- **Aylık uptime hedefi:** %99,5 (planlı bakım hariç).
- Ölçüm: hub ve web ön yüzü için dış izleme.
- Planlı bakım: mümkün olduğunda **Pazar 02:00–06:00 (UTC+3)** arası; en az **24 saat** önceden status veya e-posta ile duyuru.

## 3. Destek

| Kanal | İlk yanıt hedefi (iş günü) |
| --- | --- |
| [{$e}](mailto:{$e}) | 24 saat |
| Kritik güvenlik (hub) | 8 saat |

Destek dili: Türkçe ve İngilizce. Sunucu içi panel yapılandırması ve müşteri VPS sorunları “en iyi çaba” kapsamındadır.

## 4. Güncellemeler

Güvenlik yamaları önceliklidir. Community ve Pro kurulum betikleri [Kurulum](/setup) sayfasında yayınlanır.

## 5. Tazminat

SLA ihlali nedeniyle hizmet kredisi veya para iadesi yalnızca **yazılı kurumsal sözleşmede** açıkça tanımlanmışsa uygulanır. Standart Pro/Community lisanslarında otomatik kredi yoktur.

## 6. Mücbir sebep

Doğal afet, savaş, geniş çaplı internet kesintisi, barındırıcı arızası ve benzeri durumlar SLA hesabından düşülür.

**Son güncelleme:** {DATE}
MD,
            'iade' => <<<MD
## 1. Genel ilkeler

Panelze **dijital lisans** ve abonelik ürünleri satmaktadır. İade ve iptal kuralları ürün tipine (Community ücretsiz, Pro aylık/yıllık, kurumsal sözleşme) ve **6502 sayılı Kanun** ile Mesafeli Sözleşmeler Yönetmeliği’ne göre uygulanır.

## 2. Community (ücretsiz)

Ücret alınmadığı için iade söz konusu değildir. Hesap silme talepleri [{$e}](mailto:{$e}) üzerinden iletilir.

## 3. Pro lisans — tüketici (B2C)

- Dijital içerik **anında teslim** edildiğinde ve siparişte onay kutusu ile cayma hakkından feragat edildiğinde **14 günlük cayma hakkı kullanılamayabilir**.
- Lisans anahtarı **kullanılmamış** ve hub’da aktivasyon yapılmamışsa, talep tarihinden itibaren **14 gün** içinde koşulsuz iade değerlendirilir.
- Kısmen kullanılmış dönemlerde **orantılı iade** veya iade reddi uygulanabilir.

## 4. Abonelik iptali

Aylık/yıllık Pro abonelikler dönem sonuna kadar geçerlidir. İptal, müşteri panelinden veya e-posta ile yapılır; **otomatik yenileme** bir sonraki dönem için durdurulur. Geçmiş dönem ücretleri iade edilmez (aksi sözleşmede yazılı değilse).

## 5. Kurumsal (B2B)

Cayma hakkı bulunmayan iş sözleşmelerinde iptal ve iade, imzalanan sözleşme hükümlerine tabidir.

## 6. İade süreci

1. [{$e}](mailto:{$e}) adresine sipariş numarası ve gerekçe ile başvurun.
2. Uygunluk **5 iş günü** içinde değerlendirilir.
3. Onaylanan iadeler **14 iş günü** içinde ödeme yapılan yönteme (genelde Stripe üzerinden) iade edilir; banka süreleri ek süre gerektirebilir.

## 7. İade edilmeyen durumlar

- Kullanılmış ve hub’da doğrulanmış lisans anahtarı (istisna: mevzuat zorunluluğu).
- Özel geliştirme veya kurulum hizmeti (sözleşmede aksi belirtilmedikçe).

**Son güncelleme:** {DATE}
MD,
            'veri' => <<<MD
## 1. Özet

Panelze lisans hub’ı, müşteri paneli ve web sitesi için üretim verileri öncelikle **{$b}** bölgesinde barındırılır.

## 2. Birincil lokasyon

| Bileşen | Konum |
| --- | --- |
| panelze.com (web + API) | Almanya, AB |
| Veritabanı (lisans, sipariş) | Aynı bölge |
| Yedekler | Şifreli, coğrafi olarak yakın ikincil depolama |

Müşterinin kendi VPS’ine kurduğu **Panelze paneli** verileri müşterinin seçtiği sunucuda kalır; Panelze bu verilere yalnızca müşteri yapılandırması ve destek kapsamında erişir.

## 3. Alt işlemciler

| Sağlayıcı | Hizmet |
| --- | --- |
| **Contabo GmbH** | VPS / barındırma |
| **Stripe** | Ödeme işleme (ABD/EU) |
| E-posta SMTP sağlayıcısı | İşlem bildirimleri |

Güncel liste ve DPA talepleri: [{$e}](mailto:{$e})

## 4. Güvenlik önlemleri

- TLS 1.2+ ile veri aktarımı.
- Veritabanı ve yedeklerde şifreleme (disk düzeyi veya uygulama).
- SSH anahtarı ve sınırlı IP ile sunucu erişimi.
- Düzenli güvenlik güncellemeleri ve log izleme.

## 5. Veri yerelliği talepleri

Kurumsal müşteriler için AB dışı aktarım kısıtları ve ek DPA müzakere edilebilir.

**Son güncelleme:** {DATE}
MD,
            'musteri' => <<<MD
## 1. Taraflar

**Sağlayıcı:** {$s} — [{$e}](mailto:{$e}), {$w}  
**Müşteri:** Sipariş formunu veya bu sözleşmeyi onaylayan gerçek/tüzel kişi.

## 2. Tanımlar

- **Yazılım:** Panelze hosting kontrol paneli (Panel + Engine).
- **Hub:** panelze.com üzerindeki lisans doğrulama ve müşteri yönetim servisi.
- **Lisans:** Community veya Pro kullanım hakkı.

## 3. Hizmetin kapsamı

Sağlayıcı, Müşteri’ye yazılım lisansı, hub erişimi, dokümantasyon ve planda belirtilen destek kanallarını sunar. Kurulum ve günlük işletim Müşteri’nin sunucusunda gerçekleşir.

## 4. Müşteri yükümlülükleri

- Sunucu güvenliği, yedekleme ve yasalara uygun içerik.
- Lisans anahtarının gizliliği.
- Güncellemelerin makul sürede uygulanması.

## 5. Ücret ve ödeme

Plan veya teklifteki fiyatlar geçerlidir. Gecikmede faiz ve askıya alma hakkı saklıdır.

## 6. Fikri mülkiyet

Yazılım lisansı devredilir; mülkiyet Sağlayıcı’da kalır.

## 7. Gizlilik

Kişisel veriler [KVKK](/p/kvkk) ve [Gizlilik](/p/gizlilik-politikasi) politikalarına uygun işlenir.

## 8. Süre ve fesih

Aboneliklerde dönem sonu iptali; ciddi ihlalde derhal fesih. Fesih sonrası hub erişimi kesilir; sunucudaki yazılım Community limitlerine dönebilir.

## 9. Uygulanacak hukuk

**Türkiye Cumhuriyeti hukuku**; İstanbul Mahkemeleri ve İcra Daireleri yetkilidir (tüketici işlemlerinde tüketici mahkemesi önceliklidir).

**Son güncelleme:** {DATE}
MD,
            'sss' => <<<MD
Panelze hosting kontrol paneli hakkında sık sorulan sorular.

## Community limitleri nelerdir?

Community sürümü sunucu başına **en fazla 5 site** ile tam panel özelliklerini içerir (Nginx/Apache, PHP, SSL, DNS, veritabanı, posta vb.). Pro sürümü site limitini yükseltir (ör. 500) ve ücretli modülleri açar.

## Pro lisans nasıl doğrulanır?

Kurulu panel, **panelze.com** lisans hub’ına güvenli HTTPS API ile bağlanır. Anahtarınızı **Yönetim → Lisans** ekranından girin; plandaki modüller doğrulama yanıtında döner. Sunucu internete çıkamıyorsa offline senaryolar için destek ile iletişime geçin.

## Community ve Pro aynı kurulum mu?

Evet. İkisi de aynı paketi kurar. Fark, Pro kurulumda `PANELZE_LICENSE_KEY` ortam değişkeni ve hub’ın açtığı modüllerdir. Ana sayfadaki [kurulum komutları](/#docs) veya [Kurulum rehberi](/setup) sayfasına bakın.

## Hangi işletim sistemleri desteklenir?

**Debian/Ubuntu** (22.04 LTS önerilir). Kurulum betikleri root veya sudo ile çalıştırılır.

## Ödeme nasıl yapılır?

Pro lisans ve abonelikler **Stripe** üzerinden güvenli ödeme ile alınır. Fatura e-posta ile iletilir.

## Verilerim nerede tutulur?

Lisans ve sipariş verileri **Almanya (AB)** bölgesindeki sunucularda tutulur. Detay: [Veri merkezi](/p/veri-merkezi).

## Destek nasıl alınır?

[{$e}](mailto:{$e}) — iş günlerinde ilk yanıt hedefi 24 saattir. Kritik hub güvenlik konularında öncelik verilir.

## İade yapılır mı?

Dijital lisans koşulları [İade ve iptal](/p/iade-ve-iptal) sayfasında açıklanmıştır.

**Son güncelleme:** {DATE}
MD,
        ];
    }

    /**
     * @return object{kvkk: string, gizlilik: string, cerez: string, mesafeli: string, kullanim: string, sla: string, iade: string, veri: string, musteri: string, sss: string}
     */
    public static function en(): object
    {
        $m = self::metaEn();
        $c = $m->company;
        $a = $m->addr;
        $e = $m->mail;
        $w = $m->web;
        $r = $m->region;

        return (object) [
            'kvkk' => <<<MD
## Data controller

**{$c}** (“we”, “us”) is the controller of personal data for [panelze.com]({$w}), the Panelze hosting control panel (Community & Pro), license hub, documentation, and customer support.

| Field | Details |
| --- | --- |
| Service | Panelze hosting control panel |
| Website | {$w} |
| Contact | [{$e}](mailto:{$e}) |
| Address | {$a} |

## Scope

This notice covers website visits, purchases, customer panel access, support tickets, and technical license-validation calls from your server to our hub.

## Categories of data

Identity/contact, order and billing metadata, account security logs, support content, technical logs (IP, user agent), and—with consent—marketing preferences. Card data is processed by **Stripe**; we do not store full card numbers.

Software installed on **your server** is generally your responsibility as controller for your end users’ data.

## Purposes and legal bases

Contract performance, legal obligations, security, product improvement (aggregated), and consent where required.

## Recipients

Hosting (**Contabo GmbH**, {$r}), **Stripe** (payments), and email providers under appropriate agreements. Transfers outside your country follow applicable safeguards.

## Retention

Invoices ~10 years; support ~3 years after closure; security logs ~12 months unless law requires longer.

## Your rights

Access, rectification, erasure, restriction, objection, and complaint to your supervisory authority. Contact **[{$e}](mailto:{$e})**.

**Last updated:** {DATE}
MD,
            'gizlilik' => <<<MD
This Privacy Policy applies to **Panelze** at [panelze.com]({$w}), the customer panel, license API, and related services.

## What we collect

Information you submit (orders, support), automatic logs and cookies ([Cookie policy](/p/cerez-politikasi)), and payment metadata via **Stripe**.

## How we use it

License delivery, billing, support, security, aggregated analytics, and legal compliance. We do **not** sell personal data.

## Security

TLS, access controls, password hashing, and backups. No method is 100% secure.

## International transfers

Primary hosting in **{$r}**.

## Contact

[{$e}](mailto:{$e})

**Last updated:** {DATE}
MD,
            'cerez' => <<<MD
## What are cookies?

Small text files stored by your browser.

## Cookies we use

| Type | Purpose | Required |
| --- | --- | --- |
| Session | Login to customer/admin panel | Yes |
| CSRF | Form security | Yes |
| Locale / theme | Language and dark mode | Functional |
| Analytics (if enabled) | Aggregated traffic | Consent |

Marketing cookies are not enabled by default.

## Managing cookies

Browser settings can block or delete cookies; required cookies may break login and checkout.

**Contact:** [{$e}](mailto:{$e})

**Last updated:** {DATE}
MD,
            'mesafeli' => <<<MD
## Parties

**Seller:** {$c}, {$a}, [{$e}](mailto:{$e}), {$w}  
**Buyer:** The person or entity identified in the order.

## Subject

Online sale of **Panelze** software licenses (Community free tier or paid Pro) and related digital services.

## Product

Community (up to 5 sites/server, free) and Pro (higher limits and paid modules). License keys are delivered electronically after payment.

## Price and payment

As shown at checkout including applicable taxes. Payments via **Stripe**.

## Delivery

Instant digital delivery; no physical shipment.

## Withdrawal

For consumers, cooling-off may not apply to instantly delivered digital content once performance started with your consent, as required by local law.

## Disputes

Consumer arbitration/courts as applicable in your jurisdiction.

**Last updated:** {DATE}
MD,
            'kullanim' => <<<MD
## Acceptance

Using [panelze.com]({$w}) and Panelze software means you accept these Terms.

## Service

Linux hosting control panel (Laravel Panel + Go Engine). Community is free; Pro unlocks modules via license key.

## License

Per-server use only. No reverse engineering, redistribution, or license circumvention.

## Acceptable use

No illegal content, spam, intrusion attempts, or shared/fake license keys. We may suspend or terminate for breach.

## Liability

Provided as available; liability limited to the extent permitted by law.

## Changes

Terms may be updated; publication date shown below.

**Last updated:** {DATE}
MD,
            'sla' => <<<MD
## Scope

| Service | Covered |
| --- | --- |
| panelze.com & license API | Yes |
| Your VPS-installed panel | Your infrastructure |

## Availability target

**99.5%** monthly uptime excluding scheduled maintenance (typically Sunday 02:00–06:00 UTC+3 with 24h notice).

## Support

[{$e}](mailto:{$e}) — first response within **24 business hours**; critical hub security issues prioritized.

## Credits

Service credits only if explicitly stated in a signed enterprise agreement.

**Last updated:** {DATE}
MD,
            'iade' => <<<MD
## Overview

Digital license and subscription refunds follow product type and applicable consumer law.

## Community

Free — no refunds.

## Pro (consumers)

Cooling-off may not apply once digital delivery started with consent. Unused, non-activated keys may qualify for refund within **14 days**.

## Subscriptions

Cancel before renewal via panel or email; past periods are non-refundable unless agreed in writing.

## Process

Email [{$e}](mailto:{$e}) with order ID. Approved refunds within **14 business days** to the original payment method.

**Last updated:** {DATE}
MD,
            'veri' => <<<MD
## Summary

Production data for the Panelze hub is hosted primarily in **{$r}**.

## Components

Web, API, and license database in Germany (EU). Encrypted backups in a nearby region.

Software on **your VPS** stays on your server.

## Subprocessors

**Contabo GmbH** (hosting), **Stripe** (payments), email provider. Contact [{$e}](mailto:{$e}) for DPA requests.

## Security

TLS, encryption, access controls, and monitoring.

**Last updated:** {DATE}
MD,
            'musteri' => <<<MD
## Parties

**Provider:** {$c} — [{$e}](mailto:{$e})  
**Customer:** The entity accepting the order.

## Service

Software license, hub access, documentation, and support per plan. Installation and day-to-day ops on Customer's server.

## Fees

Per order or subscription; late payment may trigger suspension.

## Termination

End of subscription period or immediate for material breach.

## Governing law

Laws of the Republic of Türkiye; Istanbul courts (consumer courts where applicable).

**Last updated:** {DATE}
MD,
            'sss' => <<<MD
Frequently asked questions about the Panelze hosting control panel.

## What are Community limits?

Up to **5 sites per server** with full panel features. Pro raises limits and unlocks paid modules.

## How is Pro verified?

The panel calls the **panelze.com** license hub over HTTPS. Enter your key under **Admin → License**.

## Same installer for Community and Pro?

Yes. Pro adds `PANELZE_LICENSE_KEY` and hub-enabled modules. See [installation](/#docs) or the [setup guide](/setup).

## Supported OS?

**Debian/Ubuntu** (22.04 LTS recommended), run as root/sudo.

## Payment?

Pro via **Stripe**. Invoices by email.

## Where is data stored?

Hub data in **Germany (EU)**. See [Data centre](/p/veri-merkezi).

## Support?

[{$e}](mailto:{$e}) — 24 business-hour first-response target.

## Refunds?

See [Refunds & cancellation](/p/iade-ve-iptal).

**Last updated:** {DATE}
MD,
        ];
    }
}
