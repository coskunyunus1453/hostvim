<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DocPage;
use App\Models\Plan;
use App\Models\SitePage;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        SitePage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'setup'],
            [
                'title' => 'Kurulum rehberi',
                'meta_description' => 'Panelze Engine ve panel kurulumu: ön koşullar, güvenlik, ortam değişkenleri ve doğrulama adımları.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## Bu rehberde neler var?

Panelze yığını **iki ana parçadan** oluşur ve üretimde birlikte çalışması gerekir:

| Bileşen | Rol |
| --- | --- |
| **Panelze Engine** | Sunucuda Nginx, PHP-FPM, sertifika ve site düzeyi işlemleri yürüten servis (genelde `127.0.0.1:9090` gibi bir adresten API dinler). |
| **Panel (Laravel)** | Tarayıcıdan yönetim, kullanıcı/rol, lisans ve Engine’e giden API çağrıları. |

Bu sayfa **genel kurulum akışını** özetler; mimari ve ürün özellikleri için [dokümantasyon](/docs) altındaki [Mimari](/docs/architecture) ve [Panelze yetenekleri](/docs/platform-features) sayfalarına bakın.

---

## Ön koşullar

### Sunucu ve sistem

- **İşletim sistemi:** Temiz veya bakımlı bir **Ubuntu 22.04 LTS** önerilir; ekibiniz başka bir LTS dağıtımı onayladıysa ona uygun paket adlarını kullanın.
- **Donanım (kılavuz):** Küçük ekipler için **2 vCPU / 4 GB RAM** genelde yeterli başlangıç değeridir; çok sayıda site veya yoğun PHP iş yükünde kaynakları artırın.
- **Erişim:** `root` veya güvenilir **sudo** yetkisi; uzak SSH için parola yerine **anahtar tabanlı giriş** tercih edin.
- **Saat ve DNS:** Sunucu saatinin doğru olması (NTP); üretim alan adlarınızın **A/AAAA** kayıtları sunucunuzu göstermeli (Let’s Encrypt ve canlı trafik için).

### Güvenlik (kurulum öncesi)

- Sunucuda yalnızca ihtiyaç duyulan portları açın (başlangıçta genelde **22**, **80**, **443**; paneli ayrı bir porttan yayınlıyorsanız onu da tanımlayın).
- Mümkünse paneli yalnızca **VPN**, sabit IP veya **geçici SSH tüneli** üzerinden erişilebilir yapın; en azından yönetim hesaplarında **2FA** ve güçlü oturum politikası kullanın.
- Kurulumdan önce bir **snapshot / yedek** alın; üzerinde önemli veri olan mevcut sunucuları “üstüne yazmadan” önce yedek bulundurun.

---

## Hızlı kurulum

Aşağıdaki **Güncel kurulum komutları** bölümünde deploy betikleriyle uyumlu tüm komutlar listelenir (tek satır, Community, Pro, elle kurulum, güncelleme ve onarım).

> **Üretim:** Betiği çalıştırmadan önce imza / checksum doğrulaması ve betik içeriğinin incelemesi şart sayılmalıdır. Test ortamında önce deneyin. Komutları yalnızca Debian/Ubuntu VPS üzerinde root veya sudo ile çalıştırın.

Kurulum betiği tipik olarak şunları yapar: `git` ile `/var/www/panelze` altına kodu çeker, `deploy/bootstrap/install-production.sh` ile Nginx, PHP, MariaDB, Engine derlemesi ve frontend build çalıştırır. İlk yönetici bilgisi `/root/panelze-admin-login.txt` dosyasına yazılır.

---

## Panel ortam değişkenleri (Engine bağlantısı)

Panel deposundaki `.env` dosyasında Engine ile güvenli iletişim için tipik olarak şu alanlar kullanılır:

- `ENGINE_API_URL` — Engine API taban adresi (örn. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Engine ile panel arasında paylaşılan dahili anahtar.
- `ENGINE_API_SECRET` — İmzalı istekler ve web terminal JWT gibi akışlar için Engine `security` yapılandırmasıyla eşleşmelidir.

Bu değerler, aynı sunucudaki **Engine yapılandırması** ile birebir uyumlu olmalı; aksi halde site oluşturma, SSL veya terminal işlemleri başarısız olur.

Lisanslama için `LICENSE_SERVER_URL`, `LICENSE_KEY` vb. alanlar kullanılabilir; birçok kurulumda anahtar **panel içindeki lisans ekranından** girilir.

---

## Kurulum sonrası kontrol listesi

1. Panel ön yüzüne gidin ve ilk **yönetici** hesabını oluşturun (veya dağıtımınızdaki ilk oturum adımını tamamlayın).
2. HTTP(S) sonlandırıcıyı doğrulayın; üretimde **HTTPS zorunlu** olmalı.
3. Engine–panel bağlantısını test edin (ör. staging alan adıyla site açma veya panel üzerinden `GET /api/health` — yanıtta `status: ok` içeren bir JSON beklenir).
4. İlk üretim trafiğini açmadan önce **test subdomain** veya düşük riskli alan adıyla DNS, sertifika ve PHP sürümünü doğrulayın.
5. Yedekleme hedeflerini ve güncelleme planını (Engine + panel) netleştirin.

Sorun giderme: firewall, yanlış `ENGINE_*` değerleri, DNS yayılımı ve saat kayması en sık kök nedenlerdir. [Blog](/blog) ve ana sayfadaki [SSS](/#faq) bölümüne de göz atın.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'setup'],
            [
                'title' => 'Installation guide',
                'meta_description' => 'Install Panelze Engine and panel: prerequisites, hardening, environment variables, and post-install verification.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## What this guide covers

The Panelze stack has **two cooperating parts** that must be installed and configured together:

| Component | Role |
| --- | --- |
| **Panelze Engine** | Runs on the server and executes changes for Nginx, PHP-FPM, certificates, and per-site operations (typically exposes an HTTP API, e.g. on `127.0.0.1:9090`). |
| **Panel (Laravel)** | Browser UI, user/role management, licensing, and authenticated calls into the Engine. |

This page walks through the **end-to-end install flow**. For deeper architecture and product depth, read [Architecture](/docs/architecture) and [Platform capabilities](/docs/platform-features) under [Documentation](/docs).

---

## Prerequisites

### Server baseline

- **OS:** A clean, patched **Ubuntu 22.04 LTS** is recommended; other LTS distros are fine if your team already standardised on them (adjust package names and service units accordingly).
- **Sizing (rule of thumb):** **2 vCPU / 4 GB RAM** is a reasonable starting point for small fleets; increase CPU/RAM for heavy PHP workloads or very large numbers of sites.
- **Access:** `root` or passwordless **sudo**; prefer **SSH keys** over passwords for remote administration.
- **Time & DNS:** Accurate system time (NTP); production hostnames must resolve to this server (**A/AAAA**) before you rely on Let’s Encrypt and live traffic.

### Security before you install

- Open only required ports at the edge (typically **22**, **80**, **443**, plus whatever port serves the panel if not behind 443).
- Where practical, restrict the panel to a **VPN**, allow-listed IPs, or short-lived **SSH tunnels**; enforce **2FA** and strong session policy on admin-class accounts.
- Take a **snapshot or offline backup** before bootstrap scripts alter system packages or services.

---

## Quick install

Use the **Current install commands** section below — it lists every supported path (one-liner, Community, Pro, manual git clone, updates, and repair) kept in sync with `deploy/` scripts.

> **Production:** Treat every `curl | bash` as privileged code execution — verify checksums / signatures and review the script before it touches production. Always pilot in staging on Debian/Ubuntu with root or sudo.

The installer typically clones into `/var/www/panelze`, then runs `deploy/bootstrap/install-production.sh` (Nginx, PHP, MariaDB, Engine build, frontend). First admin credentials are written to `/root/panelze-admin-login.txt`.

---

## Panel environment (Engine linkage)

In the panel’s `.env`, the following variables commonly bind the UI to the Engine (names are illustrative but match the project’s layout):

- `ENGINE_API_URL` — Base URL for Engine API calls (e.g. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Shared internal key negotiated between Engine and panel.
- `ENGINE_API_SECRET` — Must align with Engine `security` settings for signed flows (e.g. web terminal JWT).

If any of these diverge from the **live Engine configuration**, provisioning, TLS, or terminal sessions will fail mysteriously.

Licensing may involve `LICENSE_SERVER_URL`, `LICENSE_KEY`, etc.; many deployments paste the key in the **in-panel license** screen instead of keeping keys only in `.env`.

---

## Post-install checklist

1. Open the panel, complete bootstrap, and create (or import) the first **administrator** account.
2. Terminate TLS correctly at Nginx/Apache; production user traffic should be **HTTPS-only**.
3. Prove Engine connectivity with a harmless action — e.g. create a **staging site**, issue a certificate, or call `GET /api/health` on the panel (`status` should be `ok` in JSON).
4. Before production cutover, validate DNS, TLS, and PHP versions on a **throwaway subdomain**.
5. Configure backup targets/schedules and document how you will **roll Engine and panel updates**.

Troubleshooting tips: firewall rules, typoed `ENGINE_*` values, DNS/TTL drift, and clock skew are the usual culprits. See the [blog](/blog), the landing [FAQ](/#faq), and nested docs for next steps.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'pricing'],
            [
                'title' => 'Fiyatlandırma özeti',
                'meta_description' => 'Freemium, Pro ve Vendor katmanları; limitler, lisans ve ödeme akışı özeti.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
Bu metin, **fiyatlandırma** sayfasındaki giriş bölümünü besler. Aşağıdaki plan kartları ise yönetim panelinde tanımlı kayıtlardan **otomatik üretilir**; buradaki kopya ürün yönünü özetler.

## Planların anlamı

- **Freemium** — Tek sunucu, temel site/domain/SSL/terminal akışları ve makul kota ile pilot veya küçük iş yükleri için. Ücret alınmadan başlarsınız; yükseltme aynı panel üzerinden yapılır.
- **Pro lisans** — Ajanslar, yüksek trafik veya sıkı SLA beklentisi olan müşteriler için genişletilmiş limitler, gelişmiş izleme ve öncelikli destek sütunları (kart üzerindeki maddeler veritabanından gelir).
- **Vendor / White-label** — Kendi markanızla hizmet vermek, özel fiyat, hukuki çerçeve ve yol haritası ortaklığı için satış ekibiyle **kurumsal teklif** üzerinden ilerlenir.

## Lisans ve ödeme

- Çevrimiçi ödeme **Stripe** ile yapılabilir; başarılı işlemden sonra lisans anahtarı e-posta ile iletilir.
- Anahtar çoğu zaman **panel → lisans** ekranına yapıştırılır; merkezi doğrulama için `LICENSE_SERVER_URL` yapılandırması kullanılabilir.

**Kesin sayısal limitler** (site adedi, yedek saklama, API hızı vb.) paneldeki **plan / lisans** kayıtlarında tutulur; bu sayfadaki rakamlar yalnızca özet niteliğindedir.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'pricing'],
            [
                'title' => 'Pricing overview',
                'meta_description' => 'Freemium, Pro, and Vendor tiers; how cards, licensing, and Stripe checkout fit together.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
This copy powers the introductory blurb on the public **pricing** page. Feature bullets on each card are generated from the database rows managed in the landing admin—what you see here is narrative context.

## How the tiers differ

- **Freemium** — One server, core hosting workflows (sites, TLS, databases, limited observability) with conservative quotas. Zero licence fee to start; upgrades keep the same panel tenant.
- **Pro licence** — Higher ceilings for agencies and demanding workloads: richer monitoring, security profiles, and support tiers (exact bullets pull from the `plans` table).
- **Vendor / white-label** — Brand packaging, custom commercials, and roadmap partnership. Reach sales for an enterprise quote when you resell Panelze to your own customers.

## Licensing & payments

- Card checkout can run through **Stripe**; successful orders trigger transactional email with licence material.
- Keys are usually pasted into the in-panel **License** screen. Large deployments can pin a central hub via `LICENSE_SERVER_URL`.

Authoritative numeric limits (sites, backup retention, API throttles) always live beside the licensing module—treat marketing tables as summaries, not contracts.
MD
            ]
        );

        $rootTr = DocPage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'getting-started'],
            [
                'parent_id' => null,
                'title' => 'Başlangıç',
                'is_published' => true,
                'sort_order' => 0,
                'content' => <<<'MD'
# Panelze dokümantasyonu

Panelze; Linux üzerinde **Engine + Panel** bileşenlerinden oluşan bir hosting kontrol paneli yığınıdır. Bu sitedeki dokümanlar; kurulum, mimari, yetenekler ve güvenli operasyon için yol gösterir.

## Nereden başlamalıyım?

| Konu | Sayfa |
| --- | --- |
| Kurulum ve ortam değişkenleri | [Kurulum rehberi](/setup) |
| Paket ve firewall sırası | [Sunucu kurulumu](/docs/server-setup) |
| Bileşenler ve veri akışı | [Mimari](/docs/architecture) |
| Panelde neler yapılabilir? | [Panelze yetenekleri](/docs/platform-features) |

**Başlangıç** altında yer alan sayfalar, üretim öncesi kontrol listesi ve sunucu hazırlığını adım adım anlatır. Sol taraftaki hiyerarşi veya doğrudan bağlantılarla ilerleyebilirsiniz.
MD
            ]
        );

        $rootEn = DocPage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'getting-started'],
            [
                'parent_id' => null,
                'title' => 'Getting started',
                'is_published' => true,
                'sort_order' => 0,
                'content' => <<<'MD'
# Panelze documentation

Panelze is a Linux hosting control stack composed of **Engine + Panel**. These guides explain installation, architecture, platform capabilities, and safe day-2 operations.

## Where should I start?

| Topic | Page |
| --- | --- |
| Install flow & environment wiring | [Installation guide](/setup) |
| OS prep, firewall, ordering | [Server setup](/docs/server-setup) |
| Components & trust boundaries | [Architecture](/docs/architecture) |
| What the product can do | [Platform capabilities](/docs/platform-features) |

Pages nested under **Getting started** focus on pre-flight checks and server hardening. Use the sidebar tree or jump directly via the links above.
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'server-setup'],
            [
                'parent_id' => $rootTr->id,
                'title' => 'Sunucu kurulumu',
                'meta_description' => 'Ubuntu sunucu hazırlığı, firewall, saat senkronizasyonu ve Panelze bootstrap sonrası doğrulama.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## Amaç

Bu sayfa, Panelze bootstrap betiğini çalıştırmadan önceki **sunucu hazırlığını** ve betik sonrası **doğrulama** adımlarını toplar. Yönergeler Ubuntu tabanlı dağıtımlar içindir; başka bir aile kullanıyorsanız paket ve servis adlarını eşdeğerleriyle değiştirin.

---

## 1. Sistem güncellemesi ve temel paketler

```bash
sudo apt update && sudo apt upgrade -y
```

Uzak erişim için **OpenSSH sunucusunun** çalıştığından ve yalnızca güvendiğiniz IP’lerden (veya VPN üzerinden) erişilebildiğinizden emin olun.

---

## 2. Saat ve zaman dilimi

TLS ve Let’s Encrypt doğrulamaları doğru sistem saatine bağlıdır:

```bash
timedatectl status
```

Gerekirse doğru zaman dilimini ayarlayın ve **NTP** senkronunun *active* olduğunu doğrulayın.

---

## 3. Firewall (örnek: UFW)

HTTP(S) ve SSH dışında gelen trafiği kapatın. Tipik başlangıç:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# Paneli ayrı bir TCP portunda dinletiyorsanız o portu da ekleyin
sudo ufw enable
sudo ufw status verbose
```

> Üretimde paneli yalnızca iç ağ veya VPN’den erişilebilir yapmak sık tercih edilen bir sertleştirmedir.

---

## 4. Panelze bootstrap

Güncel komutlar sayfanın altındaki **Kurulum komutları** bölümünde listelenir. Önerilen giriş:

- **Tek satır:** `curl -fsSL https://get.panelze.sh | bash`
- **Community:** GitHub `install-community.sh` → `install.sh` → `install-production.sh`

Betiğin tamamlandıktan sonra:

- `sudo systemctl status panelze-engine` — Engine servisi **active** olmalı
- `sudo cat /root/panelze-admin-login.txt` — ilk yönetici e-posta/parola
- Tarayıcıdan panel URL’si (Nginx varsayılanında sunucu IP veya `SERVER_NAME`)

---

## 5. Panel `.env` ve Engine eşlemesi

`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY` ve `ENGINE_API_SECRET` değerleri Engine tarafındaki yapılandırma ile **aynı** olmalıdır. Yerel geliştirmede `ENGINE_API_URL` sıklıkla `http://127.0.0.1:9090` biçimindedir; üretimde TLS terminasyonu ve geri plandaki gRPC/HTTP adresleri farklı olabilir.

---

## 6. İlk oturum ve sağlık kontrolleri

1. Tarayıcıdan panele gidin; ilk yöneticiyi oluşturun ve **parola + 2FA** politikanızı uygulayın.
2. İsteğe bağlı: `GET /api/health` uç noktası (panel API önekleri dağıtıma göre `/api/health`) JSON içinde `status: ok` döndürmelidir.
3. Staging alan adıyla bir site oluşturup sertifika çıkışını ve PHP sürümünü test edin.

Sorun çıkarsa günlükleri (panel `storage/logs`, Engine unit journal) ve firewall kurallarını birlikte kontrol edin.
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'server-setup'],
            [
                'parent_id' => $rootEn->id,
                'title' => 'Server setup',
                'meta_description' => 'Prepare Ubuntu (or your distro), harden SSH and firewall, run bootstrap, wire Engine env vars, verify health.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## Scope

Use this checklist before and after the Panelze bootstrap installer. Commands assume a **Debian/Ubuntu**-style host—swap in the equivalent packages/services for RHEL-derived distros if that is your standard.

---

## 1. Patch baseline & SSH hygiene

```bash
sudo apt update && sudo apt upgrade -y
```

Ensure **OpenSSH** is available only to trusted networks (bastion, VPN, or IP allow-lists). Prefer keys instead of static passwords.

---

## 2. Clock sync

TLS issuance and OCSP rely on accurate time:

```bash
timedatectl status
```

Fix the timezone if needed and confirm NTP synchronization is active.

---

## 3. Firewall sketch (UFW example)

Allow only what must be public. A common template:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# If the panel listens on a dedicated TCP port, allow that too
sudo ufw enable
sudo ufw status verbose
```

Many teams keep the panel off the public Internet entirely (VPN-only). That is stronger than opening another arbitrary port to the world.

---

## 4. Bootstrap Panelze

Exact commands are listed in the **Install commands** block at the bottom of this page. Recommended entry points:

- **One-liner:** `curl -fsSL https://get.panelze.sh | bash`
- **Community:** GitHub `install-community.sh` chains into `install.sh` and `install-production.sh`

After the script finishes:

- `sudo systemctl status panelze-engine` — Engine unit should be **active**
- `sudo cat /root/panelze-admin-login.txt` — first admin email/password
- Open the panel URL in a browser (server IP or your `SERVER_NAME` in Nginx)

---

## 5. Wire `.env` to the Engine

`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY`, and `ENGINE_API_SECRET` must match the Engine configuration on **that same node**. Local stacks often use `http://127.0.0.1:9090` for `ENGINE_API_URL`, but production may terminate TLS elsewhere—mirror whatever your operators documented.

---

## 6. First login & validation

1. Hit the panel URL, finish onboarding, and enforce MFA/password policy for admins.
2. Hit `GET /api/health` (prefixed according to your deployment—commonly `/api/health`) and expect JSON with `status: ok`.
3. Create a throwaway site on a staging hostname to validate DNS + ACME + PHP selection.

If anything fails, inspect the panel log under `storage/logs`, the Engine journal via `journalctl`, and re-check firewall rules—those three catch the majority of incidents.
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'platform-features'],
            [
                'parent_id' => $rootTr->id,
                'title' => 'Panelze yetenekleri',
                'meta_description' => 'Site, domain, SSL, veritabanı, yedek, e-posta, cron, izleme ve lisans — panel özellikleri özeti.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
## Genel bakış

Panelze **müşteri paneli**, alan adı ve site yaşam döngüsünü tek yerden yönetmek için tasarlanmıştır. Arayüz, arka planda **Engine** ile konuşan bir Laravel uygulamasıdır; Engine gerçek sunucu değişikliklerini (Nginx, PHP-FPM, sertifikalar vb.) uygular.

Yetenekler, **rol ve izin modeline** göre kısıtlanır (ör. site oluşturma, veritabanı yazma, yedek alma). Aşağıdaki liste ürün yönünü özetler; tam API yüzeyi sürüme göre genişleyebilir.

---

## Çekirdek hosting

- **Siteler ve alan adları:** Çoklu site; ek subdomain ve alias yönetimi; durum ve sunucu eşleştirme.
- **Web yığını:** PHP sürüm seçimi, document root, Nginx/Apache sanal host içerikleri (gelişmiş modlarda düzenleme ve geri alma).
- **SSL / TLS:** Let’s Encrypt ile sertifika çıkarma, yenileme, iptal; gerektiğinde manuel sertifika yolları.

## Veri ve dosyalar

- **Veritabanları:** MySQL/MariaDB ve PostgreSQL için kullanıcı oluşturma, yetki, içe/dışa aktarma ve parola rotasyonu (sunucu tarafı `MYSQL_*` / `POSTGRES_*` provizyon bayraklarına bağlı).
- **Dosya yöneticisi:** Gezinme, düzenleme, yükleme, sıkıştırma ve çöp kutusu ile geri yükleme (domain bazlı kota politikalarına tabi).
- **Yedekleme:** Anlık ve zamanlanmış yedekler; hedefler ve politikalar; gerektiğinde geri yükleme akışları.

## İletişim ve güvenlik

- **E-posta ve yönlendirme:** Alan adına bağlı posta kutuları ve forwarder’lar.
- **FTP:** İsteğe bağlı klasik FTP hesapları (domain kapsamında).
- **DNS kayıtları:** Basit bölge düzenleme (yetki verildiğinde).
- **Cron:** Kullanıcı düzeyinde zamanlanmış görevler ve çalıştırma geçmişi.
- **İzleme:** Özet sağlık bilgisi, site bazlı durum ve sunucu düzeyinde metrikler (okuma yetkisine bağlı).
- **Kimlik doğrulama:** Oturum açma, parola sıfırlama, isteğe bağlı **2FA** (yönetici politikalarında `ENFORCE_ADMIN_2FA` gibi bayraklarla sıkılaştırılabilir).

## Operasyon ve entegrasyon

- **Dağıtım / webhooks:** Siteler için CI/CD tarzı tetikleyiciler (yetkiye bağlı).
- **Lisanslama:** Merkezi lisans sunucusu URL’si ve anahtar; Stripe faturalandırma ile entegre edilebilir dağıtımlar için hazırlıklar.
- **WHMCS / bayi:** İsteğe bağlı modül ve çok kiracılı senaryolar (kurulumunuza göre açılır).

---

## Freemium ve Pro’dan ne beklenir?

Özet seviyede **Freemium** tek sunucu ve temel limitlerle başlamanıza izin verir; **Pro** daha geniş site/izleme/destek ihtiyaçları içindir. Kesin sayısal limitler paneldeki **lisans / plan** ekranında güncellenir — bu dokümandaki metinler pazarlama özetidir.

Daha teknik ayrıntı için [Mimari](/docs/architecture) sayfasına bakın.
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'platform-features'],
            [
                'parent_id' => $rootEn->id,
                'title' => 'Platform capabilities',
                'meta_description' => 'Sites, SSL, databases, backups, email, cron, monitoring, licensing—what Panelze exposes end-to-end.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
## Overview

The Panelze **customer panel** is a Laravel application that orchestrates day-to-day hosting operations. Persistent changes land on the host through the **Engine**, which applies Nginx/PHP-FPM/Let’s Encrypt mutations and enforces quotas.

Authorisation is **ability-based**—features below map to coarse capability groups (sites, databases, backups, etc.). The public API surface evolves per release; treat this page as the product map, not an endpoint manifest.

---

## Core hosting

- **Sites & domains:** Multi-site accounts, subdomains, aliases, suspend/resume flows, and server placement where multi-node setups exist.
- **Web stack controls:** PHP version selection, document roots, and editable vhost text for Nginx/Apache with guardrails and revert paths.
- **TLS lifecycle:** Issue, renew, revoke, or attach manual certificates—typically backed by Let’s Encrypt with admin-provided contact email defaults.

## Data plane & files

- **Databases:** MySQL/MariaDB and PostgreSQL flows for create/drop users, granular privileges, imports/exports, and credential rotation (subject to `MYSQL_*` / `POSTGRES_*` provisioning toggles on the Engine).
- **File manager:** Browse, edit, upload, archive/unarchive, trash/restore with throttles to protect IO.
- **Backups:** On-demand snapshots, scheduled policies, remote destinations, and selective restores.

## Messaging & edge security

- **Mailbox + forwarding:** Per-domain mail users and forwarders where the mail stack is enabled.
- **FTP accounts:** Classic FTP where policy allows it (scoped to a domain path).
- **DNS records:** Lightweight record editing for zones delegated to the integration.
- **Cron:** User-defined jobs with safety rails and execution history.
- **Observability:** Per-user summaries, per-site health, and deeper server metrics for operators with monitoring permissions.
- **Identity security:** Password policies, Sanctum tokens for API access, optional **TOTP 2FA**, and stricter admin enforcement via settings such as `ENFORCE_ADMIN_2FA`.

## Day-2 automation & GTM

- **Deploy hooks:** Webhook-driven pipelines for modern application releases when enabled for a site.
- **Licensing & billing:** Configurable license hub URL, Stripe keys for checkout, and email flows that deliver keys post-payment.
- **WHMCS / reseller:** Optional provisioning modules and multi-tenant knobs for larger hosters.

---

## Freemium vs licensed tiers

**Freemium** is meant for single-box pilots with conservative limits. **Pro** unlocks higher ceilings for agencies and busy workloads. Authoritative numbers always live in the in-panel **plan / license** module—marketing blurbs on the landing site are summaries only.

For trust-boundary detail, continue with [Architecture](/docs/architecture).
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'architecture'],
            [
                'parent_id' => null,
                'title' => 'Mimari genel bakış',
                'meta_description' => 'Engine, panel ve müşteri veritabanları; kimlik doğrulama, lisans ve güven sınırları.',
                'is_published' => true,
                'sort_order' => 5,
                'content' => <<<'MD'
## Üst düzey bileşenler

| Katman | Sorumluluk |
| --- | --- |
| **Panelze Engine** | Sunucu üzerinde Nginx/Apache sanal hostları, PHP-FPM havuzlarını, dosya yollarını ve Let’s Encrypt yaşam döngüsünü uygular; kota ve politika uygular. |
| **Panel (Laravel + Horizon/queue)** | Web ve API katmanı: kimlik (`sanctum`), rol/ability modeli, faturalama (Stripe), lisans doğrulama, müşteri arayüzü. |
| **Panel veritabanı** | Kiracı, site, domain, kullanıcı ve operasyonel meta veriler — **müşteri sitelerinin kendi MySQL/Postgres veritabanlarından ayrıdır**. |
| **Müşteri veritabanları** | Engine aracılığıyla oluşturulan MySQL/MariaDB veya PostgreSQL örnekleri; yedekleme ve içe/dışa aktarma panel üzerinden tetiklenir. |

Bu ayrım sayesinde **panel güncellemeleri** ile **Engine sürümü** farklı ritimde ilerleyebilir; müşteri trafiği çoğunlukla Engine’in yönettiği web sunucusundan çıkar.

---

## İstek ve güven sınırları

1. Son kullanıcı tarayıcıdan panele gider (HTTPS). Oturum çerezleri ve 2FA politikaları Laravel tarafında uygulanır.
2. Paneldeki bir eylem (ör. “sertifika yenile”) API çağrısına dönüşür; Engine’e giderken **dahili anahtarlar ve imzalar** (`ENGINE_INTERNAL_KEY`, `ENGINE_API_SECRET` vb.) ile korunur.
3. Engine, root ayrıcalıklı işlemleri yerel olarak yapar ve sonucu panele iletir; ayrıntılı audit için hem panel günlükleri hem de `journalctl` kullanılır.

Uzaktan SSH ile doğrudan sunucuya bağlanma ihtiyacı azalır; yine de kilitlenme durumları için **break-glass SSH** prosedürü tanımlayın.

---

## Lisans ve faturalama

- Panel, merkezi **lisans hub** ile konuşabilir (`LICENSE_SERVER_URL`). Checkout **Stripe** üzerinden yapılabilir; başarılı ödeme sonrası anahtar e-posta ile iletilir (landing projesindeki şablonlar ve API uçları bu akışa göre kurgulanmıştır).
- **Freemium / Pro** sınırları plan kayıtlarında tutulur; Engine bu limitleri uygulamak için panelden gelen yetkili isteklere güvenir.

---

## Çoklu sunucu ve yol haritası

Bugünün tipik kurulumu **tek düğüm** (Engine + panel aynı makinede) şeklindedir. Trafik büyüdükçe veritabanını ayırmak, CDN eklemek veya Engine örneklerini yük dengeleyici arkasına almak mümkündür; Panelze ürünü bu evrimleri destekleyecek biçimde genişler — ayrıntılar blog ve sürüm notlarında duyurulur.

Takip edilecek sayfa: [Panelze yetenekleri](/docs/platform-features).
MD
            ]
        );

        DocPage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'architecture'],
            [
                'parent_id' => null,
                'title' => 'Architecture overview',
                'meta_description' => 'Engine vs panel vs tenant DBs; trust boundaries, licensing hub, Stripe, and scale-out notes.',
                'is_published' => true,
                'sort_order' => 5,
                'content' => <<<'MD'
## High-level components

| Layer | Responsibility |
| --- | --- |
| **Panelze Engine** | Applies Nginx/Apache vhosts, PHP-FPM pools, filesystem paths, TLS automation, and host-level quotas. |
| **Panel (Laravel)** | HTTP UI + JSON API: Sanctum auth, ability-based RBAC, Stripe checkout, license verification hooks, queues. |
| **Panel database** | Stores tenants, sites, service metadata — **not** the same thing as customer MySQL/Postgres databases that belong to hosted sites. |
| **Customer DBs** | MySQL/MariaDB or PostgreSQL instances created via Engine provisioning APIs; backups/imports initiated from the panel. |

Because these layers are separate you can ship **panel releases** and **Engine builds** on different schedules; customer HTTP traffic largely terminates on the Engine-managed web stack.

---

## Request path & trust boundaries

1. Operators hit the panel over HTTPS; cookies/MFA enforced in Laravel.
2. Stateful mutations become Engine RPC/HTTP calls protected by **shared secrets** such as `ENGINE_INTERNAL_KEY` / `ENGINE_API_SECRET`.
3. The Engine performs privileged host mutations and returns structured results; troubleshooting pairs `storage/logs` on the panel with `journalctl` on the node.

Day-to-day break-glass SSH should be rare—document it for disaster recovery.

---

## Licensing & billing

- The panel can call a remote **license hub** (`LICENSE_SERVER_URL`) and/or accept keys pasted by admins.
- Checkout may run through **Stripe**; successful orders trigger transactional mail with license material (see landing email templates + billing controllers).

Authoritative **plan limits** live beside the licensing module—marketing copy is illustrative only.

---

## Multi-node roadmap

Most deployments today co-locate Engine + panel on one Linux host. As you grow, split the DB tier, add CDNs, or fan out Engine instances behind load balancers. Panelze’s roadmap targets multi-host orchestration—watch release notes and the blog for timelines.

For capability depth, jump to [Platform capabilities](/docs/platform-features).
MD
            ]
        );

        $catHostingTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'hosting-migration'],
            [
                'name' => 'Hosting ve geçiş',
                'meta_title' => 'Hosting ve geçiş — Panelze blog',
                'meta_description' => 'Paylaşımlı hostingden çıkış, sunucu taşıma ve panel geçişi üzerine yazılar.',
                'sort_order' => 10,
            ]
        );

        $catHostingEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'hosting-migration'],
            [
                'name' => 'Hosting & migration',
                'meta_title' => 'Hosting & migration — Panelze blog',
                'meta_description' => 'Moving off shared hosting, server migrations, and panel transitions.',
                'sort_order' => 10,
            ]
        );

        $catSecurityTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'security'],
            [
                'name' => 'Güvenlik',
                'meta_title' => 'Güvenlik — Panelze blog',
                'meta_description' => 'Panel ve sunucu güvenliği, erişim ve sertifika konuları.',
                'sort_order' => 20,
            ]
        );

        $catSecurityEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'security'],
            [
                'name' => 'Security',
                'meta_title' => 'Security — Panelze blog',
                'meta_description' => 'Panel and server security, access control, and certificates.',
                'sort_order' => 20,
            ]
        );

        $catScaleTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'scaling'],
            [
                'name' => 'Ölçeklendirme',
                'meta_title' => 'Ölçeklendirme ve mimari — Panelze blog',
                'meta_description' => 'Tek sunucudan çoklu düzene geçiş ve mimari notları.',
                'sort_order' => 30,
            ]
        );

        $catScaleEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'scaling'],
            [
                'name' => 'Scaling',
                'meta_title' => 'Scaling & architecture — Panelze blog',
                'meta_description' => 'Growing from one server to multi-node setups.',
                'sort_order' => 30,
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'from-shared-hosting'],
            [
                'blog_category_id' => $catHostingTr->id,
                'title' => 'Shared hosting’den kendi panelime',
                'excerpt' => 'Klasik paylaşımlı hostingden çıkıp kendi sunucunuzda Panelze ile nasıl ilerlersiniz?',
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'content' => <<<'MD'
Paylaşımlı hosting uzun yıllar işinizi görür; ta ki tek panelden onlarca siteyi yönetme ihtiyacı doğana kadar.

## Geçiş stratejisi

1. **DNS TTL** düşürün; taşıma günü kesintiyi azaltır.
2. Veritabanını **mysqldump** veya panel araçlarıyla alın.
3. Dosyaları **rsync** ile senkronize edin.
4. Panelze’de site sihirbazını çalıştırıp SSL’i doğrulayın.

Küçük projelerde önce staging subdomain ile test etmek riski ciddi şekilde azaltır.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'from-shared-hosting'],
            [
                'blog_category_id' => $catHostingEn->id,
                'title' => 'From shared hosting to your own panel',
                'excerpt' => 'How to move from classic shared hosting to Panelze on your own server.',
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'content' => <<<'MD'
Shared hosting works for years — until you need to run many sites from one panel.

## Migration strategy

1. Lower **DNS TTL** to reduce cutover pain.
2. Export the database with **mysqldump** or your tools.
3. Sync files with **rsync**.
4. Run the Panelze site wizard and verify TLS.

For smaller projects, test on a staging subdomain first.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'panel-security-basics'],
            [
                'blog_category_id' => $catSecurityTr->id,
                'title' => 'Panel güvenliğinde temel hatalar',
                'excerpt' => 'Yönetim arayüzünü internete açarken sık yapılan hatalar ve pratik önlemler.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'content' => <<<'MD'
Panel URL’sini herkese açık bırakmak yerine:

- **İki faktörlü doğrulama** kullanın
- Yönetim yolunu **rate limit** ile koruyun
- Varsayılan portları değiştirin veya **VPN** arkasına alın

Panelze yönetim hesapları için güçlü şifre politikası ve oturum süresi sınırları önerilir.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'panel-security-basics'],
            [
                'blog_category_id' => $catSecurityEn->id,
                'title' => 'Common panel security mistakes',
                'excerpt' => 'Typical pitfalls when exposing an admin UI to the internet — and practical fixes.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'content' => <<<'MD'
Before leaving the panel URL wide open:

- Enable **two-factor authentication**
- Protect admin routes with **rate limiting**
- Change default ports or place the panel behind a **VPN**

Strong password policy and session limits are recommended for Panelze admin accounts.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'single-server-to-cluster'],
            [
                'blog_category_id' => $catScaleTr->id,
                'title' => 'Tek sunucudan çoklu cluster’a',
                'excerpt' => 'Büyüdükçe mimariyi nasıl parçalayabilirsiniz?',
                'is_published' => true,
                'published_at' => now()->subDay(),
                'content' => <<<'MD'
İlk aşamada tek sunucu yeterlidir. Trafik ve ekip büyüdükçe:

- Veritabanını ayrı bir **DB host**’a taşıyın
- Statik ve medya için **CDN** ekleyin
- Engine örneklerini **load balancer** arkasında çoğaltın

Panelze bu aşamalarda aynı panel üzerinden çoklu sunucu yönetimini hedefler; roadmap’i ürün duyurularından takip edin.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'single-server-to-cluster'],
            [
                'blog_category_id' => $catScaleEn->id,
                'title' => 'From one server to a multi-node setup',
                'excerpt' => 'How to split the architecture as you grow.',
                'is_published' => true,
                'published_at' => now()->subDay(),
                'content' => <<<'MD'
A single server is enough at first. As traffic and teams grow:

- Move the database to a dedicated **DB host**
- Add a **CDN** for static assets and media
- Run multiple Engine instances behind a **load balancer**

Panelze aims to manage multiple servers from the same panel over time — follow product announcements for the roadmap.
MD
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'freemium'],
            [
                'name' => 'Freemium',
                'subtitle' => 'Tek sunucu için sınırlı ama yeterli özellikler',
                'price_label' => '₺0',
                'price_note' => '/ay',
                'sort_order' => 10,
                'is_featured' => false,
                'is_active' => true,
                'features' => [
                    '1 sunucu',
                    'Temel site ve domain yönetimi',
                    'Otomatik SSL (Let\'s Encrypt)',
                    'Sınırlı log ve terminal erişimi',
                ],
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'pro-lisans'],
            [
                'name' => 'Pro Lisans',
                'subtitle' => 'Ajanslar ve yoğun trafik için',
                'price_label' => '₺?',
                'price_note' => '/ay · sunucu başına',
                'sort_order' => 20,
                'is_featured' => true,
                'is_active' => true,
                'features' => [
                    'Sınırsız site ve domain',
                    'Gelişmiş güvenlik profilleri',
                    'Detaylı metrikler ve health checks',
                    'Öncelikli destek',
                ],
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'vendor'],
            [
                'name' => 'Vendor / White-label',
                'subtitle' => 'Kendi markanızla sunmak isteyen paneller için',
                'price_label' => 'Özel',
                'price_note' => 'teklif',
                'sort_order' => 30,
                'is_featured' => false,
                'is_active' => true,
                'features' => [
                    'Özel fiyatlandırma ve SLA',
                    'Marka özelleştirme',
                    'Roadmap iş birliği',
                ],
            ]
        );
    }
}
