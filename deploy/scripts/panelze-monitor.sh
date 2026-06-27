#!/bin/bash
#
# Panelze/HostVim sunucu-seviyesi izleme + uyari.
# Laravel'DEN BAGIMSIZ calisir: panel/DB cokse bile (asil felaket ani) uyari gider.
#
# Kontroller: disk, RAM, load, kritik servisler, HTTP uptime, failed_jobs,
#             SSL sertifika suresi, son yedek durumu/yasi.
# Uyari mantigi: yeni sorun olustugunda veya cozuldugunde mail; sorun devam
#               ediyorsa gunde 1 hatirlatma (spam yok). sendmail ile gonderir.
#
# Kurulum: /usr/local/sbin/panelze-monitor.sh, cron ile her 5 dk calisir.
# Ayar: /etc/panelze/monitor.conf (en az ALERT_EMAIL tanimlanmali).
set -uo pipefail

CONF="/etc/panelze/monitor.conf"
STATE_DIR="/var/lib/panelze-monitor"
STATE_FILE="$STATE_DIR/last_problems"
LASTMAIL_FILE="$STATE_DIR/last_mail_epoch"
LOG="/var/log/panelze-monitor.log"

# --- Varsayilanlar (monitor.conf ile gecersiz kilinabilir) ---
ALERT_EMAIL=""
MAIL_FROM="Panelze Monitor <noreply@hostvim.com>"
DISK_THRESHOLD=85
MEM_THRESHOLD=92
LOAD_PER_CORE=4
FAILED_JOBS_THRESHOLD=25
SSL_DAYS_WARN=14
BACKUP_MAX_AGE_HOURS=36
HTTP_TARGETS="https://panel.hostvim.com https://hostvim.com"
SERVICES="nginx mariadb named panelze-engine hostvim-panel-queue hostvim-store-queue panelze-panel-queue"
SSL_DOMAINS="panel.hostvim.com hostvim.com"
PANEL_DB="panelze"
STORE_DB="hostvim"
REMIND_HOURS=24

[ -f "$CONF" ] && . "$CONF"
mkdir -p "$STATE_DIR"

problems=()
add_problem() { problems+=("$1"); }

# 1) Disk
usep="$(df / | awk 'NR==2{gsub("%","",$5); print $5}')"
[ "${usep:-0}" -ge "$DISK_THRESHOLD" ] && add_problem "DISK: kok bolum %$usep dolu (esik %$DISK_THRESHOLD)"

# 2) RAM
memp="$(free | awk '/^Mem:/{printf "%d", $3/$2*100}')"
[ "${memp:-0}" -ge "$MEM_THRESHOLD" ] && add_problem "RAM: %$memp kullanimda (esik %$MEM_THRESHOLD)"

# 3) Load (cekirdek basina)
cores="$(nproc 2>/dev/null || echo 1)"
load1="$(awk '{print $1}' /proc/loadavg)"
load_limit="$((cores * LOAD_PER_CORE))"
awk -v l="$load1" -v lim="$load_limit" 'BEGIN{exit !(l>lim)}' && add_problem "LOAD: yuk $load1 (limit $load_limit = ${cores}c x $LOAD_PER_CORE)"

# 4) Kritik servisler
for s in $SERVICES; do
    systemctl is-active --quiet "$s" 2>/dev/null || add_problem "SERVIS DOWN: $s"
done

# 5) HTTP uptime
for u in $HTTP_TARGETS; do
    code="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$u" 2>/dev/null || echo 000)"
    case "$code" in
        200|301|302) ;;
        *) add_problem "HTTP: $u yanit kodu=$code" ;;
    esac
done

# 6) failed_jobs
pf="$(mysql -N -e "SELECT COUNT(*) FROM ${PANEL_DB}.failed_jobs" 2>/dev/null || echo 0)"
sf="$(mysql -N -e "SELECT COUNT(*) FROM ${STORE_DB}.failed_jobs" 2>/dev/null || echo 0)"
[ "${pf:-0}" -ge "$FAILED_JOBS_THRESHOLD" ] && add_problem "KUYRUK: panel failed_jobs=$pf (esik $FAILED_JOBS_THRESHOLD)"
[ "${sf:-0}" -ge "$FAILED_JOBS_THRESHOLD" ] && add_problem "KUYRUK: store failed_jobs=$sf (esik $FAILED_JOBS_THRESHOLD)"

# 7) SSL sertifika suresi
for d in $SSL_DOMAINS; do
    exp="$(echo | timeout 12 openssl s_client -servername "$d" -connect "$d":443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)"
    if [ -n "$exp" ]; then
        ed="$(date -d "$exp" +%s 2>/dev/null || echo 0)"
        [ "$ed" -gt 0 ] && { days=$(( (ed - $(date +%s)) / 86400 )); [ "$days" -lt "$SSL_DAYS_WARN" ] && add_problem "SSL: $d sertifikasi $days gun sonra doluyor"; }
    fi
done

# 8) Son yedek durumu / yasi
if [ -f /var/log/panelze-backup.log ]; then
    last="$(grep -E "yedekleme (TAMAM|HATALI)" /var/log/panelze-backup.log | tail -1)"
    echo "$last" | grep -q HATALI && add_problem "YEDEK: son yedekleme HATALI"
fi
lastdir="$(ls -1dt /var/backups/panelze/*/ 2>/dev/null | head -1)"
if [ -n "$lastdir" ]; then
    age=$(( ($(date +%s) - $(stat -c %Y "$lastdir")) / 3600 ))
    [ "$age" -gt "$BACKUP_MAX_AGE_HOURS" ] && add_problem "YEDEK: son yedek $age saat once alindi (gecikme, esik $BACKUP_MAX_AGE_HOURS sa)"
else
    add_problem "YEDEK: hic yerel yedek bulunamadi"
fi

# --- Durum karsilastirma + uyari ---
current="$(printf '%s\n' "${problems[@]:-}" | sort)"
previous="$([ -f "$STATE_FILE" ] && cat "$STATE_FILE" || true)"
now_epoch="$(date +%s)"
last_mail="$([ -f "$LASTMAIL_FILE" ] && cat "$LASTMAIL_FILE" || echo 0)"
host="$(hostname -f 2>/dev/null || hostname)"

send_mail() {
    local subj="$1" body="$2"
    if [ -z "$ALERT_EMAIL" ]; then
        echo "$(date '+%F %T') UYARI: ALERT_EMAIL bos, mail gonderilemedi. Sorun(lar):" >>"$LOG"
        echo "$body" >>"$LOG"
        return
    fi
    printf 'To: %s\nFrom: %s\nSubject: %s\nContent-Type: text/plain; charset=UTF-8\n\n%s\n' \
        "$ALERT_EMAIL" "$MAIL_FROM" "$subj" "$body" | /usr/sbin/sendmail -t
    echo "$now_epoch" >"$LASTMAIL_FILE"
}

if [ -n "$current" ]; then
    # Sorun var
    echo "$current" >"$STATE_FILE"
    age_since_mail=$(( (now_epoch - last_mail) / 3600 ))
    if [ "$current" != "$previous" ]; then
        send_mail "[Panelze][SORUN] $host" "Sunucuda sorun(lar) tespit edildi ($(date '+%F %T')):

$current

--
Panelze Monitor"
        echo "$(date '+%F %T') SORUN (degisim) bildirildi" >>"$LOG"
    elif [ "$age_since_mail" -ge "$REMIND_HOURS" ]; then
        send_mail "[Panelze][SUREN SORUN] $host" "Su sorun(lar) hala devam ediyor ($(date '+%F %T')):

$current

--
Panelze Monitor" 
        echo "$(date '+%F %T') SUREN sorun hatirlatildi" >>"$LOG"
    fi
else
    # Sorun yok
    if [ -n "$previous" ]; then
        send_mail "[Panelze][DUZELDI] $host" "Onceki tum sorunlar cozuldu, sistem normal ($(date '+%F %T')).

--
Panelze Monitor"
        echo "$(date '+%F %T') DUZELDI bildirildi" >>"$LOG"
    fi
    : >"$STATE_FILE"
fi

exit 0
