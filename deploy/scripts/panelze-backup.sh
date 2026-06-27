#!/bin/bash
#
# Panelze/HostVim sunucu-seviyesi otomatik yedekleme.
# - Tum veritabanlari (panel + store + musteri hosting DB'leri) -> tek mysqldump
# - Konfigurasyon (env, nginx, bind zone, panelze) -> tar.gz
# - SHA256 butunluk dosyasi
# - Yerel retention (eski yedekleri siler)
# - Opsiyonel OFFSITE: /etc/panelze/backup-offsite.conf varsa uzak sunucuya rsync
#     * yedek arsivleri (DB+config)
#     * musteri site dosyalari (21GB+) artimli mirror (yerelde TAM tar ALINMAZ; disk korunur)
#
# Kurulum: /usr/local/sbin/panelze-backup.sh, gunluk cron ile calisir.
set -uo pipefail

BACKUP_ROOT="/var/backups/panelze"
RETENTION_DAYS="${PANELZE_BACKUP_RETENTION_DAYS:-14}"
WWW_ROOT="/var/www/hostvim/data/www"
PANEL_ENV="/var/www/hostvim/panel/.env"
STORE_ENV="/var/www/hostvim/data/www/hostvim.com/public_html/.env"
OFFSITE_CONF="/etc/panelze/backup-offsite.conf"
LOG="/var/log/panelze-backup.log"

DATE="$(date +%Y%m%d_%H%M%S)"
DEST="$BACKUP_ROOT/$DATE"
mkdir -p "$DEST"

exec >>"$LOG" 2>&1
echo "================ $(date '+%F %T') yedekleme BASLADI ($DATE) ================"

fail=0

# 1) Tum veritabanlari (panel + store + musteri)
echo "-> Veritabanlari (mysqldump --all-databases)"
if mysqldump --single-transaction --quick --routines --triggers --events \
      --all-databases 2>>"$LOG" | gzip -6 > "$DEST/all-databases.sql.gz"; then
    echo "   DB dump OK: $(du -h "$DEST/all-databases.sql.gz" | cut -f1)"
else
    echo "   !! DB dump BASARISIZ"; fail=1
fi

# 2) Konfigurasyon
echo "-> Konfigurasyon (env, nginx, bind, panelze)"
tar -czf "$DEST/config.tar.gz" \
    "$PANEL_ENV" \
    "$STORE_ENV" \
    /etc/nginx/sites-enabled \
    /etc/panelze \
    /var/lib/bind/panelze/zones \
    /var/lib/bind/hostvim/zones 2>/dev/null
[ -s "$DEST/config.tar.gz" ] && echo "   Config OK: $(du -h "$DEST/config.tar.gz" | cut -f1)" || { echo "   !! Config tar bos"; fail=1; }

# 3) Butunluk
( cd "$DEST" && sha256sum ./* > SHA256SUMS 2>/dev/null )
echo "   SHA256SUMS yazildi"

# 4) Yerel retention
echo "-> Retention: $RETENTION_DAYS gunden eski yerel yedekler siliniyor"
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +"$RETENTION_DAYS" -exec rm -rf {} \; 2>/dev/null

# 5) OFFSITE (opsiyonel)
if [ -f "$OFFSITE_CONF" ]; then
    # shellcheck disable=SC1090
    source "$OFFSITE_CONF"
    SSH_OPTS="-o StrictHostKeyChecking=no -o BatchMode=yes"
    [ -n "${OFFSITE_SSH_KEY:-}" ] && SSH_OPTS="$SSH_OPTS -i ${OFFSITE_SSH_KEY}"
    [ -n "${OFFSITE_SSH_PORT:-}" ] && SSH_OPTS="$SSH_OPTS -p ${OFFSITE_SSH_PORT}"

    if [ -n "${OFFSITE_RSYNC_TARGET:-}" ]; then
        echo "-> OFFSITE: arsivler -> $OFFSITE_RSYNC_TARGET/archives/$DATE/"
        rsync -az -e "ssh $SSH_OPTS" "$DEST/" "$OFFSITE_RSYNC_TARGET/archives/$DATE/" \
            && echo "   arsiv offsite OK" || { echo "   !! arsiv offsite BASARISIZ"; fail=1; }

        echo "-> OFFSITE: site dosyalari artimli mirror -> $OFFSITE_RSYNC_TARGET/www-mirror/"
        rsync -az --delete -e "ssh $SSH_OPTS" "$WWW_ROOT/" "$OFFSITE_RSYNC_TARGET/www-mirror/" \
            && echo "   www mirror OK" || { echo "   !! www mirror BASARISIZ"; fail=1; }
    fi
else
    echo "-> OFFSITE atlandi ($OFFSITE_CONF yok). Site dosyalari (21GB) yalniz offsite ile yedeklenir."
fi

if [ "$fail" -eq 0 ]; then
    echo "================ $(date '+%F %T') yedekleme TAMAM ($DATE) ================"
else
    echo "################ $(date '+%F %T') yedekleme HATALI ($DATE) ################"
fi
exit "$fail"
