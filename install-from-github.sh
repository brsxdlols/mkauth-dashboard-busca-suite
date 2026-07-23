#!/usr/bin/env bash
set -euo pipefail

REPO_OWNER="${REPO_OWNER:-brsxdlols}"
REPO_NAME="${REPO_NAME:-mkauth-dashboard-busca-suite}"
REPO_REF="${REPO_REF:-main}"
TARGET_ADMIN_DIR="${1:-/opt/mk-auth/admin}"
BACKUP_ROOT="${2:-/opt/mk-auth/backups/codex-install}"
TMP_DIR="$(mktemp -d)"
ARCHIVE_PATH="${TMP_DIR}/repo.tar.gz"

cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

fetch() {
  local url="$1"
  local output="$2"
  if command -v curl >/dev/null 2>&1; then
    curl -fsSL "$url" -o "$output"
  elif command -v wget >/dev/null 2>&1; then
    wget -qO "$output" "$url"
  else
    echo "Erro: curl ou wget e obrigatorio para baixar o pacote." >&2
    exit 1
  fi
}

ARCHIVE_URL="https://github.com/${REPO_OWNER}/${REPO_NAME}/archive/refs/heads/${REPO_REF}.tar.gz"
echo "[1/4] Baixando pacote ${REPO_OWNER}/${REPO_NAME}@${REPO_REF}"
fetch "${ARCHIVE_URL}" "${ARCHIVE_PATH}"

echo "[2/4] Extraindo pacote"
tar -xzf "${ARCHIVE_PATH}" -C "${TMP_DIR}"
PACKAGE_DIR="$(find "${TMP_DIR}" -mindepth 1 -maxdepth 1 -type d -name "${REPO_NAME}-*" | head -n 1)"

if [ -z "${PACKAGE_DIR}" ] || [ ! -d "${PACKAGE_DIR}" ]; then
  echo "Erro: nao foi possivel localizar o diretorio extraido do pacote." >&2
  exit 1
fi

echo "[3/4] Executando instalador local"
chmod +x "${PACKAGE_DIR}/install.sh"
"${PACKAGE_DIR}/install.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_ROOT}"

echo "[4/4] Instalacao remota concluida"
