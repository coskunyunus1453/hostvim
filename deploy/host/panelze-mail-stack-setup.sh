#!/usr/bin/env bash
# Panelze — tam posta yığını: Postfix (25/587/465) + Dovecot (IMAP) + OpenDKIM + Nginx + Roundcube (SQLite)
# Debian 12 / Ubuntu 22.04+ (Ubuntu: universe etkin olmalı — Roundcube için).
# Üretimde TLS için Let's Encrypt önerilir; ilk kurulum ssl-cert snakeoil kullanır.
#
# Not: Sanal posta kutuları engine ile senkronize edilir; bu betik MTA/IMAP/webmail altyapısını açar.
# Panel nginx yapılandırmasına dokunmaz (default site silinmez).
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

if [[ "$(id -u)" -ne 0 ]]; then
  echo "panelze-mail-stack-setup: root ile çalıştırılmalı" >&2
  exit 1
fi

HOST_FQDN="$(hostname -f 2>/dev/null || hostname)"
SNAKE_CERT="/etc/ssl/certs/ssl-cert-snakeoil.pem"
SNAKE_KEY="/etc/ssl/private/ssl-cert-snakeoil.key"
OPENDKIM_MILTER="inet:127.0.0.1:8891"

run_apt() {
  apt-get update -qq
  apt-get install -y -qq "$@"
}

echo "==> vmail kullanıcısı (sanal posta kutuları)..."
if ! getent group vmail >/dev/null 2>&1; then
  groupadd -g 5000 vmail
fi
if ! id vmail >/dev/null 2>&1; then
  useradd -u 5000 -g vmail -d /var/mail/vmail -s /usr/sbin/nologin vmail
fi
install -d -o vmail -g vmail -m 0750 /var/mail/vmail
touch /etc/dovecot/passwd
chown root:dovecot /etc/dovecot/passwd
chmod 640 /etc/dovecot/passwd
touch /etc/postfix/virtual_mailbox_domains /etc/postfix/virtual_mailbox_maps /etc/postfix/virtual_alias_maps
chmod 644 /etc/postfix/virtual_mailbox_domains /etc/postfix/virtual_mailbox_maps /etc/postfix/virtual_alias_maps

echo "==> Paketler (Postfix, Dovecot, OpenDKIM, Nginx, PHP-FPM, Roundcube)..."
run_apt \
  ca-certificates openssl ssl-cert \
  postfix \
  dovecot-core dovecot-imapd dovecot-lmtpd \
  opendkim opendkim-tools \
  nginx \
  php-fpm php-cli php-mbstring php-xml php-intl php-sqlite3 php-curl php-zip \
  sqlite3

if apt-cache show roundcube-core &>/dev/null; then
  run_apt roundcube-core roundcube-sqlite3
else
  echo "Hata: roundcube-core paketi bulunamadı. Ubuntu'da: sudo add-apt-repository universe && apt update" >&2
  exit 1
fi

PHP_SOCK="$(ls -1 /run/php/php*-fpm.sock 2>/dev/null | head -1 || true)"
if [[ -z "${PHP_SOCK}" ]]; then
  echo "Hata: php-fpm unix socket bulunamadı (/run/php/php*-fpm.sock)" >&2
  exit 1
fi

echo "==> Postfix (TLS, SASL → Dovecot, OpenDKIM milter ${OPENDKIM_MILTER})..."
postconf -e "compatibility_level=3.6"
postconf -e "smtpd_banner=\$myhostname ESMTP Panelze"
postconf -e "biff=no"
postconf -e "append_dot_mydomain=no"
postconf -e "readme_directory=no"
postconf -e "smtpd_tls_security_level=may"
postconf -e "smtp_tls_security_level=may"
postconf -e "smtpd_tls_auth_only=yes"
postconf -e "smtpd_tls_cert_file=${SNAKE_CERT}"
postconf -e "smtpd_tls_key_file=${SNAKE_KEY}"
postconf -e "smtp_tls_CApath=/etc/ssl/certs"
postconf -e "smtpd_sasl_type=dovecot"
postconf -e "smtpd_sasl_path=private/auth"
postconf -e "smtpd_sasl_auth_enable=yes"
postconf -e "smtpd_sasl_security_options=noanonymous"
postconf -e "smtpd_recipient_restrictions=permit_sasl_authenticated,permit_mynetworks,reject_unauth_destination"
postconf -e "mynetworks=127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128"
postconf -e "inet_interfaces=all"
postconf -e "myhostname=${HOST_FQDN}"
postconf -e "milter_default_action=accept"
postconf -e "milter_protocol=6"
postconf -e "smtpd_milters=${OPENDKIM_MILTER}"
postconf -e "non_smtpd_milters=${OPENDKIM_MILTER}"
postconf -e "virtual_mailbox_domains=/etc/postfix/virtual_mailbox_domains"
postconf -e "virtual_mailbox_maps=hash:/etc/postfix/virtual_mailbox_maps"
postconf -e "virtual_alias_maps=hash:/etc/postfix/virtual_alias_maps"
postconf -e "virtual_mailbox_base=/var/mail/vmail"
postconf -e "virtual_minimum_uid=5000"
postconf -e "virtual_uid_maps=static:5000"
postconf -e "virtual_gid_maps=static:5000"
postmap /etc/postfix/virtual_mailbox_maps 2>/dev/null || true
postmap /etc/postfix/virtual_alias_maps 2>/dev/null || true

MASTER_CF="/etc/postfix/master.cf"
if grep -qE '^#submission' "$MASTER_CF" 2>/dev/null; then
  sed -i 's/^#submission/submission/' "$MASTER_CF" || true
fi
if ! grep -qE '^submission[[:space:]]+inet' "$MASTER_CF" 2>/dev/null; then
  cat >>"$MASTER_CF" <<EOF

# panelze-mail-stack
submission inet n       -       y       -       -       smtpd
  -o syslog_name=postfix/submission
  -o smtpd_tls_security_level=encrypt
  -o smtpd_sasl_auth_enable=yes
  -o smtpd_tls_auth_only=yes
  -o smtpd_client_restrictions=permit_sasl_authenticated,reject
  -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject_unauth_destination
EOF
fi

if ! grep -qE '^smtps[[:space:]]+inet' "$MASTER_CF" 2>/dev/null; then
  cat >>"$MASTER_CF" <<EOF

smtps     inet  n       -       y       -       -       smtpd
  -o syslog_name=postfix/smtps
  -o smtpd_tls_wrappermode=yes
  -o smtpd_sasl_auth_enable=yes
  -o smtpd_tls_auth_only=yes
  -o smtpd_client_restrictions=permit_sasl_authenticated,reject
  -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject_unauth_destination
EOF
fi

echo "==> Dovecot (sanal kutular + Postfix SASL; TLS snakeoil)..."
cat >/etc/dovecot/conf.d/99-panelze-mail-stack.conf <<'EOF'
mail_location = maildir:/var/mail/vmail/%d/%n
mail_privileged_group = mail
auth_mechanisms = plain login
disable_plaintext_auth = no

passdb {
  driver = passwd-file
  args = scheme=SHA512-CRYPT username_format=%u /etc/dovecot/passwd
}

userdb {
  driver = static
  args = uid=vmail gid=vmail home=/var/mail/vmail/%d/%n
}

ssl_cert = </etc/ssl/certs/ssl-cert-snakeoil.pem
ssl_key = </etc/ssl/private/ssl-cert-snakeoil.key

service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0660
    user = postfix
    group = postfix
  }
}
EOF

echo "==> OpenDKIM (inet:8891 — Postfix ile uyumlu)..."
install -d -m 0750 /etc/opendkim/keys/default
if [[ ! -f /etc/opendkim/keys/default/default.private ]]; then
  opendkim-genkey -b 2048 -d "${HOST_FQDN}" -D /etc/opendkim/keys/default -s default -v
  chown -R opendkim:opendkim /etc/opendkim/keys
fi

cat >/etc/opendkim.conf <<EOF
Syslog                  yes
Canonicalization        relaxed/simple
Mode                    sv
SubDomains              no
AutoRestart             yes
Background              yes
SignatureAlgorithm      rsa-sha256

KeyTable                refile:/etc/opendkim/key.table
SigningTable            refile:/etc/opendkim/signing.table
ExternalIgnoreList      /etc/opendkim/trusted.hosts
InternalHosts           /etc/opendkim/trusted.hosts

Socket                  inet:8891@127.0.0.1
PidFile                 /run/opendkim/opendkim.pid
UMask                   007
UserID                  opendkim:opendkim
EOF

cat >/etc/opendkim/trusted.hosts <<EOF
127.0.0.1
localhost
${HOST_FQDN}
EOF

cat >/etc/opendkim/key.table <<EOF
default._domainkey.${HOST_FQDN} ${HOST_FQDN}:default:/etc/opendkim/keys/default/default.private
EOF

cat >/etc/opendkim/signing.table <<EOF
*@${HOST_FQDN} default._domainkey.${HOST_FQDN}
EOF

echo "==> Roundcube (localhost IMAPS/SMTP submission)..."
install -d -m 0755 /etc/roundcube
cat >/etc/roundcube/config.local.inc.php <<'PHP'
<?php
$config['product_name'] = 'Panelze Webmail';
$config['default_host'] = 'ssl://127.0.0.1';
$config['default_port'] = 993;
$config['imap_conn_options'] = [
  'ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ],
];
$config['smtp_server'] = 'tls://127.0.0.1';
$config['smtp_port'] = 587;
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';
$config['smtp_conn_options'] = [
  'ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ],
];
PHP

echo "==> Nginx (webmail.* — panel varsayılanına dokunulmadı)..."
cat >/etc/nginx/snippets/panelze-roundcube-php.conf <<'NGX'
location ~ ^/(bin|SQL|config|temp|logs)/ {
  deny all;
}
location ~ \.php$ {
  include snippets/fastcgi-php.conf;
  fastcgi_pass unix:PHP_SOCK_PLACEHOLDER;
  fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  include fastcgi_params;
}
NGX
sed -i "s|PHP_SOCK_PLACEHOLDER|${PHP_SOCK}|g" /etc/nginx/snippets/panelze-roundcube-php.conf

cat >/etc/nginx/snippets/panelze-roundcube-signon.conf <<'NGX'
location = /panelze-signon {
    include snippets/fastcgi-php.conf;
    fastcgi_param SCRIPT_FILENAME /usr/share/roundcube/panelze-signon.php;
    fastcgi_pass unix:PHP_SOCK_PLACEHOLDER;
}
NGX
sed -i "s|PHP_SOCK_PLACEHOLDER|${PHP_SOCK}|g" /etc/nginx/snippets/panelze-roundcube-signon.conf

install -d -m 0755 /usr/local/share/panelze
_HERE="$(cd "$(dirname "$0")" && pwd)"
if [[ -f "${_HERE}/panelze-roundcube-signon.php" ]]; then
  install -m 0644 "${_HERE}/panelze-roundcube-signon.php" /usr/local/share/panelze/panelze-roundcube-signon.php
  install -m 0644 "${_HERE}/panelze-roundcube-signon.php" /usr/share/roundcube/panelze-signon.php
fi

cat >/etc/nginx/sites-available/panelze-roundcube <<'NGX'
server {
  listen 80;
  listen [::]:80;
  server_name ~^webmail\.(?<dom>[a-z0-9.-]+)$;
  root /usr/share/roundcube;
  index index.php;
  client_max_body_size 25M;
  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }
  include snippets/panelze-roundcube-signon.conf;
  include snippets/panelze-roundcube-php.conf;
}
NGX

ln -sf /etc/nginx/sites-available/panelze-roundcube /etc/nginx/sites-enabled/50-panelze-roundcube.conf

CFG_SCRIPT="${_HERE}/../scripts/configure-roundcube-signon.sh"
if [[ -x "$CFG_SCRIPT" ]]; then
  bash "$CFG_SCRIPT" || true
elif [[ -f "$CFG_SCRIPT" ]]; then
  bash "$CFG_SCRIPT" || true
fi

echo "==> Servisler..."
systemctl enable postfix dovecot opendkim nginx
systemctl enable "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"
systemctl restart postfix
systemctl restart dovecot
systemctl restart opendkim
systemctl restart nginx
systemctl restart "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"
nginx -t

echo "==> Mail provision betiği..."
_HERE="$(cd "$(dirname "$0")" && pwd)"
MPROV="${_HERE}/panelze-mail-provision"
[[ -f "$MPROV" ]] || MPROV="/usr/local/sbin/panelze-mail-provision"
if [[ -x "$MPROV" ]]; then
  ENGINE_STATE="${PANELZE_HOME:-/var/www/panelze}/engine-state"
  [[ -d "$ENGINE_STATE/mail" ]] && bash "$MPROV" "$ENGINE_STATE" || true
fi

echo ""
echo "=== Panelze mail stack tamam (mail-stack-webmail) ==="
echo "FQDN: ${HOST_FQDN}"
echo "Güvenlik duvarı önerisi: ufw allow 25,80,443,143,465,587,993/tcp"
echo ""
echo "DKIM DNS (default._domainkey.${HOST_FQDN} TXT):"
[[ -f /etc/opendkim/keys/default/default.txt ]] && cat /etc/opendkim/keys/default/default.txt
echo ""
echo "Teslim edilebilirlik: SPF, DKIM, DMARC, gönderici IP için PTR kayıtlarını tamamlayın."
echo "HTTPS: certbot --nginx -d webmail.ornekalan.com (veya uygun san)."
echo "OK mail-stack-webmail"
