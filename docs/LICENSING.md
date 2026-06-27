# Lisanslama Sistemi (Offline İmzalı + Opsiyonel Hub)

Bu belge, Panelze/HostVim sisteminin **satışa hazır lisanslama** mimarisini, satıcı (vendor)
ve müşteri (alıcı) iş akışlarını anlatır.

## 1. Model: Hibrit (offline-ana, online-iptal)

- **Ana doğrulama – Offline imzalı anahtar (Ed25519):** Her lisans anahtarı, satıcının
  **private key**'i ile imzalanır. Panel (PHP) ve Go engine, satıcının **gömülü public key**'i
  ile imzayı, süreyi ve domain bağını **internet olmadan** doğrular.
- **Opsiyonel online iptal – License Hub:** `LICENSE_SERVER_URL` ayarlıysa panel, anahtarı
  merkezi hub'a da sorar. Hub yalnızca **uzaktan iptal/askı** için kullanılır; imzası geçerli
  bir offline anahtarı hub açıkça `revoked/suspended/disabled` demedikçe geçerli kalır.

> Önemli: Artık “her zaman geçerli” engine davranışı **yoktur**. Geçerli imza veya hub onayı
> olmadan kurulum **community/geçersiz** sayılır (Pro modüller kapanır).

### Anahtar biçimi

```
PLZ1.<base64url(payload_json)>.<base64url(ed25519_signature)>
```

`payload` alanları:

| Alan  | Tip    | Açıklama |
|-------|--------|----------|
| `v`   | int    | Biçim sürümü (1) |
| `lid` | string | Lisans referansı (ör. `HV-2026-0001`) |
| `to`  | string | Lisans sahibi / firma |
| `plan`| string | `community`, `standard`, `pro`, `enterprise`… |
| `feat`| array  | Aktif modüller (liste) ya da `{anahtar: bool}` |
| `dom` | array  | Bağlı host(lar). Boş ya da `["*"]` = her host. `*.x.com` desteklenir |
| `iat` | int    | Üretim zamanı (unix) |
| `exp` | int    | Bitiş (unix). `0` = süresiz |
| `grace`| int   | Bitiş sonrası ek gün (varsayılan 14) |

---

## 2. Satıcı (Vendor) Kurulumu — TEK SEFERLİK

### 2.1 Anahtar çifti üret

```bash
php artisan license:keygen --out=$HOME/.panelze/vendor-license-private.key
```

- **PUBLIC KEY**: panele ve engine'e gömülür (aşağıya bakın).
- **SECRET KEY**: yalnızca satıcıda kalır. **Asla** ürünle/müşteriyle paylaşılmaz, repoya
  commit edilmez. Önerilen yer: `~/.panelze/vendor-license-private.key` (chmod 600).

### 2.2 Public key'i göm

Aynı public key iki yere yazılır:

- **Panel:** `panel/config/panelze.php` → `license.public_key`
  (veya `.env` → `PANELZE_LICENSE_PUBLIC_KEY=...`)
- **Engine:** `engine/internal/license/license.go` → `DefaultPublicKey`
  (veya `/etc/panelze/engine.yaml` → `license.public_key: "..."`)

> Engine binary'sini yeniden derlemek gömülü değeri günceller; config dosyası override eder.

### 2.3 Private key'i koru

- Yedeğini güvenli (offline/şifreli) tut. **Kaybedersen yeni anahtar çifti üretip tüm
  müşteri anahtarlarını yeniden imzalaman gerekir.**

---

## 3. Lisans Üretme (her satış için)

```bash
php artisan license:issue \
  --to="Acme Bilişim" \
  --plan=enterprise \
  --domains="panel.acme.com" \
  --days=365 \
  --feat=phpmyadmin_sso,security_pro \
  --id=HV-2026-0001
```

| Seçenek      | Açıklama |
|--------------|----------|
| `--to`       | Firma/sahip adı |
| `--plan`     | Plan kodu |
| `--domains`  | Bağlı host(lar), virgülle. Boş/`*` = her host |
| `--days`     | Geçerlilik gün. `0` = süresiz |
| `--grace`    | Bitiş sonrası ek gün |
| `--feat`     | Aktif modüller, virgülle |
| `--id`       | Lisans referansı (boşsa otomatik) |
| `--secret`/`--secret-file` | Private key (yoksa env / varsayılan dosya) |

Çıktı `PLZ1...` ile başlayan anahtardır → müşteriye verilir.

### Süreli mi, süresiz mi?

- **Abonelik (yıllık) satışı:** `--days=365`. Yenilemede yeni anahtar üretip gönderirsin.
- **Tek seferlik (lifetime):** `--days=0`.

---

## 4. Müşteri (Alıcı) Aktivasyonu

İki yol:

1. **Panel arayüzü:** Admin → Lisans → “Aktive Et” → anahtarı yapıştır.
   (Anahtar veritabanında **APP_KEY ile şifreli** saklanır; `.env` gerekmez.)
2. **.env:** `LICENSE_KEY="PLZ1...."` → `php artisan config:clear`.

Doğrulama otomatik yapılır; sonuç 5 dk cache'lenir. Cache temizleme:

```bash
php artisan tinker --execute="app(App\Services\PanelLicenseService::class)->forgetCache();"
```

---

## 5. Teşhis / Destek

```bash
# Kurulumdaki etkin anahtarı doğrula (APP_URL host'una göre):
php artisan license:inspect

# Belirli bir anahtarı belirli host için test et:
php artisan license:inspect "PLZ1...." --host=panel.acme.com
```

Olası `code` değerleri: `ok`, `grace`, `expired`, `domain_mismatch`,
`signature_invalid`, `malformed`, `no_public_key`.

---

## 6. İptal (Revocation)

- **Offline anahtarlar geri çağrılamaz** (imza geçerli kaldıkça çalışır). İptal gerekiyorsa:
  - Kısa süreli (`--days`) anahtar ver, yenilemede durdur; veya
  - **Online hub** kullan: `LICENSE_SERVER_URL` ayarlı kurulumlarda hub anahtarı
    `revoked/suspended/disabled` döndürürse panel, offline imza geçerli olsa bile reddeder.

---

## 7. Güvenlik Notları

- Kaynak kod ile satışta, alıcı PHP/Go kaynağını düzenleyip doğrulamayı atlayabilir
  (tüm kaynak-dağıtımlı yazılımlarda olduğu gibi). Güçlü koruma için **derlenmiş engine
  binary** dağıtımı + sözleşmesel lisans önerilir; engine binary'sine gömülü public key,
  imza doğrulamasını PHP'ye göre daha zor atlanır kılar.
- `force_valid` / `force_pro` yalnızca **geliştirme** içindir; production .env'de kullanma.
- Private key'i repoya koyma; `~/.panelze/` zaten repo dışındadır.

---

## 8. English Quick Reference

- **Model:** Offline Ed25519-signed license keys (primary) + optional online hub (revocation only).
- **Vendor (one-time):** `php artisan license:keygen` → embed PUBLIC key in `config/panelze.php`
  (`license.public_key`) and `engine/internal/license/license.go` (`DefaultPublicKey`); keep the
  SECRET key offline.
- **Issue per sale:** `php artisan license:issue --to=.. --plan=.. --domains=.. --days=.. --feat=..`
- **Customer activation:** Panel → License → Activate, or `.env LICENSE_KEY=...`.
- **Diagnose:** `php artisan license:inspect [key] --host=...`
- **Revoke:** only via online hub (`LICENSE_SERVER_URL`) returning `revoked/suspended/disabled`.
