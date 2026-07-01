## Ubuntu 22.04 web sunucu kurulumu: eksiksiz kontrol listesi

Yeni bir VPS sipariş ettiniz; e-postada IP adresi ve root şifresi var. Şimdi ne? Ubuntu 22.04 LTS (Jammy Jellyfish) 2027’ye kadar standart destek alır; web hosting için hâlâ güvenilir bir tabandır. Bu checklist, sıfırdan üretime hazır bir web sunucusu kurmanız ve ardından Panelze hosting panelini devreye almanız için adım adım rehber niteliğindedir. Her adımı tamamladıkça işaretleyin; atlarsanız ileride güvenlik veya performans borcu ödersiniz.

### Ön hazırlık

- VPS sağlayıcısından Ubuntu 22.04 LTS imajı seçildi
- Domain DNS A kaydı sunucu IP’sine yönlendirildi (panel ve siteler için)
- Yerel makinede SSH istemcisi hazır
- Panelze kurulumu için [kurulum sayfası](/setup) ve [sunucu kurulum dokümantasyonu](/docs/server-setup) okundu

### 1. İlk giriş ve sistem güncellemesi

Root ile bağlanın:

```bash
ssh root@SUNUCU_IP
```

Hemen güncelleyin:

```bash
apt update && apt upgrade -y
```

Zaman dilimini Türkiye için ayarlayın:

```bash
timedatectl set-timezone Europe/Istanbul
```

Hostname anlamlı olsun (`web01.ajansiniz.com`); panel ve loglarda işinizi kolaylaştırır:

```bash
hostnamectl set-hostname web01
```

### 2. Sudo kullanıcısı oluşturma

Root ile günlük çalışmak risklidir. Yeni kullanıcı:

```bash
adduser deploy
usermod -aG sudo deploy
```

SSH anahtarı ile giriş için yerel makinenizden:

```bash
ssh-copy-id deploy@SUNUCU_IP
```

Bundan sonra root SSH ile girişi kapatacaksınız; önce `deploy` kullanıcısıyla anahtarsız girişi test edin.

### 3. SSH sertleştirme

`/etc/ssh/sshd_config` düzenleyin:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

Değişiklik sonrası:

```bash
sudo systemctl restart sshd
```

Yanlış yapılandırma kendinizi dışarıda bırakabilir; sağlayıcı konsolundan (VNC/serial) erişim yolunu önceden not edin. Detaylı güvenlik için [Fail2ban ve UFW rehberimiz](/blog/sunucu-guvenligi-fail2ban-ufw-rehberi) tamamlayıcıdır.

### 4. UFW firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

Panelze özel port kullanıyorsa (kurulum script’inde belirtilir) o portu da açın. Gereksiz port açmayın; yalnızca ihtiyaç duyulan servisler.

### 5. Swap (düşük RAM VPS’lerde)

4 GB altı RAM’de 2 GB swap önerilir:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Swap performansın yerine geçmez; ani bellek taşmasında servislerin ölmesini engeller.

### 6. Nginx kurulumu

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

`http://SUNUCU_IP` tarayıcıda “Welcome to nginx” göstermeli. Varsayılan site yapılandırmasını sonra Panelze yönetecek; şimdilik servisin ayakta olduğunu doğrulayın.

Temel güvenlik başlıkları ve `server_tokens off` gibi ayarlar [kurulum sonrası](/docs/post-install) dokümantasyonunda önerilir.

### 7. PHP-FPM (8.2 veya 8.3)

Ubuntu 22.04 depolarında PHP 8.1 varsayılandır; web projeleri için Ondřej Surý PPA ile 8.2/8.3 alın:

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml \
  php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
```

WordPress ve Laravel için bu uzantılar çoğu senaryoyu karşılar. Site başına farklı PHP sürümü Panelze kurulduktan sonra [PHP sürüm yönetimi](/blog/php-surumu-degistirme-performans) ile yapılır.

OPcache üretimde açık olmalı; `php.ini` içinde:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### 8. MariaDB kurulumu

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
```

Root şifresi belirleyin, anonim kullanıcıları kaldırın, uzaktan root girişini kapatın. Panelze kurulumu kendi veritabanlarını oluşturur; manuel DB yalnızca panel gerektiriyorsa gerekir.

Veritabanı yedekleme stratejinizi erken planlayın ([yedekleme rehberi](/docs/backups), [veritabanları](/docs/databases)).

### 9. Temel araçlar

```bash
sudo apt install -y git curl unzip zip fail2ban certbot
```

- **Git:** deploy ve Panelze güncellemeleri
- **Certbot:** Let’s Encrypt (Panelze çoğu SSL işini kendisi yapar; yedek olarak)
- **Fail2ban:** SSH brute-force koruması

### 10. Panelze kurulumu

Sunucu hazır. [Hızlı kurulum](/setup) sayfasındaki tek komut veya script ile Panelze’yi yükleyin. Kurulum genelde:

- Nginx sanal host şablonlarını yazar
- PHP-FPM pool’larını yapılandırır
- Panel veritabanını oluşturur
- Yönetici hesabı sorar

Kurulum bitince panel URL’sine tarayıcıdan gidin, ilk girişi yapın. [Başlangıç rehberi](/docs/getting-started) ilk site oluşturma adımlarını anlatır.

### 11. İlk site ve SSL

Panelde:

1. Site ekleyin, domain bağlayın ([site ve domain](/docs/sites-and-domains))
2. DNS A/AAAA kayıtlarının yayıldığını doğrulayın (`dig` veya online DNS checker)
3. Let’s Encrypt SSL etkinleştirin ([SSL dokümantasyonu](/docs/ssl-dns-email))

HTTPS zorunlu yönlendirme açık olsun. [SSL blog yazımız](/blog/lets-encrypt-ssl-hosting-panelinde) sorun giderme ipuçları içerir.

### 12. Yedekleme ve izleme

Kurulum günü yedekleme ayarlanmazsa “yarın yaparım” genelde unutulur. Panelze’de:

- Yerel yedek zamanlaması
- İsteğe bağlı Google Drive hedefi ([Drive yedekleme rehberi](/blog/google-drive-sunucu-yedekleme-rehberi))
- İlk manuel yedek ve test restore

Uptime ve disk izleme için harici servis veya basit cron + e-posta script’i ekleyin.

### 13. Git deploy (opsiyonel ama önerilir)

Geliştirme akışınız Git ise kurulum haftasında [Git deploy](/docs/git-deploy) yapılandırın. FTP alışkanlığını erken kırmak uzun vadede kazandırır ([Git deploy blog](/blog/git-deploy-ile-canli-site-guncelleme)).

### Kontrol listesi özeti

| Adım | Tamamlandı |
|------|------------|
| Sistem güncellemesi ve timezone | ☐ |
| Sudo kullanıcı + SSH anahtarı | ☐ |
| Root SSH ve parola girişi kapalı | ☐ |
| UFW (22, 80, 443) | ☐ |
| Swap (gerekirse) | ☐ |
| Nginx çalışıyor | ☐ |
| PHP-FPM 8.x + uzantılar | ☐ |
| MariaDB güvenli kurulum | ☐ |
| Fail2ban aktif | ☐ |
| Panelze kurulu | ☐ |
| İlk site + SSL | ☐ |
| Yedekleme zamanlandı | ☐ |

### Sık karşılaşılan sorunlar

**502 Bad Gateway:** PHP-FPM socket yolu Nginx config ile uyuşmuyor; Panelze yeniden yapılandırma veya `php8.3-fpm` servis durumu kontrol edin.

**Permission denied:** Web kök dizin sahibi site kullanıcısı olmalı; `www-data` veya panel kullanıcısı — Panelze varsayılanlarına güvenin, manuel `chmod 777` yapmayın.

**Disk doldu:** Log rotasyonu (`logrotate`), eski yedeklerin retention ile silinmesi.

**Panel erişilemiyor:** UFW’de panel portu, Nginx reverse proxy config.

### Üretim öncesi son kontroller

- Otomatik güvenlik güncellemeleri: `unattended-upgrades` isteğe bağlı
- Cron jobs panel ve site için tanımlı (Laravel scheduler, WP cron)
- [Panel rehberi](/docs/panel-guide) ile ekip üyelerine kısa eğitim
- [Fiyatlandırma](/pricing) ve lisans planınız net (ajans müşteri satışı için)

Ubuntu 22.04 üzerinde bu checklist’i takip ettiğinizde elinizde tekrarlanabilir, güvenli bir web sunucu tabanı olur. Panelze bu tabanın üzerine site yönetimini, SSL’i, yedeği ve deploy’u tek arayüzde toplar. Takıldığınız noktada [SSS](/#faq) bölümüne bakın; VPS panel seçimi için [karşılaştırma yazımız](/blog/vps-hosting-paneli-nasil-secilir) da faydalıdır.

Sunucu hazır; sıradaki adım ilk gerçek müşteri sitenizi açmak ve şablon iş akışınızı oluşturmak. İyi çalışmalar.
