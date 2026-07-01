## Google Drive ile sunucu yedekleme: dosya ve veritabanı stratejisi

“Yedek aldım” demek çoğu zaman yalnızca aynı sunucudaki başka bir klasöre kopya koymak anlamına gelir. Disk arızası, veri merkezi yangını, fidye yazılımı veya yanlışlıkla çalıştırılan `rm -rf` komutu bu yedeği de yok edebilir. Gerçek yedekleme kuralı basittir: **3-2-1** — en az üç kopya, iki farklı ortam, birinin off-site (sunucu dışında) olması. Google Drive, küçük ve orta ölçekli VPS işleten ekipler için off-site hedef olarak pratik, uygun maliyetli ve tanıdık bir seçenektir.

Panelze, sunucu yedeklerini yerel diskten Google Drive’a otomatik taşımayı destekleyerek bu stratejiyi günlük operasyona indirger. Bu rehberde neleri yedeklemeniz gerektiğini, veritabanı dump’larının nasıl alınacağını, saklama (retention) politikalarını ve felaket kurtarma senaryolarını ele alıyoruz.

### Neyi yedeklemelisiniz?

Tam sunucu imajı (snapshot) değerli olabilir; ancak web hosting bağlamında genelde şunlar yeterlidir:

| Bileşen | Neden kritik | Tipik konum |
|---------|--------------|-------------|
| Site dosyaları | Kod, medya, yapılandırma | `/home/kullanici/siteler/...` |
| Veritabanları | Müşteri verisi, siparişler, içerik | MariaDB/MySQL dump |
| Panel yapılandırması | Site tanımları, SSL, cron | Panel veri dizini |
| `.env` ve gizli dosyalar | API anahtarları, DB şifreleri | Site kökü (repoda olmayan) |

Git repoda tutulan kod için “yedek” zaten uzak depodadır; ama `wp-content/uploads`, müşteri yüklemeleri ve panelde tanımlı site ayarları repoda olmayabilir. [Yedekleme dokümantasyonu](/docs/backups) Panelze’nin hangi dizinleri kapsadığını detaylandırır.

### Veritabanı dump’ları: doğru yöntem

Canlı veritabanını doğrudan `.sql` dosyası olarak kopyalamak yerine tutarlı bir dump alın. MariaDB/MySQL için:

```bash
mysqldump --single-transaction --routines --triggers \
  -u kullanici -p veritabani_adi > backup_$(date +%F).sql
```

`--single-transaction` InnoDB tablolarında kilitlenmeyi minimize eder; site çalışırken dump almanızı sağlar. Büyük veritabanlarında sıkıştırma ekleyin:

```bash
mysqldump ... | gzip > backup_$(date +%F).sql.gz
```

Panelze zamanlanmış görevlerle bu dump’ı otomatik üretebilir ve yedek paketine dahil edebilir. [Veritabanı yönetimi](/docs/databases) sayfasında her site için ayrı DB kullanıcısı ve izolasyon önerilir; yedekler de site bazında ayrı tutulmalıdır.

Dump dosyasını restore etmek için:

```bash
gunzip -c backup_2026-07-01.sql.gz | mysql -u kullanici -p veritabani_adi
```

Restore öncesi mevcut veritabanının yedeğini almayı unutmayın; “geri yüklerken bir şey daha bozuldu” senaryosu sık görülür.

### Google Drive entegrasyonu nasıl çalışır?

Panelze, OAuth2 ile Google hesabınıza bağlanır veya servis hesabı kullanır (kurumsal senaryolarda). Yedek tamamlandığında arşiv dosyası (genelde `.tar.gz` veya `.zip`) Drive’daki belirlediğiniz klasöre yüklenir. Yerel diskte geçici dosya oluşturulur, yükleme bitince silinir; böylece disk dolması riski azalır.

Kurulum adımları özetle:

1. Google Cloud Console’da proje oluşturun, Drive API’yi etkinleştirin.
2. OAuth istemci kimliği veya servis hesabı anahtarı oluşturun.
3. Panelze yedekleme ayarlarından Google Drive’ı bağlayın, hedef klasörü seçin.
4. Yedekleme sıklığını (günlük, haftalık) ve saatini belirleyin.

İlk bağlantıda panelin istediği izinleri verin; yedek dosyaları yalnızca sizin belirlediğiniz klasöre yazılır. [Kurulum sonrası](/docs/post-install) kontrol listesinde yedekleme testinin ilk 24 saat içinde yapılması önerilir.

### Saklama süresi (retention) politikası

Sınırsız yedek tutmak hem maliyet hem yönetim yükü getirir. Pratik bir politika:

- **Günlük yedekler:** Son 7 gün
- **Haftalık yedekler:** Son 4 hafta
- **Aylık yedekler:** Son 3–6 ay

Panelze retention ayarları eski dosyaları Drive’dan otomatik siler; manuel yönetimde Google Drive’da “yaşam döngüsü” kuralları veya script ile eski dosyaları temizleyebilirsiniz. Kritik müşteri projelerinde yasal saklama süreleri (KVKK, sözleşme) retention planını etkileyebilir; hukuk danışmanlığı gerekebilir.

Yedek boyutunu küçültmek için:

- Log dosyalarını yedek dışı bırakın (`/var/log`, site `storage/logs`)
- `node_modules` ve `vendor` gibi yeniden üretilebilir dizinleri hariç tutun (deploy ile gelir)
- Büyük medya arşivleri için ayrı, daha seyrek yedek planı düşünün

### Şifreleme ve güvenlik

Google Drive aktarımı TLS ile şifrelidir; Google’ın sunucularında dinlenme durumunda da şifreli saklanır. Ek güvenlik için yedek arşivini yüklemeden önce GPG ile şifreleyebilirsiniz:

```bash
tar czf - /yol/site | gpg --symmetric --cipher-algo AES256 -o site_backup.tar.gz.gpg
```

Şifre anahtarını yedekle birlikte saklamayın; parola yöneticisinde veya ayrı kasada tutun. Yedek dosyaları hassas müşteri verisi içerir; Drive klasör paylaşımını minimumda tutun, iki faktörlü kimlik doğrulamayı Google hesabında zorunlu kılın.

Sunucu tarafında yedekleme cron’unun çalıştığı kullanıcı yalnızca gerekli dizinlere erişebilmeli. [Sunucu güvenliği](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) rehberimizdeki UFW ve SSH sıkılaştırması, yedek sürecini de dolaylı korur.

### Felaket kurtarma (disaster recovery) senaryoları

**Senaryo 1 — Tek site bozuldu:** Müşteri yanlışlıkla dosya sildi veya hatalı deploy yaptı. Drive’dan ilgili tarihin yedeğini indirin, dosyaları site dizinine geri yükleyin, gerekirse DB dump’ı import edin. Panelze’de site geri yükleme sihirbazı varsa adımları takip edin.

**Senaryo 2 — Veritabanı bozuldu:** Tablo bozulması veya fidye yazılımı. En son temiz dump’tan restore; ardından kod ve dosyaların da o tarihle uyumlu olduğundan emin olun (kod yedeği ile DB yedeği farklı günlerden ise tutarsızlık olur).

**Senaryo 3 — Tüm sunucu kayboldu:** VPS sağlayıcısı, disk ölümü veya hesap kapatma. Yeni Ubuntu sunucu kurun, [sunucu kurulum checklist’imizi](/blog/ubuntu-22-04-web-sunucu-kurulum-checklist) izleyin, Panelze’yi yeniden yükleyin ([sunucu kurulumu](/docs/server-setup)). Drive’dan en son tam yedeği indirip restore edin. DNS’i yeni IP’ye yönlendirin. Bu senaryoda RTO (kurtarma süresi) saatler, RPO (kabul edilebilir veri kaybı) son yedekleme aralığına bağlıdır — günlük yedekte en fazla 24 saatlik veri kaybı riski vardır.

**Senaryo 4 — Google hesabı erişilemez:** İkincil hedef (S3, başka Drive hesabı, yerel NAS) düşünün. 3-2-1 kuralının “iki ortam” şartını karşılayın.

### Yedekleri test etmek: en çok atlanan adım

Yedek almak yetmez; **restore testi** şarttır. Ayda bir kez:

1. Staging veya geçici bir sunucuda yedeği açın
2. Bir veritabanını import edin, birkaç tabloyu sorgulayın
3. Site dosyalarının bütünlüğünü kontrol edin
4. Süreyi not edin; gerçek felakette ne kadar süreceğinizi bilirsiniz

Test sırasında production’a dokunmayın. Panelze’de staging sitesi oluşturmak [site ve domain](/docs/sites-and-domains) dokümantasyonundaki adımlarla kolaydır.

### Yerel yedek + bulut: hibrit model

Google Drive’a giden yedeklerin yanında sunucuda son 1–2 yedeği tutmak restore hızını artırır (büyük arşivi Drive’dan indirmek zaman alır). Panelze genelde önce yerel snapshot alır, ardından buluta gönderir. Yerel kopyalar retention ile silinir; asıl uzun vadeli arşiv Drive’dadır.

Bant genişliği sınırlı sunucularda yedekleme saatini gece trafiğinin düşük olduğu zamana alın. İlk tam yedek büyük olabilir; sonraki artımlı veya değişen dosya odaklı stratejiler mümkünse tercih edilir.

### Maliyet ve kapasite planlaması

Google Drive ücretsiz katman sınırlıdır; birden fazla site ve günlük DB dump ile hızla dolar. Google One veya Workspace planları TB seviyesinde alan sunar. Yedek boyutunu izleyin; panel veya Drive arayüzünden klasör büyümesini aylık kontrol edin.

[Panelze fiyatlandırması](/pricing) sunucu maliyetinizin yanında Drive depolama maliyetini de bütçeye dahil etmeyi unutmayın. Küçük ajanslar için aylık birkaç euro ek depolama, müşteri verisi kaybı riskine kıyasla ihmal edilebilir düzeydedir.

### Operasyonel ipuçları

- Yedekleme başarısız olduğunda e-posta veya webhook ile uyarı alın; sessiz hata en tehlikelisidir.
- Büyük deploy günlerinden önce manuel yedek tetikleyin.
- Müşteri sözleşmesinde yedekleme sıklığı ve sorumluluk sınırını netleştirin.
- [Panel rehberi](/docs/panel-guide) içinde yedekleme ekranlarının güncel kullanımını bulabilirsiniz.

Google Drive ile sunucu yedekleme, kurumsal backup yazılımı kadar gösterişli değildir; ancak doğru yapılandırıldığında küçük ekipler için etkili bir felaket kurtarma hattı oluşturur. Panelze bu hattı otomatikleştirir; sizin yapmanız gereken retention belirlemek, düzenli restore testi yapmak ve yedeklerin gerçekten sunucu dışında olduğundan emin olmaktır.

Sorularınız için [SSS bölümüne](/#faq) bakın veya henüz panel kurmadıysanız [hızlı kurulum](/setup) ile başlayın. Git ile deploy ediyorsanız kod yedeği ayrı; dosya ve veritabanı yedeği birlikte düşünülmeli — [Git deploy rehberimiz](/blog/git-deploy-ile-canli-site-guncelleme) bu ayrımı vurgular.
