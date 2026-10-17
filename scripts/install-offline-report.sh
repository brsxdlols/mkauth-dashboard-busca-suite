#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_ADMIN_DIR="${1:-/opt/mk-auth/admin}"
BACKUP_DIR="${2:?Informe o diretorio de backup}"
mkdir -p "$BACKUP_DIR"
if [ -f "${TARGET_ADMIN_DIR}/addons/addon.js" ]; then
  cp -a "${TARGET_ADMIN_DIR}/addons/addon.js" "${BACKUP_DIR}/addon.js.before"
fi
php "${SCRIPT_DIR}/install-offline-database.php"
python3 "${SCRIPT_DIR}/install-offline-menu.py" "${TARGET_ADMIN_DIR}"
echo '[offline] Relatorio instalado com atalho no menu Clientes.'
