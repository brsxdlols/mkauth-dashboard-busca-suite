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

install_reconcile() {
  local reconcile_url="https://raw.githubusercontent.com/brsxdlols/mkauth-toolkit/main/installers/install-radius-reconcile.sh"
  local reconcile_tmp="/root/install-radius-reconcile.sh"

  if ! command -v curl >/dev/null 2>&1; then
    echo "[aviso] curl nao encontrado; reconcile nao foi instalado automaticamente."
    return 0
  fi

  echo "[5/5] Instalando reconcile de Radius"
  if curl -fsSL "${reconcile_url}" -o "${reconcile_tmp}"; then
    chmod +x "${reconcile_tmp}"
    if ! bash "${reconcile_tmp}"; then
      echo "[aviso] instalacao do reconcile retornou erro; continuei com a dashboard instalada."
    fi
    rm -f "${reconcile_tmp}"
  else
    echo "[aviso] nao foi possivel baixar o reconcile; continuei com a dashboard instalada."
  fi
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
if [ -d "${TARGET_ADDONS_DIR}/dashboard-legado" ]; then
  cp -a "${TARGET_ADDONS_DIR}/dashboard-legado" "${BACKUP_DIR}/addons/dashboard-legado"
fi
if [ -d "${TARGET_ADDONS_DIR}/busca_inteligente-legado" ]; then
  cp -a "${TARGET_ADDONS_DIR}/busca_inteligente-legado" "${BACKUP_DIR}/addons/busca_inteligente-legado"
fi
if [ -d "${TARGET_ADDONS_DIR}/shared" ]; then
  cp -a "${TARGET_ADDONS_DIR}/shared" "${BACKUP_DIR}/addons/shared"
fi

echo "[3/4] Instalando arquivos"
mkdir -p "${TARGET_ADDONS_DIR}"
cp -a "${SCRIPT_DIR}/admin/index.hhvm" "${TARGET_ADMIN_DIR}/index.hhvm"
rm -rf "${TARGET_ADDONS_DIR}/dashboard"
cp -a "${SCRIPT_DIR}/addons/dashboard" "${TARGET_ADDONS_DIR}/dashboard"
rm -rf "${TARGET_ADDONS_DIR}/busca_inteligente"
cp -a "${SCRIPT_DIR}/addons/busca_inteligente" "${TARGET_ADDONS_DIR}/busca_inteligente"
rm -rf "${TARGET_ADDONS_DIR}/dashboard-legado"
cp -a "${SCRIPT_DIR}/addons/dashboard-legado" "${TARGET_ADDONS_DIR}/dashboard-legado"
rm -rf "${TARGET_ADDONS_DIR}/busca_inteligente-legado"
cp -a "${SCRIPT_DIR}/addons/busca_inteligente-legado" "${TARGET_ADDONS_DIR}/busca_inteligente-legado"
rm -rf "${TARGET_ADDONS_DIR}/shared"
cp -a "${SCRIPT_DIR}/addons/shared" "${TARGET_ADDONS_DIR}/shared"

echo "[4/4] Validando instalacao"
lint_file "${TARGET_ADMIN_DIR}/index.hhvm"
# Lint only the entry points. Third-party/legacy helper files can have syntax
# intended for another PHP release and must not abort an otherwise valid install.
lint_file "${TARGET_ADDONS_DIR}/shared/layout_mode.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/index.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/mkauth_dashboard_top.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/exibir_resultados.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard-legado/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente-legado/index.php"

install_reconcile

echo "[5/5] Finalizado"
echo "Backup salvo em: ${BACKUP_DIR}"
echo "Instalacao concluida em: ${TARGET_ADMIN_DIR}"
