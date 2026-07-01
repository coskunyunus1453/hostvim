## WordPress web’in üçte birini çalıştırıyor

WordPress, internetteki sitelerin yaklaşık %43’ünü güçlendiriyor — bloglardan kurumsal portallara, e-ticaretten üyelik sitelerine kadar geniş bir yelpaze. Bu popülerlik hem avantaj hem risk: geniş eklenti ekosistemi, topluluk desteği ve kolay kurulum sunarken; güvenlik hedefi olma ve yanlış hosting seçiminde performans sorunları da beraberinde geliyor.

“WordPress hosting” dediğinizde çoğu kişi paylaşımlı hosting paketlerini düşünür. Oysa VPS üzerinde kendi panelinizle WordPress çalıştırmak, hem maliyet hem kontrol hem de ölçeklenebilirlik açısından 2026’da ciddi bir alternatif. Bu rehberde doğru hosting seçimi, kurulum, güvenlik ve performans adımlarını pratik bir kontrol listesiyle ele alıyoruz.

## Paylaşımlı hosting mi, VPS mi?

### Paylaşımlı hosting

Aylık birkaç dolarla başlarsınız; cPanel, tek tık WordPress kurulumu ve sınırlı destek dahildir. Küçük bloglar ve düşük trafikli siteler için yeterlidir. Dezavantajları: komşu site etkisi (noisy neighbor), PHP sürümü kısıtları, özelleştirme sınırları ve trafik arttığında ani yükseltme maliyetleri.

### VPS + hosting paneli

Sunucu kaynakları size ayrılmıştır. PHP sürümünü, önbelleği, SSL’i ve yedeklemeyi siz yönetirsiniz — panel sayesinde teknik derinlik isteğe bağlıdır. Panelze gibi paneller WordPress kurulumunu dakikalara indirir. [VPS panel seçim rehberi](/blog/vps-hosting-paneli-nasil-secilir) bu geçişi anlatır.

**Kural:** Günlük 500’den fazla ziyaretçi, WooCommerce veya çoklu site planlıyorsanız VPS düşünmeye değer.

## WordPress hosting seçerken teknik kriterler

### PHP sürümü: 8.2 veya 8.3

PHP 7.4 ve 8.0 desteği sona erdi. WordPress 6.x, PHP 8.2 ve 8.3 ile uyumludur; performans kazancı %15–30 civarında olabilir. Eski eklentiler uyumsuzluk çıkarabilir; staging ortamında test şart.

Panel üzerinden site başına PHP sürümü seçmek büyük avantajdır: aynı sunucuda bir site PHP 8.3, diğeri geçiş sürecinde 8.1 çalışabilir. [Kurulum sonrası yapılandırma](/docs/post-install) PHP ayarlarını kapsar.

### Web sunucusu ve önbellek

Nginx + PHP-FPM, WordPress için yaygın ve verimli bir kombinasyondur. OPcache PHP bytecode önbelleği sunar; her istekte PHP dosyalarının yeniden derlenmesini önler. `opcache.enable=1` ve yeterli `opcache.memory_consumption` (128 MB+) önerilir.

Sayfa önbelleği için:

- **Eklenti tabanlı:** WP Super Cache, W3 Total Cache, LiteSpeed Cache (LiteSpeed sunucuda).
- **Sunucu tabanlı:** Nginx fastcgi_cache veya Redis object cache.

Redis, veritabanı sorgularını ve oturum verilerini bellekte tutar; yoğun WooCommerce sitelerinde fark yaratır.

### Veritabanı

MariaDB 10.6+ veya MySQL 8.0+ tercih edin. `innodb_buffer_pool_size` sunucu RAM’inin %50–70’i kadar ayarlanabilir (küçük VPS’lerde dikkatli olun). Düzenli veritabanı optimizasyonu ve yedekleme ihmal edilmemeli. [Veritabanı yönetimi](/docs/databases) panel üzerinden kullanıcı ve yetki oluşturmayı gösterir.

### SSL ve HTTPS

WordPress’te `wp-config.php` ve veritabanındaki `siteurl` / `home` değerleri HTTPS ile uyumlu olmalıdır. Let's Encrypt ile ücretsiz SSL almak panelden birkaç tıkla mümkün. [SSL rehberi](/blog/lets-encrypt-ssl-hosting-panelinde) otomatik kurulum ve yenilemeyi detaylandırır.

## Tek tık WordPress kurulumu

Modern hosting panelleri “WordPress kur” sihirbazı sunar. Tipik adımlar:

1. Panelde yeni site oluşturun; domain ve document root atayın.
2. Veritabanı otomatik veya tek tıkla oluşturulur.
3. WordPress sürümü seçilir; site başlığı ve yönetici bilgileri girilir.
4. Kurulum tamamlanır; `/wp-admin` üzerinden giriş yapılır.

Panelze ile bu süreç genelde üç dakikadan kısa sürer. Manuel kurulum tercih ediyorsanız:

```bash
cd /var/www/siteniz
wp core download --locale=tr_TR
wp config create --dbname=wp_db --dbuser=wp_user --dbpass=guclu_sifre
wp core install --url=https://siteniz.com --title="Site Başlığı" --admin_user=admin
```

WP-CLI, toplu güncelleme ve staging senkronizasyonunda vazgeçilmezdir. [Git deploy](/docs/git-deploy) ile tema ve eklenti güncellemelerini sürüm kontrollü yapabilirsiniz.

## Güvenlik: kurulumdan sonra hemen

WordPress güvenliği hosting + uygulama katmanında ele alınmalıdır.

### Sunucu katmanı

- **UFW firewall:** Yalnızca 22 (SSH), 80 ve 443 açık olsun.
- **Fail2ban:** SSH ve panel giriş denemelerini izleyin. [Güvenlik rehberi](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) adım adım anlatır.
- **SSH anahtar tabanlı giriş:** Parola ile root girişini kapatın.

### WordPress katmanı

- **Güçlü yönetici parolası ve benzersiz kullanıcı adı:** `admin` kullanmayın.
- **Güncel tutun:** Çekirdek, tema ve eklentiler; otomatik güncelleme küçük sitelerde açılabilir.
- **Gereksiz eklentileri kaldırın:** Kullanılmayan eklenti = kullanılmayan saldırı yüzeyi.
- **wp-config.php sertleştirme:** `DISALLOW_FILE_EDIT`, güvenlik anahtarları, `WP_DEBUG` production’da kapalı.
- **İki faktörlü kimlik doğrulama:** Wordfence, Solid Security veya benzeri eklentiler.
- **Dosya izinleri:** Dizinler 755, dosyalar 644; `wp-config.php` 600.

### Yedekleme

Yedek almadan canlıya çıkmayın. Günlük otomatik yedekleme + haftalık harici kopya minimum standarttır. [Yedekleme stratejisi](/docs/backups) Google Drive entegrasyonunu kapsar.

## Performans optimizasyonu kontrol listesi

| Adım | Etki | Zorluk |
|------|------|--------|
| PHP 8.3 + OPcache | Yüksek | Düşük |
| Redis object cache | Orta-yüksek | Orta |
| Sayfa önbelleği eklentisi | Yüksek | Düşük |
| Görselleri WebP’ye çevirme | Orta | Düşük |
| CDN (Cloudflare vb.) | Yüksek (global) | Düşük |
| Gereksiz eklenti temizliği | Orta | Düşük |
| MariaDB indeks optimizasyonu | Orta | Orta |

Google PageSpeed Insights ve GTmetrix ile önce/sonra ölçüm yapın. “His” ile optimizasyon yapmayın; veriye bakın.

## WooCommerce ve e-ticaret ek notlar

WooCommerce, standart bloga göre daha fazla RAM ve CPU ister. Minimum 2 GB RAM önerilir; yoğun trafikte 4 GB+. Staging ortamında ödeme testlerini SSL aktifken yapın. PCI uyumluluğu için kart bilgilerini sunucunuzda saklamayın; Stripe veya iyzico gibi ödeme geçitleri kullanın.

Cron işleri: WordPress varsayılan `wp-cron` her sayfa yüklemesinde tetiklenebilir; yüksek trafikte sistem cron’a geçin:

```bash
*/15 * * * * cd /var/www/siteniz && wp cron event run --due-now
```

## Gerçek senaryo: kurumsal site geçişi

Bursa’da bir üretim firması, eski PHP 7.4 paylaşımlı hostingde yavaşlayan WordPress kurumsal sitesini taşıdı. Yeni ortam: 4 GB RAM VPS, Panelze, PHP 8.3, Redis, Let's Encrypt SSL. Taşıma öncesi All-in-One WP Migration ile export; yeni sunucuda import. DNS geçişi Cuma akşamı yapıldı.

Sonuç: Ana sayfa yükleme süresi 4.2 saniyeden 1.1 saniyeye indi. Aylık hosting maliyeti paylaşımlı “business” paketinden %20 daha düşük; kaynak kontrolü tamamen firmada.

## Panelze ile WordPress iş akışı

1. [Sunucu kurulumu](/docs/server-setup) ve [Panelze kurulumu](/setup) tamamlanır.
2. Site ve veritabanı oluşturulur; [alan adı ayarları](/docs/sites-and-domains) yapılır.
3. SSL etkinleştirilir.
4. WordPress tek tık veya WP-CLI ile kurulur.
5. Yedekleme planı ve güvenlik sertleştirmesi uygulanır.
6. [Panel kılavuzu](/docs/panel-guide) ile günlük yönetim sürdürülür.

Açık kaynak panel karşılaştırması için [cPanel alternatifleri yazısı](/blog/cpanel-alternatifi-acik-kaynak-hosting-paneli) faydalı olabilir.

## Sık yapılan hatalar

- **Production’da eklenti denemek:** Staging kullanın.
- **Yedek almadan güncelleme:** Her majör güncelleme öncesi snapshot.
- **HTTPS’siz admin:** `/wp-admin` mutlaka SSL arkasında.
- **Zayıf veritabanı parolası:** Panel otomatik güçlü parola üretsin.
- **Sınırsız eklenti:** Her eklenti bakım yükü ve risk.

## Sonuç

WordPress hosting seçimi “en ucuz paket” değil; PHP sürümü, önbellek, SSL, yedekleme ve güvenlik katmanlarının birlikte düşünülmesidir. VPS + Panelze kombinasyonu, ajanslar ve büyüyen siteler için 2026’da güçlü ve ekonomik bir yoldur.

İlk adım için [başlangıç rehberine](/docs/getting-started) bakın; maliyet planlaması için [fiyatlandırma](/pricing) sayfasını inceleyin; sorularınız için [SSS](/#faq) bölümüne göz atın. Doğru altyapıyla WordPress hem hızlı hem güvenli çalışır — özgür yazılımın gücü, doğru sunucuda gerçek anlamını bulur.
