#!/usr/bin/env bash
# Geriye dönük kısayol → ensure-webserver-stack.sh
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec bash "$SCRIPT_DIR/ensure-webserver-stack.sh"
