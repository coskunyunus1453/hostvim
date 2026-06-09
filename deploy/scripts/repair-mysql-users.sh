#!/usr/bin/env bash
# Panel MySQL kullanıcılarını (.env + secret dosyaları) MariaDB ile eşitler.
# Kurulum, güncelleme veya elle müdahale sonrası 1045 hatalarını giderir.
#
# Kullanım (root):
#   PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/repair-mysql-users.sh
#   MYSQL_ROOT_PASS='...' PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/repair-mysql-users.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "$SCRIPT_DIR/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
elif [[ -f "${PANELZE_HOME:-/var/www/panelze}/deploy/scripts/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=/dev/null
  source "${PANELZE_HOME:-/var/www/panelze}/deploy/scripts/lib/panelze-deploy-common.sh"
else
  echo "Hata: panelze-deploy-common.sh bulunamadi. PANELZE_HOME veya repo deploy/scripts yolunu kontrol edin." >&2
  exit 1
fi

PANEL_ROOT="${PANEL_ROOT:-$(hostvim_resolve_hostvim_home)/panel}"
ENV_FILE="${ENV_FILE:-$PANEL_ROOT/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Hata: .env bulunamadı: $ENV_FILE" >&2
  exit 1
fi

if [[ "${WITH_MARIADB:-1}" != "1" && "${WITH_MARIADB:-1}" != "yes" ]]; then
  if grep -q '^DB_CONNECTION=sqlite' "$ENV_FILE" 2>/dev/null; then
    echo "SQLite kullanılıyor; MySQL onarımı atlandı."
    exit 0
  fi
fi

if [[ -s /root/panelze-panel-mysql.secret ]]; then
  PANEL_DB_PASS="$(tr -d '\r\n' < /root/panelze-panel-mysql.secret)"
elif [[ -s /root/panelsar-panel-mysql.secret ]]; then
  PANEL_DB_PASS="$(tr -d '\r\n' < /root/panelsar-panel-mysql.secret)"
else
  PANEL_DB_PASS="$(hostvim_read_env_value "$ENV_FILE" DB_PASSWORD)"
  if [[ -z "$PANEL_DB_PASS" ]]; then
    PANEL_DB_PASS="$(openssl rand -hex 16)"
  fi
  echo "$PANEL_DB_PASS" > /root/panelze-panel-mysql.secret
  chmod 600 /root/panelze-panel-mysql.secret
  echo "==> Panel DB şifresi oluşturuldu: /root/panelze-panel-mysql.secret"
fi

if [[ -s /root/panelze-mysql-provision.secret ]]; then
  MYSQL_PROVISION_PASS="$(tr -d '\r\n' < /root/panelze-mysql-provision.secret)"
elif [[ -s /root/panelsar-mysql-provision.secret ]]; then
  MYSQL_PROVISION_PASS="$(tr -d '\r\n' < /root/panelsar-mysql-provision.secret)"
else
  MYSQL_PROVISION_PASS="$(hostvim_read_env_value "$ENV_FILE" MYSQL_PROVISION_PASSWORD)"
  if [[ -z "$MYSQL_PROVISION_PASS" ]]; then
    MYSQL_PROVISION_PASS="$(openssl rand -hex 18)"
  fi
  echo "$MYSQL_PROVISION_PASS" > /root/panelze-mysql-provision.secret
  chmod 600 /root/panelze-mysql-provision.secret
  echo "==> Provision şifresi oluşturuldu: /root/panelze-mysql-provision.secret"
fi

update_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    echo "${key}=${value}" >> "$ENV_FILE"
  fi
}

echo "==> MySQL admin bağlantısı deneniyor..."
if ! hostvim_mysql_admin -e "SELECT 1 AS ok;" >/dev/null 2>&1; then
  echo "Hata: MySQL/MariaDB admin erişimi yok." >&2
  echo "  Deneyin: MYSQL_ROOT_PASS='root_sifreniz' bash $0" >&2
  echo "  veya /root/.my.cnf içinde [client] user/password tanımlayın." >&2
  exit 1
fi

echo "==> hostvim + hostvim_provision kullanıcıları eşitleniyor..."
hostvim_mysql_admin <<EOSQL
CREATE DATABASE IF NOT EXISTS hostvim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hostvim'@'localhost' IDENTIFIED BY '${PANEL_DB_PASS}';
CREATE USER IF NOT EXISTS 'hostvim'@'127.0.0.1' IDENTIFIED BY '${PANEL_DB_PASS}';
ALTER USER 'hostvim'@'localhost' IDENTIFIED BY '${PANEL_DB_PASS}';
ALTER USER 'hostvim'@'127.0.0.1' IDENTIFIED BY '${PANEL_DB_PASS}';
GRANT ALL PRIVILEGES ON hostvim.* TO 'hostvim'@'localhost';
GRANT ALL PRIVILEGES ON hostvim.* TO 'hostvim'@'127.0.0.1';
CREATE USER IF NOT EXISTS 'hostvim_provision'@'localhost' IDENTIFIED BY '${MYSQL_PROVISION_PASS}';
CREATE USER IF NOT EXISTS 'hostvim_provision'@'127.0.0.1' IDENTIFIED BY '${MYSQL_PROVISION_PASS}';
ALTER USER 'hostvim_provision'@'localhost' IDENTIFIED BY '${MYSQL_PROVISION_PASS}';
ALTER USER 'hostvim_provision'@'127.0.0.1' IDENTIFIED BY '${MYSQL_PROVISION_PASS}';
GRANT ALL PRIVILEGES ON *.* TO 'hostvim_provision'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'hostvim_provision'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOSQL

update_env "DB_CONNECTION" "mysql"
update_env "DB_HOST" "127.0.0.1"
update_env "DB_PORT" "3306"
update_env "DB_DATABASE" "hostvim"
update_env "DB_USERNAME" "hostvim"
update_env "DB_PASSWORD" "$PANEL_DB_PASS"
update_env "MYSQL_PROVISION_ENABLED" "true"
update_env "MYSQL_PROVISION_HOST" "127.0.0.1"
update_env "MYSQL_PROVISION_PORT" "3306"
update_env "MYSQL_PROVISION_USERNAME" "hostvim_provision"
update_env "MYSQL_PROVISION_PASSWORD" "$MYSQL_PROVISION_PASS"

echo "$PANEL_DB_PASS" > /root/panelze-panel-mysql.secret
chmod 600 /root/panelze-panel-mysql.secret
echo "$MYSQL_PROVISION_PASS" > /root/panelze-mysql-provision.secret
chmod 600 /root/panelze-mysql-provision.secret

echo "==> Bağlantı testi..."
mysql -u hostvim -p"${PANEL_DB_PASS}" -h 127.0.0.1 -e "USE hostvim; SELECT 'panel_db ok' AS status;"
mysql -u hostvim_provision -p"${MYSQL_PROVISION_PASS}" -h 127.0.0.1 -e "SELECT 'provision ok' AS status;"

if [[ -f "$PANEL_ROOT/artisan" ]]; then
  hostvim_run_artisan config:clear || true
fi

echo "Tamam. Secret dosyaları: /root/panelze-panel-mysql.secret, /root/panelze-mysql-provision.secret"
