#!/usr/bin/env bash
# Mevcut mail-stack kurulumlarında Dovecot sanal kullanıcı kimlik doğrulamasını düzeltir.
set -euo pipefail

AUTH_CONF="/etc/dovecot/conf.d/10-auth.conf"
STACK_CONF="/etc/dovecot/conf.d/99-panelze-mail-stack.conf"

if [[ ! -f "$AUTH_CONF" ]]; then
  echo "Dovecot kurulu değil ($AUTH_CONF yok)." >&2
  exit 1
fi

if grep -q '^!include auth-system.conf.ext' "$AUTH_CONF"; then
  sed -i 's/^!include auth-system.conf.ext/#!include auth-system.conf.ext  # panelze: virtual mail only/' "$AUTH_CONF"
  echo "==> auth-system.conf.ext devre dışı (sanal kutular)"
fi

cat >"$STACK_CONF" <<'EOF'
mail_location = maildir:%h
mail_privileged_group = mail
auth_mechanisms = plain login
disable_plaintext_auth = no

passdb {
  driver = passwd-file
  args = scheme=SHA512-CRYPT username_format=%u /etc/dovecot/passwd
}

userdb {
  driver = passwd-file
  args = username_format=%u /etc/dovecot/passwd
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

STATE_DIR="${1:-}"
if [[ -z "$STATE_DIR" ]]; then
  for candidate in /var/www/panelze/data/engine-state /var/www/hostvim/data/engine-state; do
    if [[ -d "$candidate/mail" ]]; then
      STATE_DIR="$candidate"
      break
    fi
  done
fi

if [[ -n "$STATE_DIR" ]] && [[ -x /usr/local/sbin/panelze-mail-provision ]]; then
  /usr/local/sbin/panelze-mail-provision "$STATE_DIR"
  echo "==> Mail provision: $STATE_DIR"
fi

if [[ -f /etc/dovecot/passwd ]]; then
  chown root:dovecot /etc/dovecot/passwd 2>/dev/null || true
  chmod 640 /etc/dovecot/passwd
fi

doveconf -n >/dev/null
systemctl restart dovecot
echo "OK dovecot-virtual-auth"
