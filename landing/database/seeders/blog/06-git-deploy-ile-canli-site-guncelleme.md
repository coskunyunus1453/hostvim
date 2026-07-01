## Git deploy ile canlı site güncelleme: FTP'ye veda

Hâlâ FileZilla ile `public_html` klasörüne dosya sürükleyip bırakıyorsanız, yalnız değilsiniz. Yıllarca bu yöntem iş gördü; ancak proje büyüdükçe “hangi dosyayı attım?”, “canlıda hangi sürüm var?” ve “bir şey bozuldu, nasıl geri alacağım?” soruları kaçınılmaz hale gelir. Git tabanlı deploy, bu belirsizliği ortadan kaldırır: her değişiklik bir commit ile kayıt altına alınır, staging ortamında test edilir ve production’a kontrollü şekilde taşınır.

Panelze gibi modern hosting panelleri, Git entegrasyonunu FTP’nin yerine geçecek şekilde tasarlar. Amaç, geliştiricinin yerel makinede veya CI/CD hattında yaptığı işi sunucuya güvenli ve tekrarlanabilir biçimde aktarmaktır. Bu yazıda Git deploy’un neden tercih edildiğini, webhook akışını, staging ile production ayrımını ve rollback stratejilerini pratik örneklerle ele alıyoruz.

### FTP neden artık yeterli değil?

FTP (veya SFTP) basit görünür: dosyayı yükle, bitti. Sorun şu ki bu model sürüm kontrolü bilmez. İki geliştirici aynı dosyayı farklı saatlerde güncellediğinde son yüklenen kazanır; diğerinin değişiklikleri sessizce ezilir. Üstüne bir de `.env`, `vendor/` veya `node_modules/` gibi yanlışlıkla yüklenen dosyalar eklenince güvenlik ve tutarlılık riski doğar.

Git ise her değişikliği kim, ne zaman, hangi mesajla yaptı sorusuna cevap verir. Sunucuya deploy edilen kod, belirli bir commit hash’ine bağlanır. Bu hash sayesinde “canlıda tam olarak ne çalışıyor?” sorusunun cevabı netleşir. [Git deploy dokümantasyonu](/docs/git-deploy) bu akışın Panelze üzerinde nasıl kurulacağını adım adım anlatır.

### Git deploy nasıl çalışır?

Temel mantık şudur: kaynak kodunuz bir Git deposunda (GitHub, GitLab, Bitbucket veya kendi sunucunuz) tutulur. Sunucu tarafında ise sitenin çalıştığı dizin bir Git working copy olarak yapılandırılır veya her deploy’da temiz bir checkout alınır. Yeni sürüm yayınlamak için:

1. Geliştirici yerelde değişiklik yapar ve `main` veya `develop` dalına push eder.
2. Webhook veya panel üzerinden manuel tetikleme ile sunucu bu push’u algılar.
3. Sunucu `git pull` (veya `git fetch` + belirli commit’e checkout) çalıştırır.
4. Gerekirse `composer install`, `npm run build`, cache temizleme gibi post-deploy adımları çalışır.

Panelze’de site oluştururken Git deposu URL’sini tanımlayıp deploy anahtarını (deploy key) eklediğinizde, panel bu adımların çoğunu sizin yerinize yönetir. SSH anahtarı yalnızca okuma yetkili olmalıdır; yazma yetkisi gereksiz risk oluşturur.

### Webhook: otomatik deploy’un kalbi

Manuel “Deploy Et” düğmesi işe yarar; ancak ekip büyüdükçe otomasyon şart olur. Webhook, Git sağlayıcınızın her push sonrası Panelze sunucunuza bir HTTP POST isteği göndermesidir. İstek genelde imzalıdır (GitHub’da `X-Hub-Signature-256`, GitLab’da secret token) ve yalnızca doğrulanmış istekler deploy tetikler.

Webhook kurarken dikkat edilmesi gerekenler:

- **Dal filtresi:** Production webhook yalnızca `main` dalına; staging webhook `develop` veya `staging` dalına bağlanmalıdır. Aksi halde yarım kalmış bir feature branch canlıya düşebilir.
- **Gizli anahtar:** Webhook URL’si ve secret değeri kimseyle paylaşılmamalı; panel ayarlarından periyodik olarak yenilenebilir.
- **Yeniden deneme:** Ağ kesintisinde Git sağlayıcısı isteği tekrar gönderebilir; idempotent deploy script’leri (aynı commit iki kez deploy edilse bile sorun çıkarmayan) tercih edin.

Webhook yerine GitHub Actions veya GitLab CI kullanmak da mümkündür. CI sunucusu SSH ile sunucuya bağlanıp deploy komutlarını çalıştırır. Panelze ortamında çoğu ekip için webhook daha az yapılandırma gerektirir; CI ise test, lint ve build adımlarını deploy öncesine koymanızı sağlar.

### Staging ve production: neden iki ortam?

“Doğrudan canlıya atsam daha hızlı olmaz mı?” düşüncesi cazip; ta ki ödeme sayfası bozulana kadar. Staging (ön üretim) ortamı, production’ın mümkün olduğunca birebir kopyasıdır: aynı PHP sürümü, benzer veritabanı yapısı, aynı Nginx kuralları. Fark genelde domain (`staging.ornek.com`), veritabanı içeriği (anonimleştirilmiş veya test verisi) ve dış servis anahtarlarıdır (sandbox Stripe, test e-posta).

İyi bir staging akışı şöyle görünür:

- Feature branch → merge request → `develop` → otomatik staging deploy
- QA onayı → `main`’e merge → production deploy (manuel onay veya ayrı webhook)

Panelze’de [site ve domain yönetimi](/docs/sites-and-domains) ile aynı sunucuda iki ayrı site tanımlayarak staging ve production’ı izole edebilirsiniz. Her sitenin kendi Git dalı, kendi `.env` dosyası ve kendi veritabanı olur. Bu yapı, müşteri projelerinde de standarttır: müşteri staging’i görür, onay verir; siz production’a taşırsınız.

Staging’i atlamak küçük statik sitelerde kabul edilebilir; ancak WooCommerce, Laravel veya özel API içeren projelerde staging neredeyse zorunludur. [WordPress hosting rehberimiz](/blog/wordpress-hosting-nasil-secilir-kurulum) da eklenti güncellemelerinin önce test ortamında denenmesini önerir.

### Post-deploy adımları: unutulan detaylar

`git pull` bitti diye iş bitmiş sayılmaz. PHP projelerinde:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force   # Laravel
php artisan config:cache
```

Node tabanlı front-end’de `npm ci && npm run build` çalıştırılır; build çıktısı (`dist/`, `public/build/`) sunucuda üretilir, repoda tutulmaz. WordPress’te `wp-cli` ile cache flush veya permalink yenileme gerekebilir.

Panelze deploy script alanına bu komutları ekleyerek her deploy sonrası otomatik çalışmasını sağlayabilirsiniz. [Kurulum sonrası yapılacaklar](/docs/post-install) listesinde önerilen PHP uzantıları ve dizin izinleri deploy öncesi de kontrol edilmelidir.

### Rollback: geri alma stratejileri

En güzel deploy planı bile bazen ters gider. Rollback seçenekleri:

**1. Git commit’e dönüş:** En hızlı yöntem. Önceki bilinen iyi commit hash’ine checkout veya revert commit push edilir. Panelze’de “Son deploy’a geri dön” benzeri bir işlem varsa tek tıkla yapılır; yoksa SSH ile `git checkout <hash>` yeterlidir.

**2. Veritabanı migration geri alma:** Kod geri alınsa bile migration çalıştırdıysanız şema değişmiş olabilir. Production’da destructive migration’ları dikkatle yönetin; mümkünse geri alınabilir (reversible) migration yazın. Kritik deploy öncesi [veritabanı yedeği](/docs/databases) şarttır.

**3. Dosya yedeği:** Git kodu kapsar; yüklenen medya dosyaları (`wp-content/uploads` gibi) repoda olmayabilir. Bu yüzden [yedekleme stratejiniz](/docs/backups) Git’ten bağımsız çalışmalıdır.

**4. Blue-green deploy:** İki production dizini döngüsel kullanılır; yeni sürüm hazır olunca trafik yeni dizine yönlendirilir. Tek VPS’te Panelze ile tam blue-green zor olsa da, staging’i “yeşil” ortam olarak kullanıp DNS veya Nginx upstream ile geçiş yapılabilir.

Rollback sonrası mutlaka kök neden analizi yapın: hangi commit, hangi migration, hangi config değişikliği soruna yol açtı? Bir sonraki deploy’da aynı hata tekrarlanmasın.

### Güvenlik ve erişim kontrolü

Deploy anahtarları ve webhook secret’ları `.env` veya panel kasasında tutulmalı; repoya commit edilmemelidir. Sunucuda Git remote “read-only” deploy key kullanın. Production sunucusuna doğrudan dosya düzenleme (nano ile canlıda patch) alışkanlığını bırakın; acil hotfix bile bir commit ve deploy ile yapılmalı, aksi halde Git ile sunucu dosyaları senkron dışı kalır.

SSH erişimini [sunucu güvenliği rehberimizde](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) anlattığımız gibi Fail2ban ve UFW ile sıkılaştırın. Deploy kullanıcısı root olmamalı; site kullanıcısı yalnızca kendi dizinine erişebilmelidir.

### Panelze ile pratik başlangıç

Yeni bir projeye Git deploy eklemek için özet yol haritası:

1. [Panelze’ye başlarken](/docs/getting-started) sunucunuzu kurun ve paneli yükleyin.
2. Site oluşturun, domain ve SSL’i tanımlayın ([SSL ve DNS rehberi](/docs/ssl-dns-email)).
3. Git deposu URL’sini girin, deploy key’i Git sağlayıcıya ekleyin.
4. Staging sitesini ayrı oluşturun; webhook’ları dala göre ayırın.
5. İlk deploy’u staging’de test edin; production’a manuel veya onaylı otomatik geçiş yapın.

FTP’yi tamamen bırakmak ilk hafta alışkanlık gerektirir; ancak bir kez webhook ile sorunsuz deploy yaşadığınızda geri dönmek istemezsiniz. Ekip içi iletişim de netleşir: “canlıya çıktı mı?” sorusunun cevabı artık Git log’unda ve panel deploy geçmişinde görünür.

Daha fazla soru için [sık sorulan sorular](/#faq) bölümüne göz atabilir veya [fiyatlandırma](/pricing) sayfamızdan ihtiyacınıza uygun planı inceleyebilirsiniz. Kendi sunucunuzu henüz hazırlamadıysanız [kurulum rehberi](/setup) size yol gösterecektir.

Git deploy, hızın ötesinde güvenilirlik ve geri alınabilirlik sunar. FTP’ye veda etmek, profesyonel web operasyonunun doğal bir sonraki adımıdır.
