#!/usr/bin/env bash
# Geriye uyumluluk: eski betik adı → hostvim-deploy-common.sh
_LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=hostvim-deploy-common.sh
source "$_LIB_DIR/hostvim-deploy-common.sh"
