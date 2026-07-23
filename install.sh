#!/usr/bin/env bash
set -euo pipefail

TARGET_ADMIN_DIR="${1:-/opt/mk-auth/admin}"
TARGET_ADDONS_DIR="${TARGET_ADMIN_DIR}/addons"
BACKUP_ROOT="${2:-/opt/mk-auth/backups/codex-install}"
STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="${BACKUP_ROOT}/${STAMP}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

lint_file() {
  local file="$1"
  if command -v php >/dev/null 2>&1 && [ -f "$file" ]; then
    php -l "$file" >/dev/null
  fi
}

lint_tree() {
  local dir="$1"
  [ -d "$dir" ] || return 0
  find "$dir" -type f \( -name '*.php' -o -name '*.hhvm' \) -print0 | while IFS= read -r -d '' file; do
    lint_file "$file"
  done
}

echo "[1/4] Validando caminhos"
test -d "${TARGET_ADMIN_DIR}"
mkdir -p "${BACKUP_DIR}"

echo "[2/4] Gerando backup"
mkdir -p "${BACKUP_DIR}/admin" "${BACKUP_DIR}/addons"
if [ -f "${TARGET_ADMIN_DIR}/index.hhvm" ]; then
  cp -a "${TARGET_ADMIN_DIR}/index.hhvm" "${BACKUP_DIR}/admin/index.hhvm"
fi
if [ -d "${TARGET_ADDONS_DIR}/dashboard" ]; then
  cp -a "${TARGET_ADDONS_DIR}/dashboard" "${BACKUP_DIR}/addons/dashboard"
fi
if [ -d "${TARGET_ADDONS_DIR}/busca_inteligente" ]; then
  cp -a "${TARGET_ADDONS_DIR}/busca_inteligente" "${BACKUP_DIR}/addons/busca_inteligente"
fi

echo "[3/4] Instalando arquivos"
mkdir -p "${TARGET_ADDONS_DIR}"
cp -a "${SCRIPT_DIR}/admin/index.hhvm" "${TARGET_ADMIN_DIR}/index.hhvm"
rm -rf "${TARGET_ADDONS_DIR}/dashboard"
cp -a "${SCRIPT_DIR}/addons/dashboard" "${TARGET_ADDONS_DIR}/dashboard"
rm -rf "${TARGET_ADDONS_DIR}/busca_inteligente"
cp -a "${SCRIPT_DIR}/addons/busca_inteligente" "${TARGET_ADDONS_DIR}/busca_inteligente"

echo "[4/4] Validando instalacao"
lint_file "${TARGET_ADMIN_DIR}/index.hhvm"
lint_tree "${TARGET_ADDONS_DIR}/dashboard"
lint_tree "${TARGET_ADDONS_DIR}/busca_inteligente"

echo "[5/5] Finalizado"
echo "Backup salvo em: ${BACKUP_DIR}"
echo "Instalacao concluida em: ${TARGET_ADMIN_DIR}"
