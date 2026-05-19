# Hostvim panel güncelleme sistemi

## Mimari

1. **Landing admin** (`/admin/panel-releases`) — sürüm, changelog, artifact URL veya git etiketi yayınlanır.
2. **Hub API** — `GET https://hostvim.com/api/v1/panel-updates/check?current=0.1.0&profile=customer`
3. **Müşteri paneli** — admin kontrol eder, bildirim alır, onayla günceller (`hostvim-panel-update` + `deploy-panel.sh`).

## Yayın akışı (sizin taraf)

```bash
# 1) Sürüm etiketi ve artifact
git tag v0.2.0 && git push origin v0.2.0
cd /var/www/hostvim && bash deploy/scripts/build-profile-artifact.sh customer
sha256sum dist-artifacts/hostvim-customer-*.tar.gz

# 2) Artifact’ı HTTPS’e yükleyin (GitHub Release önerilir)

# 3) Landing admin → Panel sürümleri → Yeni sürüm
#    - version: 0.2.0
#    - changelog: müşteriye gösterilecek metin
#    - artifact_url + artifact_sha256 (veya git_tag: v0.2.0)
#    - Yayınla
```

## Müşteri paneli

- Özet ve **Sistem** sayfasında güncelleme kartı
- `hostvim:check-panel-update` her 6 saatte bildirim oluşturur
- Güncelleme: `.env`, `data/www`, `panel/storage` korunur

## Ortam değişkenleri

| Uygulama | Değişken | Açıklama |
|----------|----------|----------|
| Landing | `HOSTVIM_PANEL_UPDATES_API_SECRET` | Hub API Bearer (boş = herkese açık, önerilmez) |
| Panel | `HOSTVIM_UPDATE_HUB_URL` | Varsayılan: `LICENSE_SERVER_URL` / hostvim.com |
| Panel | `HOSTVIM_PANEL_VERSION` | Kurulu sürüm (güncelleme sonrası betik yazar) |
| Panel | `HOSTVIM_PANEL_UPDATES_API_SECRET` | Hub ile aynı secret |

## Sunucu gereksinimleri

- `/usr/local/sbin/hostvim-panel-update` (kurulum betiği yükler)
- `www-data` sudo NOPASSWD bu betik için (`/etc/sudoers.d/hostvim-engine`)
- Üretimde queue worker önerilir (`QUEUE_CONNECTION=database` veya redis)
