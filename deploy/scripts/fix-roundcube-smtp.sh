#!/usr/bin/env bash
# Roundcube giden posta: aynı sunucudaki Postfix (127.0.0.1:25, TLS/SMTP auth yok).
# Müşteri ek SMTP ayarı yapmaz; mynetworks ile yerel relay güvenlidir.
set -euo pipefail

LOCAL="/etc/roundcube/config.local.inc.php"
MAIN="/etc/roundcube/config.inc.php"

if [[ ! -f "$LOCAL" ]]; then
  echo "Roundcube config.local.inc.php bulunamadı." >&2
  exit 1
fi

# Eski tls://587 satırlarını kaldır / güncelle
sed -i "s/\['smtp_server'\]/['smtp_host']/g" "$LOCAL"
sed -i "s|\$config\['smtp_host'\] = 'tls://127.0.0.1';|\$config['smtp_host'] = '127.0.0.1';|g" "$LOCAL"
sed -i "s|\$config\['smtp_host'\] = 'localhost:587';|\$config['smtp_host'] = '127.0.0.1';|g" "$LOCAL"
sed -i "s|\$config\['smtp_port'\] = 587;|\$config['smtp_port'] = 25;|g" "$LOCAL"
sed -i "s|\$config\['smtp_user'\] = '%u';|\$config['smtp_user'] = '';|g" "$LOCAL"
sed -i "s|\$config\['smtp_pass'\] = '%p';|\$config['smtp_pass'] = '';|g" "$LOCAL"

if ! grep -q "smtp_host" "$LOCAL"; then
  sed -i "/<?php/a \$config['smtp_host'] = '127.0.0.1';" "$LOCAL"
fi
if ! grep -q "smtp_port" "$LOCAL"; then
  sed -i "/smtp_host/a \$config['smtp_port'] = 25;" "$LOCAL"
fi
if ! grep -q "smtp_user" "$LOCAL"; then
  sed -i "/smtp_port/a \$config['smtp_user'] = '';\n\$config['smtp_pass'] = '';" "$LOCAL"
fi

if [[ -f "$MAIN" ]]; then
  sed -i "s|\$config\['smtp_host'\] = 'tls://127.0.0.1';|\$config['smtp_host'] = '127.0.0.1';|g" "$MAIN"
  sed -i "s|\$config\['smtp_host'\] = 'localhost:587';|\$config['smtp_host'] = '127.0.0.1';|g" "$MAIN"
  sed -i "s|\$config\['smtp_port'\] = 587;|\$config['smtp_port'] = 25;|g" "$MAIN"
  sed -i "s|\$config\['smtp_user'\] = '%u';|\$config['smtp_user'] = '';|g" "$MAIN"
  sed -i "s|\$config\['smtp_pass'\] = '%p';|\$config['smtp_pass'] = '';|g" "$MAIN"
fi

echo "OK roundcube-smtp (127.0.0.1:25 yerel relay, müşteri ayarı gerekmez)"
