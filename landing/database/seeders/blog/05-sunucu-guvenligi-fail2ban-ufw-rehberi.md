## Sunucuyu açtığınız anda botlar kapıyı çalıyor

Yeni bir VPS kiraladığınızda, SSH portuna (22) dakikalar içinde otomatik tarama başlar. Root parolası denemeleri, bilinen exploit aramaları ve panel portlarına yönelik istekler log dosyalarında hızla birikir. Bu “internetin normal gürültüsü”dür; ancak zayıf parola, açık port veya güncellenmemiş yazılımla birleştiğinde gerçek ihlale dönüşebilir.

Güvenlik tek bir araçla sağlanmaz; katmanlı savunma (defense in depth) gerekir. Bu rehberde UFW firewall ve Fail2ban ile temel ama etkili bir koruma katmanı kuruyoruz. SSH sertleştirme ve hosting paneli erişimini sıkılaştırma adımlarını da kapsıyoruz.

## Güvenlik zihniyeti: neyi koruyoruz?

Sunucunuzda değerli varlıklar:

- **Müşteri siteleri ve veritabanları**
- **E-posta ve kişisel veriler (KVKK kapsamı)**
- **SSH ve panel erişim kimlik bilgileri**
- **SSL özel anahtarları ve yedekler**

Hedef “%100 güvenlik” değil; saldırı maliyetini yükseltmek ve otomatik tehditleri erken durdurmaktır. Profesyonel saldırganlara karşı ek katmanlar (WAF, IDS, SIEM) gerekir; ancak Fail2ban + UFW çoğu otomatik brute-force senaryosunu engeller.

Panel kurmadan önce [sunucu kurulum rehberi](/docs/server-setup) ile temiz bir Ubuntu 22.04 LTS tabanı oluşturun.

## UFW: Uncomplicated Firewall

UFW, iptables üzerinde basit bir arayüz sunar. Varsayılan politika: gelen trafiği reddet, giden trafiğe izin ver.

### Temel kurulum

```bash
sudo apt update && sudo apt install ufw -y
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### Gerekli portları açın

```bash
sudo ufw allow 22/tcp comment 'SSH'
sudo ufw allow 80/tcp comment 'HTTP'
sudo ufw allow 443/tcp comment 'HTTPS'
```

Panelze varsayılan panel portunu kullanıyorsanız (örneğin 8443), yalnızca güvendiğiniz IP’lerden açmak daha güvenlidir:

```bash
sudo ufw allow from SIZIN_IP_ADRESINIZ to any port 8443 proto tcp comment 'Panelze panel'
```

Genel internete panel portu açmak risklidir; mümkünse VPN veya SSH tüneli kullanın. [Panel kılavuzu](/docs/panel-guide) erişim önerilerini içerir.

### UFW’yi etkinleştirin

```bash
sudo ufw enable
sudo ufw status verbose
```

**Uyarı:** SSH portunu açmadan UFW’yi etkinleştirirseniz kendinizi dışarıda bırakırsınız. Önce 22/tcp kuralını eklediğinizden emin olun.

### İleri düzey kurallar

Belirli bir IP’den SSH:

```bash
sudo ufw delete allow 22/tcp
sudo ufw allow from GUVENILIR_IP to any port 22 proto tcp
```

Rate limiting (bağlantı seli koruması):

```bash
sudo ufw limit 22/tcp
```

Bu, aynı IP’den kısa sürede çok fazla SSH bağlantı denemesini kısıtlar.

## Fail2ban: brute-force engelleyici

Fail2ban log dosyalarını izler; başarısız giriş denemelerini tespit edince ilgili IP’yi firewall üzerinden geçici olarak banlar.

### Kurulum

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### SSH jail yapılandırması

`/etc/fail2ban/jail.local` dosyası oluşturun:

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
ignoreip = 127.0.0.1/8 SIZIN_IP_ADRESINIZ

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400
```

- **maxretry:** Kaç başarısız denemeden sonra ban.
- **bantime:** Ban süresi (saniye); 86400 = 24 saat.
- **ignoreip:** Asla banlanmayacak IP’ler (ofis IP’niz).

```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status sshd
```

### Panel için özel jail

Panel giriş logları ayrı bir dosyadaysa özel filter tanımlanabilir. Panelze Nginx access log’unu izleyen bir jail, panel brute-force denemelerini de engeller. [Kurulum sonrası rehber](/docs/post-install) panel güvenlik adımlarını listeler.

## SSH sertleştirme

Firewall ve Fail2ban’dan önce veya birlikte SSH yapılandırmasını sıkılaştırın.

### Parola yerine anahtar tabanlı giriş

Yerel makinenizde:

```bash
ssh-keygen -t ed25519 -C "sunucu-anahtar"
ssh-copy-id kullanici@sunucu_ip
```

Sunucuda `/etc/ssh/sshd_config`:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

```bash
sudo systemctl restart sshd
```

**Kritik:** Anahtarlı girişi test etmeden parolayı kapatmayın; aksi halde sunucuya erişimi kaybedersiniz.

### Root yerine sudo kullanıcısı

```bash
adduser deploy
usermod -aG sudo deploy
```

Günlük işlemleri `deploy` kullanıcısıyla yapın; root yalnızca acil durumda.

### SSH portunu değiştirmek (opsiyonel)

Port 22 yerine 2222 gibi bir port “security through obscurity” sağlar; tek başına yeterli değildir ama otomatik taramaların çoğunu atlatır. UFW’de yeni portu açmayı unutmayın.

## Hosting paneli erişim güvenliği

Panel, sunucunuzun kontrol merkezidir; web üzerinden herkese açık bırakılmamalıdır.

### Önerilen uygulamalar

1. **Güçlü parola veya 2FA:** Panel hesabında benzersiz, uzun parola kullanın.
2. **IP kısıtlaması:** UFW veya panel ayarlarıyla yalnızca ofis IP’sinden erişim.
3. **HTTPS zorunlu:** Panel üzerinden Let's Encrypt veya self-signed yerine geçerli SSL. [SSL rehberi](/blog/lets-encrypt-ssl-hosting-panelinde) web siteleri için; panel subdomain’i için de geçerlidir.
4. **Güncel tutun:** Panel ve sistem paketlerini düzenli güncelleyin.
5. **Varsayılan kullanıcı adını değiştirin:** `admin` hedef olur.

Panelze kurulumu [setup sayfası](/setup) üzerinden yapıldığında, kurulum sihirbazı güçlü parola ve temel güvenlik adımlarını hatırlatır.

## Web katmanı: Nginx ve PHP

### Gizli dosyalara erişimi engelleyin

Nginx yapılandırmasında:

```nginx
location ~ /\. {
    deny all;
}
```

`.env`, `.git`, `.htaccess` gibi dosyalar web’den okunamaz.

### PHP güvenliği

`disable_functions` ile tehlikeli fonksiyonları kapatın (panel bazen bunu yönetir). `expose_php = Off` ile PHP sürüm bilgisini header’dan gizleyin.

WordPress siteleri için ek önlemler [WordPress hosting rehberinde](/blog/wordpress-hosting-nasil-secilir-kurulum) detaylandırılmıştır.

## İzleme ve log yönetimi

Güvenlik kurulumu “bir kez yap, unut” değildir.

### Düzenli kontroller

- `sudo fail2ban-client status` — aktif ban listesi
- `sudo ufw status` — firewall kuralları
- `/var/log/auth.log` — SSH giriş denemeleri
- Panel güncelleme bildirimleri

### Logrotate

Log dosyaları diski doldurabilir; `logrotate` varsayılan olarak aktiftir. Disk %90 dolulukta uyarı kurun.

### Yedekleme

Güvenlik ihlali sonrası temiz geri dönüş yalnızca yedekle mümkündür. [Yedekleme dokümantasyonu](/docs/backups) otomatik plan oluşturmayı anlatır.

## Gerçek senaryo: brute-force saldırısı

Gece 03:14’te bir VPS’e bağlı Fail2ban 847 başarısız SSH denemesi tespit etti; üç farklı IP 24 saat banlandı. Sunucu root parolası zayıf değildi; anahtar tabanlı giriş aktifti. Parola ile giriş kapalı olduğu için saldırı başarısız kaldı — ancak log gürültüsü ve kaynak tüketimi Fail2ban olmadan devam edecekti.

İkinci örnek: Panel portu internete açık bırakılmış bir sunucuda panel giriş denemeleri arttı. UFW kuralı yalnızca iki ofis IP’sine izin verecek şekilde güncellendi; denemeler anında kesildi.

## Güvenlik kontrol listesi (yeni VPS)

Kurulumdan sonra bu listeyi işaretleyin:

- [ ] Sistem güncellemesi (`apt update && apt upgrade`)
- [ ] Sudo kullanıcısı oluşturuldu, root SSH kapatıldı
- [ ] SSH anahtar tabanlı giriş, parola kapalı
- [ ] UFW: 22, 80, 443 açık; gereksiz portlar kapalı
- [ ] Fail2ban SSH jail aktif
- [ ] Panel erişimi IP ile kısıtlandı veya VPN arkasında
- [ ] Let's Encrypt SSL tüm sitelerde aktif
- [ ] Otomatik yedekleme planlandı
- [ ] [Kurulum komutları](/docs/install-commands) ve [mimari](/docs/architecture) dokümantasyonu okundu

## Panelze ile entegre güvenlik yaklaşımı

Panelze, sunucu yönetimini kolaylaştırırken güvenlik sorumluluğunu ortadan kaldırmaz. Panel üzerinden site izolasyonu (ayrı sistem kullanıcıları), PHP sürüm yönetimi ve SSL otomasyonu saldırı yüzeyini düzenler; ancak UFW ve Fail2ban gibi temel katmanlar hâlâ sunucu düzeyinde sizin yapılandırmanızı bekler.

[VPS panel seçimi](/blog/vps-hosting-paneli-nasil-secilir) yaparken panelin güvenlik özelliklerini (2FA, IP kısıtlama, audit log) de değerlendirin. [Fiyatlandırma](/pricing) sayfasında altyapı maliyetini planlarken güvenlik araçları için ek bütçe ayırmayı unutmayın — ücretsiz araçlar (UFW, Fail2ban) çoğu senaryo için yeterlidir.

## Sık sorulan sorular

**Fail2ban kendi IP’mi banlarsa ne olur?**  
`ignoreip` listesine ofis IP’nizi ekleyin. Banlandıysanız sunucu sağlayıcının KVM/konsol erişimiyle `fail2ban-client unban IP` çalıştırın.

**UFW Docker ile çakışır mı?**  
Docker kendi iptables kurallarını ekler; dikkatli yapılandırma gerekir. Basit hosting sunucularında Docker kullanmıyorsanız sorun olmaz.

**Cloudflare kullanıyorsam UFW gerekli mi?**  
Evet. CDN önbellek katmanıdır; origin sunucu hâlâ doğrudan erişilebilir olabilir. Origin IP gizleme ve firewall birlikte düşünülmeli.

Daha fazla soru için [SSS bölümümüze](/#faq) bakın.

## Sonuç

Fail2ban ve UFW, yeni VPS’inize dakikalar içinde eklenebilecek, ücretsiz ve etkili güvenlik katmanlarıdır. SSH sertleştirme ve panel erişim kısıtlamasıyla birleştiğinde otomatik brute-force saldırılarının büyük çoğunluğu engellenir. Hosting paneli — Panelze dahil — operasyonu kolaylaştırır; ancak sunucunun kapısını kilitlemek sizin elinizde.

[Getting started rehberi](/docs/getting-started) ile temiz kuruluma başlayın; SSL için [Let's Encrypt yazısına](/blog/lets-encrypt-ssl-hosting-panelinde) göz atın. Güvenlik bir kerelik proje değil, sürekli alışkanlıktır.
