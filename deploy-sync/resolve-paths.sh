# Panelze kurulum yolları (source ile kullanın).
# shellcheck shell=bash

resolve_panelze_home() {
  if [[ -n "${PANELZE_HOME:-}" ]]; then
    echo "$PANELZE_HOME"
    return
  fi
  local d
  for d in /var/www/panelze /var/www/hostvim; do
    if [[ -d "$d/panel" || -d "$d/engine" || -d "$d/.git" ]]; then
      echo "$d"
      return
    fi
  done
  echo /var/www/panelze
}

resolve_panel_root() {
  echo "$(resolve_panelze_home)/panel"
}

migrate_engine_config_to_panelze() {
  if [[ -f /etc/hostvim/engine.yaml && ! -f /etc/panelze/engine.yaml ]]; then
    mkdir -p /etc/panelze
    cp -a /etc/hostvim/engine.yaml /etc/panelze/engine.yaml
    chmod 640 /etc/panelze/engine.yaml
    chown root:www-data /etc/panelze/engine.yaml 2>/dev/null || true
    echo "==> engine.yaml taşındı: /etc/hostvim -> /etc/panelze"
  fi
}

migrate_engine_service_to_panelze() {
  if systemctl is-active --quiet hostvim-engine 2>/dev/null; then
    systemctl disable --now hostvim-engine 2>/dev/null || true
    echo "==> hostvim-engine durduruldu (panelze-engine kullanılıyor)"
  fi
}

# Eski dizin adı (/var/www/hostvim) hâlâ duruyorsa panelze yolunu symlink ile aç.
migrate_panelze_home_symlink() {
  if [[ -d /var/www/hostvim && ! -e /var/www/panelze ]]; then
    ln -s /var/www/hostvim /var/www/panelze
    echo "==> /var/www/panelze -> /var/www/hostvim (geçiş symlink)"
  fi
}
