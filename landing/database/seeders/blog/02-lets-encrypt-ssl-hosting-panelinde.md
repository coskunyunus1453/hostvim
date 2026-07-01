## HTTPS artık opsiyon değil

Google’ın 2018’den bu yana HTTPS’i sıralama sinyali olarak kullanması, tarayıcıların HTTP sitelerde “Güvenli değil” uyarısı göstermesi ve kullanıcıların adres çubuğundaki kilit simgesine alışması, SSL sertifikasını lüks değil zorunluluk haline getirdi. 2026’da yeni bir site açıp hâlâ `http://` ile yayın yapmak hem SEO hem güven hem de form gönderimlerinde veri koruma açısından kabul edilemez.

İyi haber şu: Let's Encrypt, 2015’ten beri ücretsiz ve otomatik yenilenen SSL sertifikaları sunuyor. Kötü haber: sertifikayı almak tek başına yeterli değil; doğru yapılandırma, yenileme otomasyonu ve DNS uyumu gerekiyor. Hosting paneli bu süreci büyük ölçüde basitleştirir.

## Let's Encrypt nasıl çalışır?

Let's Encrypt, ACME (Automatic Certificate Management Environment) protokolünü kullanır. Sunucunuz veya paneliniz, sertifika otoritesine “bu domain bana ait” kanıtı sunar; kanıt geçerliyse 90 günlük bir sertifika alırsınız. Süre dolmadan önce otomatik yenileme şarttır; aksi halde siteniz bir sabah “bağlantınız gizli değil” uyarısıyla karşılaşır.

### HTTP-01 doğrulama

En yaygın yöntemdir. Let's Encrypt, `http://siteniz.com/.well-known/acme-challenge/` altında bir dosyaya erişmeye çalışır. Web sunucunuz bu dosyayı sunabiliyorsa doğrulama başarılı olur. Panel üzerinden “SSL Etkinleştir” dediğinizde çoğu zaman arka planda bu yöntem kullanılır.

**Dikkat:** Domain DNS kaydı sunucunuza işaret etmiyorsa HTTP-01 başarısız olur. Site henüz taşınmadan SSL almaya çalışıyorsanız önce A kaydını güncelleyin. [Alan adı ve site yönetimi rehberi](/docs/sites-and-domains) DNS adımlarını kapsar.

### DNS-01 doğrulama

Wildcard sertifika (`*.siteniz.com`) veya sunucu henüz canlıya alınmamışken sertifika almak için DNS-01 gerekir. Bu yöntemde bir TXT kaydı oluşturulur; Let's Encrypt DNS üzerinden doğrular. Panelze ve birçok modern panel, DNS API entegrasyonu ile bu süreci otomatikleştirir.

**Senaryo:** Staging ortamınız `staging.siteniz.com` altında; henüz production’a geçmeden SSL istiyorsunuz. DNS-01 ile ana domain veya alt domain için sertifika alınabilir. [SSL, DNS ve e-posta dokümantasyonu](/docs/ssl-dns-email) her iki yöntemi detaylandırır.

## Hosting panelinde otomatik SSL kurulumu

Komut satırında Certbot kullanmak mümkündür:

```bash
certbot certonly --webroot -w /var/www/siteniz -d siteniz.com -d www.siteniz.com
```

Ancak on site yönettiğinizde her domain için bu komutu çalıştırmak ve cron job yazmak verimsizdir. Panel şunları tek tıkla yapar:

1. Domain için web kök dizinini belirler.
2. ACME challenge dosyasını oluşturur.
3. Sertifikayı Nginx veya Apache yapılandırmasına yazar.
4. HTTP’den HTTPS’e yönlendirme ekler.
5. Yenileme cron’unu otomatik planlar.

Panelze’de site oluşturduktan sonra SSL sekmesinden Let's Encrypt’i etkinleştirmek genelde 30–60 saniye sürer. [Panel kullanım kılavuzu](/docs/panel-guide) arayüz adımlarını gösterir.

## Yenileme: unutulan ama kritik adım

Let's Encrypt sertifikaları 90 gün geçerlidir. Certbot ve panel cron job’ları genelde süre dolmadan 30 gün önce yenilemeyi dener. Yenileme başarısız olursa — DNS değişikliği, firewall kuralı, disk doluluğu — sertifika sessizce süresi dolar.

### Yenileme başarısızlığının yaygın nedenleri

- **DNS kaydı değişti:** Domain başka sunucuya taşındı ama panel hâlâ eski sunucuda yenilemeye çalışıyor.
- **Port 80 kapalı:** HTTP-01 için 80 numaralı port açık olmalıdır. UFW’de `80/tcp` ve `443/tcp` izinli mi kontrol edin. [Güvenlik rehberimiz](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) firewall kurallarını anlatır.
- **Rate limit:** Let's Encrypt haftalık başarısız deneme limiti koyar. Test ortamında sürekli deneme yapmayın; staging ortamı kullanın.
- **www ve apex uyumsuzluğu:** `siteniz.com` ve `www.siteniz.com` için ayrı sertifika veya SAN (Subject Alternative Name) gerekir. Panel genelde ikisini birlikte ister.

Aylık bir kontrol listesi oluşturun: SSL bitiş tarihlerini panelden veya `certbot certificates` çıktısından doğrulayın.

## HTTP’den HTTPS’e yönlendirme

Sertifika almak yeterli değildir; ziyaretçilerin `http://` ile gelen istekleri `https://` adresine yönlendirilmelidir. Nginx örneği:

```nginx
server {
    listen 80;
    server_name siteniz.com www.siteniz.com;
    return 301 https://$host$request_uri;
}
```

Panel bu kuralı genelde otomatik ekler. Manuel düzenleme yaptıysanız çift yönlendirme (redirect loop) oluşmamasına dikkat edin.

### HSTS (HTTP Strict Transport Security)

HSTS, tarayıcıya “bu siteye bir daha HTTP ile gelme” der. `Strict-Transport-Security` başlığı eklenir. İlk kurulumda dikkatli olun: yanlış yapılandırma sitenize erişimi zorlaştırır. SSL tamamen stabil olduktan sonra etkinleştirin.

## Wildcard ve çoklu domain senaryoları

### Ajans: onlarca müşteri sitesi

Her müşteri için ayrı sertifika almak normaldir; panel bunu ölçekler. Önemli olan her yeni site eklendiğinde SSL adımının atlanmamasıdır. [Çoklu site yönetimi](/docs/sites-and-domains) ile her domain izole kalır.

### Alt domain ve API

`api.siteniz.com`, `cdn.siteniz.com` gibi alt domainler için ya ayrı sertifika ya da wildcard sertifika gerekir. Wildcard için DNS-01 şarttır.

### E-posta ve SSL

SSL web trafiği için geçerlidir; SMTP/IMAP için ayrı sertifika veya Let's Encrypt’in mail sunucusu desteği panel yapılandırmasına bağlıdır. [SSL, DNS ve e-posta](/docs/ssl-dns-email) bölümünde mail TLS ayarları da yer alır.

## Performans ve SEO etkisi

HTTPS, TLS el sıkışması nedeniyle milisaniye düzeyinde ek gecikme ekler; modern sunucularda bu ihmal edilebilir. HTTP/2 ve HTTP/3 (QUIC) genelde yalnızca HTTPS üzerinde çalışır; dolayısıyla SSL açmak aslında performansı da iyileştirebilir.

Google Search Console’da “HTTPS sayfalar” oranınız %100 olmalıdır. Karışık içerik (mixed content) uyarıları — HTTPS sayfada HTTP kaynak yükleme — tarayıcı konsolunda görünür; görselleri ve script’leri de HTTPS veya göreli URL ile sunun.

## Panelze ile pratik SSL iş akışı

Panelze kurulumundan sonra tipik akış şöyledir:

1. [Sunucu kurulumu](/docs/server-setup) tamamlanır; 80 ve 443 portları açıktır.
2. Panelde yeni site oluşturulur; document root atanır.
3. Domain DNS A kaydı sunucu IP’sine yönlendirilir (propagasyon 5 dakika–48 saat sürebilir).
4. SSL sekmesinden Let's Encrypt seçilir; domain listesi onaylanır.
5. “Zorla HTTPS” seçeneği açılır.
6. [Yedekleme planı](/docs/backups) oluşturulur — sertifika dosyaları da yedek kapsamına girer.

WordPress sitelerinde SSL sonrası `siteurl` ve `home` değerlerinin `https://` olduğundan emin olun. [WordPress hosting rehberi](/blog/wordpress-hosting-nasil-secilir-kurulum) bu geçişi kapsar.

## Sorun giderme kontrol listesi

| Belirti | Olası neden | Çözüm |
|---------|-------------|-------|
| “Bağlantı gizli değil” | Süresi dolmuş sertifika | Manuel yenileme veya cron kontrolü |
| SSL alınamıyor | DNS henüz yönlenmedi | `dig siteniz.com` ile IP doğrula |
| Redirect loop | Çift yönlendirme kuralı | Nginx/Apache config incele |
| Mixed content | HTTP kaynaklar | URL’leri HTTPS yap |
| Wildcard başarısız | HTTP-01 kullanılıyor | DNS-01’e geç |

## Ücretli sertifika gerekir mi?

Let's Encrypt çoğu web sitesi, blog, e-ticaret ve kurumsal tanıtım sitesi için yeterlidir. EV (Extended Validation) veya özel garantili ticari sertifikalar, bankacılık düzeyi uyumluluk veya eski istemci desteği gerektiren nadir senaryolarda düşünülür. 2026’da tarayıcılar Let's Encrypt’i tam güvenilir kabul eder.

## Sonuç

Let's Encrypt, hosting paneli ile birleştiğinde SSL yönetimi neredeyse görünmez hale gelir: kur, unut, yenilensin. Asıl iş DNS doğruluğu, firewall kuralları ve yenileme izlemesinde. Panel seçerken SSL otomasyonunun ne kadar olgun olduğunu mutlaka test edin.

VPS ve panel seçimi henüz yapılmadıysa [VPS hosting paneli rehberi](/blog/vps-hosting-paneli-nasil-secilir) size yol gösterir. [Panelze kurulumu](/setup) ile başlayıp [sık sorulan sorular](/#faq) bölümünden devam edebilirsiniz. Ücretsiz SSL, artık herkesin hakkı — doğru yapılandırmayla da herkesin sorunsuz deneyimi.
