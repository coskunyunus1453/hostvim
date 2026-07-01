## Web ajansları için çoklu site yönetimi: tek panelden onlarca proje

Freelancer veya küçük bir web ajansı olarak her müşteri için ayrı paylaşımlı hosting hesabı açmak hem maliyetli hem de operasyonel kabustur: on farklı panel şifresi, on farklı fatura, on farklı destek hattı. Tek bir VPS üzerinde Panelze ile onlarca siteyi yönetmek ise ölçeklenebilir, öngörülebilir ve müşteriye daha profesyonel bir deneyim sunar. Bu yazıda ajansların çoklu site yönetiminde dikkat etmesi gereken izolasyon, white-label panel, müşteri erişimi ve tek sunucuda çok proje stratejilerini paylaşıyoruz.

### Neden tek VPS, çok site?

Modern VPS fiyatları (2–4 vCPU, 4–8 GB RAM) çoğu ajansın ilk 20–50 müşteri sitesi için yeterlidir. Trafiği yüksek e-ticaret veya özel uygulamalar hariç, statik ve WordPress ağırlıklı portföy tek makinede rahatlıkla barınır. Avantajlar:

- **Sabit aylık maliyet:** Sunucu + panel lisansı (Panelze açık kaynak olduğunda lisans maliyeti düşük) öngörülebilir.
- **Merkezi güncelleme:** PHP, Nginx, güvenlik yamaları bir kez uygulanır.
- **Standart iş akışı:** Her yeni proje aynı şablonla açılır: site, DB, SSL, yedek, Git deploy.

Dezavantaj da vardır: tek sunucu tek hata noktasıdır. Bu yüzden [Google Drive yedekleme](/blog/google-drive-sunucu-yedekleme-rehberi) ve izleme şarttır. Büyüdükçe ikinci sunucuya müşteri gruplarını bölmek mantıklıdır.

### Müşteri izolasyonu: güvenlik temeli

Aynı sunucuda birden fazla müşteri barındırırken **bir müşterinin diğerinin dosyasına veya veritabanına erişememesi** kritiktir. Panelze her site için ayrı sistem kullanıcısı (veya eşdeğer izolasyon) ve ayrı web kök dizini tanımlar. PHP-FPM pool’ları site başına çalışır; bir sitenin `open_basedir` veya benzeri kısıtlamaları komşu dizinlere sızmaz.

Ajans olarak kontrol listesi:

- Her site için ayrı veritabanı ve ayrı DB kullanıcısı ([veritabanları rehberi](/docs/databases))
- SSH erişimini yalnızca ajans ekibine verin; müşteriye SFTP ile sınırlı hesap
- Paylaşımlı sunucu anahtarları kullanmayın; müşteri başına deploy key
- Düzenli güvenlik güncellemeleri ve [Fail2ban/UFW](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) yapılandırması

“Noisy neighbor” sorunu: bir müşterinin sitesi CPU’yu tüketirse diğerleri etkilenir. `cgroups`, PHP-FPM `pm.max_children` limitleri ve isteğe bağlı rate limiting ile kaynak tavanları koyun. Aşırı trafik alan siteyi ayrı VPS’e taşıma planınız olsun.

### White-label: müşteri panelde kendi markanızı görsün

Ajans kimliğiniz güçlüyse müşteriye “cPanel giriş linki” yerine `panel.sizinajans.com` adresinde kendi logonuzla panel sunmak profesyonellik katar. Panelze white-label özellikleriyle:

- Özel domain (panel alt alan adı)
- Logo ve renk teması
- Destek e-postası ve yardım linkleri sizin markanıza yönlendirilir

Müşteri yalnızca kendi sitesini görür; diğer müşterilerin listesi görünmez. Bu model, “hosting’i biz yönetiyoruz, siz içeriğe odaklanın” sözleşmesine uyar. Teknik detaylar için [panel rehberi](/docs/panel-guide) ve [başlangıç dokümantasyonu](/docs/getting-started) faydalıdır.

White-label kullanırken SSL sertifikasını panel domain’i için de unutmayın ([SSL ve DNS](/docs/ssl-dns-email)). Let’s Encrypt otomasyonu bu süreci kolaylaştırır; [SSL blog yazımız](/blog/lets-encrypt-ssl-hosting-panelinde) konuyu derinleştirir.

### Site şablonları ve hızlı onboarding

Her yeni müşteri projesinde sıfırdan kurulum yapmak yerine şablon kullanın:

| Proje tipi | Şablon içeriği |
|------------|----------------|
| Kurumsal WordPress | WP çekirdek, güvenlik eklentisi, cache, staging dalı |
| Laravel SaaS | `.env.example`, queue worker cron, Redis opsiyonel |
| Statik landing | Nginx config, Git deploy, minimal PHP |

Panelze’de site oluşturduktan sonra [Git deploy](/docs/git-deploy) ile şablon repodan çekilir; müşteri domain’i bağlanır, SSL açılır. İlk yedekleme ve staging ortamı aynı gün içinde tanımlanmalıdır.

Onboarding dokümanını müşteriye PDF veya Notion sayfası olarak verin: FTP/SFTP bilgileri (veya “biz deploy ediyoruz”), destek kanalı, yedekleme sıklığı. Şeffaflık güven oluşturur.

### Faturalandırma ve paketleme

Teknik altyapı tek olsa da müşteriye satış paketleri ayrıdır. Örnek katmanlar:

- **Başlangıç:** 1 site, 5 GB disk, haftalık yedek, e-posta desteği
- **Profesyonel:** staging, günlük yedek, Git deploy, öncelikli destek
- **Bakım dahil:** aylık güncelleme, uptime izleme, küçük içerik değişiklikleri

Sunucu maliyetinizi toplam site sayısına bölerek site başına marj hesaplayın. [Fiyatlandırma](/pricing) sayfamız Panelze’nin lisans modelini gösterir; ajans fiyatınızı buna göre kurgulayın. cPanel tabanlı reseller’dan geçişte [cPanel alternatifi karşılaştırmamız](/blog/cpanel-alternatifi-acik-kaynak-hosting-paneli) maliyet avantajını özetler.

### Ekip içi roller ve erişim

Ajans büyüdükçe herkesin root şifresi olmamalı. Roller önerisi:

- **Süper admin:** sunucu, panel ayarları, faturalandırma
- **Geliştirici:** site oluşturma, deploy, DB erişimi (staging)
- **Destek:** yedek restore, SSL yenileme, log okuma — production DB yazma yok

Panelze’nin kullanıcı ve izin modeli bu ayrımı destekler. Müşteri kullanıcısı yalnızca kendi site paneline girer; sunucu seviyesi ayarları görmez.

### Monitoring ve SLA beklentileri

Onlarca site tek sunucuda iken proaktif izleme şart:

- Uptime kontrolü (UptimeRobot, Better Stack vb.)
- Disk doluluk uyarısı (%85 eşiği)
- SSL süre sonu uyarısı (panel otomasyonuna ek olarak)
- Yedekleme başarısızlık bildirimi

Müşteri sözleşmesinde SLA’yı gerçekçi yazın. “%99.9 uptime” demek için altyapı ve yedek sunucu gerekir; küçük ajans için “iş günü içinde müdahale” daha dürüst olabilir.

### Ölçeklenme: ne zaman ikinci sunucu?

Şu sinyallerde bölün:

- RAM sürekli %85 üzeri, swap kullanımı
- Disk I/O darboğazı (özellikle paylaşımlı VPS disklerinde)
- Müşteri sayısı 40–60+ ve çoğu aktif WordPress
- Coğrafi gecikme: Avrupa ve Türkiye müşterileri için ayrı lokasyon

İkinci sunucuda yine Panelze; müşteri gruplarını coğrafya veya paket tipine göre ayırın. DNS ve yedek stratejisi her sunucu için ayrı düşünülmeli.

### Yasal ve KVKK notları

Türkiye’deki müşterilerin kişisel verileri sunucunuzda işleniyorsa KVKK kapsamında veri işleyen sıfatıyla yükümlülükleriniz olabilir. Sunucu lokasyonu (AB, TR, ABD), yedeklerin Drive’da hangi bölgede tutulduğu ve alt işleyici sözleşmeleri müşteri sözleşmenize yansıtılmalıdır. Bu yazı hukuki tavsiye değildir; gerektiğinde uzman desteği alın.

### Pratik başlangıç: ilk on müşteri

1. [Ubuntu 22.04 checklist](/blog/ubuntu-22-04-web-sunucu-kurulum-checklist) ile sunucuyu hazırlayın
2. [Kurulum](/setup) ile Panelze’yi yükleyin
3. White-label domain ve SSL’i ayarlayın
4. İç şablon repolarınızı hazırlayın, [site ve domain](/docs/sites-and-domains) ile ilk müşteriyi açın
5. [Yedekleme](/docs/backups) ve izlemeyi devreye alın
6. Müşteri panel kullanıcılarını oluşturun

Her yeni site için aynı kontrol listesini tekrarlayın; tutarlılık ajans kalitesini tanımlar.

### Sık yapılan hatalar

- Tüm siteleri tek DB kullanıcısıyla açmak (izolasyon ihlali)
- Yedek almadan toplu PHP sürüm yükseltmesi ([PHP sürüm rehberi](/blog/php-surumu-degistirme-performans) önce staging’de test önerir)
- Müşteriye root SSH vermek
- Staging olmadan canlıya doğrudan deploy ([Git deploy yazısı](/blog/git-deploy-ile-canli-site-guncelleme) staging vurgular)

Web ajansı olarak çoklu site yönetimi, doğru araç ve disiplinle rekabet avantajıdır. Panelze tek VPS üzerinde onlarca projeyi yönetmenizi, müşteriye white-label deneyim sunmanızı ve operasyonu tek noktadan standartlaştırmanızı sağlar. Sorular için [SSS](/#faq) bölümüne göz atın; sunucu seçimi konusunda [VPS panel rehberimiz](/blog/vps-hosting-paneli-nasil-secilir) de yardımcı olur.

Tek panel, çok proje — ajansınız büyüdükçe bu cümle iş modelinizin özeti olabilir.
