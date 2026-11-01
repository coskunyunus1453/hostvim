#!/usr/bin/env bash
# shellcheck shell=bash
# Panelze — dağıtım algılama (Debian/Ubuntu şimdi; Rocky/Alma sonra).
#
# Kullanım:
#   source deploy/host/lib/os-detect.sh
#   panelze_os_detect
#   panelze_os_assert_supported   # desteklenmeyen OS'ta exit 1

panelze_os_detect() {
  PANELZE_OS_ID=""
  PANELZE_OS_LIKE=""
  PANELZE_OS_VERSION_ID=""
  PANELZE_OS_CODENAME=""
  PANELZE_OS_PRETTY=""
  PANELZE_OS_FAMILY=""   # debian | rhel | other
  PANELZE_OS_PKG=""      # apt | dnf | unknown
  PANELZE_OS_SUPPORTED=0

  if [[ ! -r /etc/os-release ]]; then
    PANELZE_OS_FAMILY=other
    PANELZE_OS_PKG=unknown
    return 1
  fi

  # shellcheck source=/dev/null
  . /etc/os-release

  PANELZE_OS_ID="${ID:-}"
  PANELZE_OS_LIKE="${ID_LIKE:-}"
  PANELZE_OS_VERSION_ID="${VERSION_ID:-}"
  PANELZE_OS_CODENAME="${VERSION_CODENAME:-${UBUNTU_CODENAME:-}}"
  PANELZE_OS_PRETTY="${PRETTY_NAME:-$PANELZE_OS_ID $PANELZE_OS_VERSION_ID}"

  case "$PANELZE_OS_ID" in
    debian|ubuntu)
      PANELZE_OS_FAMILY=debian
      PANELZE_OS_PKG=apt
      ;;
    linuxmint|pop|elementary|zorin)
      # Ubuntu türevi
      PANELZE_OS_FAMILY=debian
      PANELZE_OS_PKG=apt
      PANELZE_OS_ID=ubuntu
      ;;
    rocky|almalinux|centos|rhel|ol)
      PANELZE_OS_FAMILY=rhel
      PANELZE_OS_PKG=dnf
      ;;
    *)
      case " $PANELZE_OS_LIKE " in
        *" debian "*|*" ubuntu "*)
          PANELZE_OS_FAMILY=debian
          PANELZE_OS_PKG=apt
          ;;
        *" rhel "*|*" fedora "*|*" centos "*)
          PANELZE_OS_FAMILY=rhel
          PANELZE_OS_PKG=dnf
          ;;
        *)
          PANELZE_OS_FAMILY=other
          PANELZE_OS_PKG=unknown
          ;;
      esac
      ;;
  esac

  panelze_os_evaluate_support
  return 0
}

panelze_os_evaluate_support() {
  PANELZE_OS_SUPPORTED=0
  PANELZE_OS_SUPPORT_NOTE=""

  case "$PANELZE_OS_FAMILY" in
    debian)
      case "$PANELZE_OS_ID" in
        ubuntu)
          case "$PANELZE_OS_VERSION_ID" in
            22.04|24.04|24.10|25.04)
              PANELZE_OS_SUPPORTED=1
              ;;
            20.04)
              PANELZE_OS_SUPPORTED=1
              PANELZE_OS_SUPPORT_NOTE="Ubuntu 20.04 eski; 22.04/24.04 önerilir."
              ;;
            *)
              PANELZE_OS_SUPPORT_NOTE="Ubuntu ${PANELZE_OS_VERSION_ID:-?} test edilmedi; 22.04/24.04 önerilir."
              # Bilinmeyen minor'da denemeye izin ver (apt ailesi)
              PANELZE_OS_SUPPORTED=1
              ;;
          esac
          ;;
        debian)
          case "$PANELZE_OS_VERSION_ID" in
            12|13)
              PANELZE_OS_SUPPORTED=1
              ;;
            11)
              PANELZE_OS_SUPPORTED=1
              PANELZE_OS_SUPPORT_NOTE="Debian 11 eski; Debian 12 önerilir."
              ;;
            *)
              PANELZE_OS_SUPPORT_NOTE="Debian ${PANELZE_OS_VERSION_ID:-?} test edilmedi; 12 önerilir."
              PANELZE_OS_SUPPORTED=1
              ;;
          esac
          ;;
        *)
          # Mint vb. Ubuntu türevi
          PANELZE_OS_SUPPORTED=1
          PANELZE_OS_SUPPORT_NOTE="Ubuntu türevi (${PANELZE_OS_ID}); Ubuntu 22.04/24.04 ile aynı yol kullanılır."
          ;;
      esac
      ;;
    rhel)
      PANELZE_OS_SUPPORTED=0
      PANELZE_OS_SUPPORT_NOTE="Rocky Linux / AlmaLinux / CentOS desteği yakında. Şimdilik Ubuntu 22.04/24.04 veya Debian 12 kullanın."
      ;;
    *)
      PANELZE_OS_SUPPORTED=0
      PANELZE_OS_SUPPORT_NOTE="Desteklenmeyen dağıtım. Ubuntu 22.04/24.04 veya Debian 12 gerekir."
      ;;
  esac
}

panelze_os_assert_supported() {
  if [[ -z "${PANELZE_OS_FAMILY:-}" ]]; then
    panelze_os_detect || true
  fi

  echo "==> Sistem: ${PANELZE_OS_PRETTY:-?} (family=${PANELZE_OS_FAMILY:-?} pkg=${PANELZE_OS_PKG:-?})"

  if [[ -n "${PANELZE_OS_SUPPORT_NOTE:-}" ]]; then
    echo "    Not: $PANELZE_OS_SUPPORT_NOTE"
  fi

  if [[ "${PANELZE_OS_SUPPORTED:-0}" != "1" ]]; then
    echo "Hata: Bu işletim sistemi şu an desteklenmiyor." >&2
    echo "Desteklenen: Ubuntu 22.04 / 24.04, Debian 12 (+13)." >&2
    echo "Sırada: Rocky Linux / AlmaLinux 9 (CentOS yerine)." >&2
    if [[ "${PANELZE_OS_FORCE:-0}" == "1" ]]; then
      echo "UYARI: PANELZE_OS_FORCE=1 — devam ediliyor (destek garantisi yok)." >&2
      return 0
    fi
    exit 1
  fi
}

# Debian ailesinde Ubuntu'ya özgü paketleri ayır.
panelze_os_apt_base_packages() {
  local pkgs=(
    nginx curl ca-certificates sudo git rsync unzip zip
    sqlite3 acl lsb-release gnupg
  )
  case "${PANELZE_OS_ID:-}" in
    ubuntu|linuxmint|pop|elementary|zorin)
      pkgs+=(software-properties-common)
      ;;
    debian)
      # add-apt-repository (universe) Ubuntu'ya özgü; Debian'da gerekmez.
      # software-properties-common Debian'da da var ama zorunlu değil.
      pkgs+=(apt-transport-https)
      ;;
    *)
      pkgs+=(software-properties-common apt-transport-https)
      ;;
  esac
  printf '%s\n' "${pkgs[@]}"
}

# Roundcube için Ubuntu universe; Debian'da main'de.
panelze_os_ensure_roundcube_repo() {
  if apt-cache show roundcube-core &>/dev/null; then
    return 0
  fi
  case "${PANELZE_OS_ID:-}" in
    ubuntu|linuxmint|pop)
      if command -v add-apt-repository >/dev/null 2>&1; then
        add-apt-repository -y universe 2>/dev/null || true
        apt-get update -qq 2>/dev/null || true
      fi
      ;;
  esac
}
