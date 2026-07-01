# HostVim — Operasyon ve Deploy Rehberi

Bu belge **HostVim satış sitesi (store)** + **Panelze panel** birlikte çalışan kurulumlar içindir. Başka bir projede veya yeni sunucuda deploy yapacak kişi yalnızca bu dosyayı okuyarak doğru akışı anlamalıdır.

Panelze genel kurulumu için: [deploy/README.md](README.md)  
Teknik özet: [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md)

---

## Mimari (kısa)

| Bileşen | Sunucu yolu (varsayılan) | Açıklama |
|---------|---------------------------|----------|
| **Store** (Laravel) | `/var/www/hostvim/data/www/hostvim.com/public_html` | hostvim.com satış sitesi + Filament admin |
| **Panel** (Laravel) | `/var/www/hostvim/panel` | Panelze müşteri/admin paneli |
| **Repo kökü** | `/var/www/hostvim` | Git, `deploy/` betikleri burada |
| **PHP-FPM (store)** | `pk-hostvim-com` kullanıcısı | PanelKafes izolasyonu — **www-data değil** |
| **PHP-FPM (panel)** | `www-data` | Panel API |

Store, panel ile `PANELZE_STORE_SECRET` üzerinden `/api/integrations/store/*` konuşur.

---

## Tek doğru deploy yolu

### Mac'ten (önerilen)

```bash
# Tam kurulum: panel entegrasyonu + store rsync + migrate + izinler
bash deploy/scripts/install-hostvim-full.sh

# Yalnızca store güncelleme
bash deploy/scripts/deploy-store.sh
```

### Sunucuda (kod zaten sunucudaysa)

```bash
bash /var/www/hostvim/deploy/scripts/install-hostvim-full.sh --local
# veya
bash /var/www/hostvim/deploy/scripts/deploy-store.sh --local
```

### Deploy sonrası zorunlu adım (500 önleme)

Her deploy'dan sonra — özellikle manuel `rsync` yaptıysanız:

```bash
bash /var/www/hostvim/deploy/scripts/fix-store-permissions.sh --local
```

Mac'ten:

```bash
bash deploy/scripts/fix-store-permissions.sh
```

Bu betik: `storage` + `bootstrap/cache` izinlerini düzeltir, cron/queue kullanıcısını `pk-hostvim-com` yapar, artisan'ı doğru kullanıcıyla çalıştırır, HTTP 200 doğrular.

---

## ASLA yapmayın (500 hatasının ana nedeni)

| Yanlış | Doğru |
|--------|--------|
| Sunucuda **root** ile `php artisan optimize:clear` (store dizininde) | `hostvim_run_as_store php artisan …` veya `fix-store-permissions.sh` |
| `rsync` sonrası izin düzeltmeden bırakmak | Her zaman `fix-store-permissions.sh --local` |
| Store queue/cron'u **www-data** ile çalıştırmak | `pk-hostvim-com` (artisan dosya sahibi) |
| `store/deploy/server-setup.sh` kullanmak | **Silindi** — `deploy-store.sh` kullanın |
| `store/deploy/fix-storage.sh` kullanmak | **Silindi** — aşağıdaki "Logo sorunu" bölümü |

**Neden?** Root veya www-data ile oluşturulan `storage/framework/cache` dosyaları, PanelKafes FPM (`pk-hostvim-com`) tarafından yazılamaz → Laravel **500 Permission denied**.

---

## Kullanılacak betikler (kanonik)

| Betik | Ne zaman |
|-------|----------|
| `deploy/scripts/install-hostvim-full.sh` | İlk kurulum veya panel+store birlikte güncelleme |
| `deploy/scripts/deploy-store.sh` | Yalnızca store kodu deploy |
| `deploy/scripts/fix-store-permissions.sh` | 500, cache izin, deploy sonrası |
| `deploy/scripts/fix-hosting-permissions.sh` | **Müşteri siteleri** (`data/www`) — engine/www-data izinleri |
| `deploy/scripts/lib/hostvim-common.sh` | Ortak fonksiyonlar (source ile; doğrudan çalıştırmayın) |

## Kullanılmayacak / silinmiş dosyalar

| Dosya | Durum | Yerine |
|-------|--------|--------|
| `store/deploy/server-setup.sh` | **Silindi** | `deploy-store.sh --local` |
| `store/deploy/fix-storage.sh` | **Silindi** | `fix-store-permissions.sh` + logo bölümü aşağıda |
| `deploy/fix-hosting-permissions.sh` (kök) | **Silindi** | `deploy/scripts/fix-hosting-permissions.sh` |

---

## Ortam değişkenleri (store `.env`)

```env
PANELZE_API_URL=http://127.0.0.1
PANELZE_STORE_SECRET=<panel .env ile aynı>
PANELZE_PANEL_URL=https://panel.hostvim.com
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Panel `.env` içinde `PANELZE_STORE_SECRET` store ile **birebir aynı** olmalı.

---

## Systemd ve cron (store)

Deploy betikleri bunları otomatik yazar:

| Servis / cron | Kullanıcı | Görev |
|---------------|-----------|--------|
| `hostvim-store-queue.service` | `pk-hostvim-com` | Sipariş provizyon job'ları |
| `/etc/cron.d/hostvim-store` | `pk-hostvim-com` | `schedule:run` (stuck provision recovery) |
| `hostvim-panel-queue.service` | `www-data` | Panel kuyruğu |
| `/etc/cron.d/hostvim-panel-scheduler` | `www-data` | Panel billing, SSL, yedek |

Kontrol:

```bash
systemctl status hostvim-store-queue
cat /etc/cron.d/hostvim-store
```

---

## Sorun giderme

### hostvim.com 500 — Permission denied (cache)

```bash
tail -20 /var/www/hostvim/data/www/hostvim.com/public_html/storage/logs/laravel.log
bash /var/www/hostvim/deploy/scripts/fix-store-permissions.sh --local
```

Logda `storage/framework/cache` geçiyorsa %99 izin sorunu.

### Panelze API 403/ bağlantı

```bash
cd /var/www/hostvim/data/www/hostvim.com/public_html
php artisan tinker --execute="echo json_encode(app(\App\Services\Panel\PanelzeApiService::class)->test());"
```

`PANELZE_STORE_SECRET` ve panel nginx'i kontrol edin.

### Logo / branding görünmüyor

```bash
cd /var/www/hostvim/data/www/hostvim.com/public_html
sudo -u pk-hostvim-com php artisan storage:link --force
sudo -u pk-hostvim-com php artisan optimize:clear
```

Admin'den logoyu yeniden yükleyin; `public/storage` symlink'inin var olduğunu doğrulayın.

### Müşteri sitesi dosya izni (hosting)

Store ile karıştırmayın — bu **barındırılan siteler** içindir:

```bash
bash /var/www/hostvim/deploy/scripts/fix-hosting-permissions.sh
# tek site:
bash /var/www/hostvim/deploy/scripts/fix-hosting-permissions.sh --domain ornek.com
```

---

## Yeni sunucu / başka proje checklist

1. Panel kurulu mu? → `install-hostvim-full.sh` veya `HOSTVIM_INSTALL_PANEL=1`
2. Store `.env` oluşturuldu mu? (`hostvim_ensure_store_env` otomatik)
3. `PANELZE_*` secret eşleşiyor mu?
4. Hosting ürünlerinde `panel_package_id` atanmış mı?
5. `fix-store-permissions.sh --local` → HTTP 200
6. Queue worker çalışıyor mu? → `systemctl status hostvim-store-queue`
7. Test siparişi: ödeme → provizyon → panel SSO

---

## SSH (HostVim prod)

```bash
ssh -i ~/.ssh/hostvim_aapanel root@207.180.237.13
```

Mac'ten deploy:

```bash
export HOSTVIM_SSH_KEY=~/.ssh/hostvim_aapanel
export HOSTVIM_SSH_HOST=root@207.180.237.13
bash deploy/scripts/deploy-store.sh
```

---

## Özet kural

> **Store'da artisan ve cache her zaman `pk-hostvim-com` ile; deploy sonrası her zaman `fix-store-permissions.sh`.**

Bu üç cümleyi hatırlayan biri 500 izin hatalarını tekrarlamaz.
