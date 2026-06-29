#!/usr/bin/env bash
# Panelze SSL onarımı:
#  - renewal webroot'u nginx acme-challenge root'uyla uyuşmayan sertifikaları düzeltir
#    (yenileme ~60 günde patlamasın diye). LE çağrısı YOK; renewal .conf yerinde güncellenir.
#  - www DNS'i sunucuya gelen apex alan adlarının sertifikasına www ekler (yeniden alır).
# Başarısız doğrulama rate-limit'ine girmemek için her işlemden önce HTTP-01 erişilebilirliği test edilir.
# Kullanım: sudo fix-ssl-www-webroot.sh [SERVER_IP]
set -uo pipefail
SRV="${1:-207.180.237.13}"
CFG=/var/www/hostvim/data/ssl/letsencrypt/config
WD=/var/www/hostvim/data/ssl/letsencrypt/work
LD=/var/www/hostvim/data/ssl/letsencrypt/logs
MAP=/tmp/acmemap.txt
NEED_NGINX_RELOAD=0

[[ -f "$MAP" ]] || { echo "acme map yok: $MAP"; exit 1; }
declare -A ACME
while IFS=$'\t' read -r name root; do [[ -n "$name" ]] && ACME["$name"]="$root"; done < "$MAP"

reach() { # <domain> <webroot> -> 0 erisilebilir
  local d="$1" wr="$2" tok code
  tok="pzt-$RANDOM$RANDOM"
  mkdir -p "$wr/.well-known/acme-challenge" 2>/dev/null || return 1
  printf '%s' "$tok" > "$wr/.well-known/acme-challenge/$tok" 2>/dev/null || return 1
  chmod 644 "$wr/.well-known/acme-challenge/$tok" 2>/dev/null || true
  # -L: Let's Encrypt http-01 yönlendirmeleri (http->https) izler; biz de izleyelim.
  code=$(curl -sSL --max-time 12 -o /dev/null -w '%{http_code}' "http://$d/.well-known/acme-challenge/$tok" 2>/dev/null)
  rm -f "$wr/.well-known/acme-challenge/$tok" 2>/dev/null || true
  [[ "$code" == "200" ]]
}

conffix() { # <cn> <acmeRoot> — renewal webroot_path + webroot_map'i yerinde guncelle
  local cn="$1" ar="$2" conf="$CFG/renewal/$cn.conf"
  [[ -f "$conf" ]] || { echo "  ! renewal conf yok"; return 1; }
  cp -a "$conf" "$conf.bak-sslfix" 2>/dev/null || true
  # 1) webroot_path satiri (benzersiz anahtar — guvenli)
  sed -i "s#^webroot_path = .*#webroot_path = ${ar},#" "$conf"
  # 2) SADECE [[webroot_map]] bolumundeki "domain = yol" satirlari (config_dir/cert vb. korunur)
  awk -v ar="$ar" '
    /^\[\[webroot_map\]\]/ { inmap=1; print; next }
    inmap && /^\[/ { inmap=0 }
    inmap && /^[^[].*=/ { sub(/=.*/, "= " ar); print; next }
    { print }
  ' "$conf" > "$conf.tmp" && mv "$conf.tmp" "$conf"
  echo "  -> renewal webroot guncellendi: $ar"
}

printf '%-32s %s\n' "DOMAIN" "ISLEM"
for live in "$CFG"/live/*/; do
  cn=$(basename "$live")
  [[ "$cn" == "README" ]] && continue
  [[ -f "$live/fullchain.pem" ]] || continue
  ar="${ACME[$cn]:-}"
  if [[ -z "$ar" || "$ar" == "?" || "$ar" == /Applications/* ]]; then
    printf '%-32s %s\n' "$cn" "ATLA (acme root gecersiz: ${ar:-yok})"; continue
  fi
  conf="$CFG/renewal/$cn.conf"
  rnw=$(grep -oE '^webroot_path = .*' "$conf" 2>/dev/null | head -1 | sed 's/webroot_path = //; s/,$//')
  san=$(openssl x509 -in "$live/fullchain.pem" -noout -text 2>/dev/null | grep -A1 'Subject Alternative Name' | tail -1)
  haswww=no; echo "$san" | grep -qE "DNS:www\.$cn(,| |$)" && haswww=yes
  dots=$(printf '%s' "$cn" | tr -cd '.' | wc -c | tr -d ' ')
  apex=no; [[ "$dots" -eq 1 ]] && apex=yes
  wwwserved=no; [[ -n "${ACME[www.$cn]:-}" ]] && wwwserved=yes
  wwwdns=no; ip=$(dig +short "www.$cn" A 2>/dev/null | tail -1); [[ "$ip" == "$SRV" ]] && wwwdns=yes
  includewww=no
  [[ "$apex" == yes && "$wwwserved" == yes && "$wwwdns" == yes ]] && includewww=yes
  needwww=no; [[ "$includewww" == yes && "$haswww" == no ]] && needwww=yes
  mismatch=no; [[ "$rnw" != "$ar" ]] && mismatch=yes

  if [[ "$needwww" == no && "$mismatch" == no ]]; then
    printf '%-32s %s\n' "$cn" "OK (degisiklik yok)"; continue
  fi

  if [[ "$needwww" == yes ]]; then
    # apex erisilebilir mi
    if ! reach "$cn" "$ar"; then
      printf '%-32s %s\n' "$cn" "ATLA (apex HTTP-01 erisilemiyor: $ar)"; continue
    fi
    dargs=( -d "$cn" )
    wwwnote=""
    if reach "www.$cn" "$ar"; then dargs+=( -d "www.$cn" ); else wwwnote=" (www erisilemedi, apex-only)"; fi
    printf '%-32s %s\n' "$cn" "YENIDEN AL${wwwnote} -w $ar"
    if certbot certonly --webroot -w "$ar" "${dargs[@]}" --cert-name "$cn" \
        --config-dir "$CFG" --work-dir "$WD" --logs-dir "$LD" \
        --key-type ecdsa --expand --non-interactive --agree-tos >/tmp/cb-$cn.log 2>&1; then
      echo "  -> OK reissue"; NEED_NGINX_RELOAD=1
    else
      echo "  ! certbot HATA (bkz /tmp/cb-$cn.log): $(tail -3 /tmp/cb-$cn.log | tr '\n' ' ')"
    fi
    continue
  fi

  # sadece webroot uyusmazligi: yerinde conf duzelt (LE cagrisi yok)
  if [[ "$mismatch" == yes ]]; then
    if reach "$cn" "$ar"; then
      printf '%-32s %s\n' "$cn" "CONF DUZELT (webroot: $rnw -> $ar)"
      conffix "$cn" "$ar"
    else
      printf '%-32s %s\n' "$cn" "UYARI (acme erisilemiyor, conf elle bakilmali: $ar)"
    fi
  fi
done

if [[ "$NEED_NGINX_RELOAD" == 1 ]]; then
  nginx -t >/dev/null 2>&1 && systemctl reload nginx && echo "nginx reloaded"
fi
echo "BITTI"
