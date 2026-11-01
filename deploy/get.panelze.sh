#!/usr/bin/env bash
#
# Panelze — tek satır kurulum girişi (https://get.panelze.sh)
# Sunucuda: curl -fsSL https://get.panelze.sh | bash
#
# Bu dosyayı get.panelze.sh kökünde "index" veya default olarak sunun (nginx örnek):
#   root /var/www/panelze-get;
#   location = / { default_type application/x-sh; try_files /get.panelze.sh =404; }
# veya: return 302 https://raw.githubusercontent.com/.../install-community.sh;
#
set -euo pipefail

INSTALL_SCRIPT="${PANELZE_INSTALL_COMMUNITY_SCRIPT:-${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh}}"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Panelze kurulumu yalnızca Linux sunucu içindir (Ubuntu 22.04/24.04, Debian 12)." >&2
  echo "SSH ile VPS'e bağlanıp orada çalıştırın: curl -fsSL https://get.panelze.com | bash" >&2
  exit 1
fi

# Erken OS kontrolü (asıl betik de doğrular)
if [[ -r /etc/os-release ]]; then
  # shellcheck source=/dev/null
  . /etc/os-release
  case "${ID:-}" in
    ubuntu|debian|linuxmint|pop) ;;
    rocky|almalinux|centos|rhel)
      echo "Hata: ${PRETTY_NAME:-$ID} henüz desteklenmiyor." >&2
      echo "Şimdilik Ubuntu 22.04/24.04 veya Debian 12 kullanın. Rocky/Alma desteği sırada." >&2
      exit 1
      ;;
    *)
      case " ${ID_LIKE:-} " in
        *" debian "*|*" ubuntu "*) ;;
        *)
          echo "Hata: Desteklenmeyen dağıtım: ${PRETTY_NAME:-${ID:-?}}" >&2
          echo "Desteklenen: Ubuntu 22.04/24.04, Debian 12." >&2
          exit 1
          ;;
      esac
      ;;
  esac
fi

curl -fsSL "$INSTALL_SCRIPT" | bash
