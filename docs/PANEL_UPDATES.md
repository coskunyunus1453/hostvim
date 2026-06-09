# Panelze panel güncelleme sistemi

## Mimari

1. **Landing admin** (`/admin/panel-releases`) — sürüm, changelog, artifact URL veya git etiketi yayınlanır.
2. **Hub API** — `GET https://panelze.com/api/v1/panel-updates/check?current=0.1.0&profile=customer`
3. **Müşteri paneli** — admin kontrol eder, bildirim alır, onayla günceller (`panelze-panel-update` + `deploy-panel.sh`).

## Yayın akışı (sizin taraf)

```bash
# 1) Sürüm etiketi ve artifact
git tag v0.2.0 && git push origin v0.2.0
cd /var/www/panelze && bash deploy/scripts/build-profile-artifact.sh customer
sha256sum dist-artifacts/panelze-customer-*.tar.gz

# 2) Artifact’ı HTTPS’e yükleyin (GitHub Release önerilir)

# 3) Landing admin → Panel sürümleri → Yeni sürüm
#    - version: 0.2.0
#    - changelog: müşteriye gösterilecek metin
#    - artifact_url + artifact_sha256 (veya git_tag: v0.2.0)
#    - Yayınla
```

## Müşteri paneli

- Özet ve **Sistem** sayfasında güncelleme kartı
- `panelze:check-panel-update` her 6 saatte bildirim oluşturur
- Güncelleme: `.env`, `data/www`, `panel/storage` korunur

## Ortam değişkenleri

| Uygulama | Değişken | Açıklama |
|----------|----------|----------|
| Landing | `PANELZE_PANEL_UPDATES_API_SECRET` | Hub API Bearer (boş = herkese açık, önerilmez) |
| Panel | `PANELZE_UPDATE_HUB_URL` | Varsayılan: `LICENSE_SERVER_URL` / panelze.com |
| Panel | `PANELZE_PANEL_VERSION` | Kurulu sürüm (güncelleme sonrası betik yazar) |
| Panel | `PANELZE_PANEL_UPDATES_API_SECRET` | Hub ile aynı secret |

## Sunucu gereksinimleri

- `/usr/local/sbin/panelze-panel-update` (kurulum betiği yükler)
- `www-data` sudo NOPASSWD bu betik için (`/etc/sudoers.d/panelze-engine`)
- Üretimde queue worker önerilir (`QUEUE_CONNECTION=database` veya redis)
