#!/usr/bin/env bash
set -euo pipefail

BOOTSTRAP_STARTED=$SECONDS

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
report_failure() {
  local status=$?
  if { [ -t 2 ] || [ "${FORCE_COLOR:-0}" = "1" ]; } && [ -z "${NO_COLOR:-}" ]; then
    printf '\033[31m[erro] Instalacao interrompida (codigo %s). Consulte a mensagem acima.\033[0m\n' "$status" >&2
  else
    printf '[erro] Instalacao interrompida (codigo %s). Consulte a mensagem acima.\n' "$status" >&2
  fi
  return "$status"
}
trap report_failure ERR

fetch() {
  local url="$1"
  local output="$2"
  if command -v curl >/dev/null 2>&1; then
    local display=--silent
    if [ -t 2 ]; then display=--progress-bar; fi
    curl --fail --location --show-error "$display" --retry 2 --retry-max-time 300 --connect-timeout 15 --max-time 300 -H 'Cache-Control: no-cache' -H 'Pragma: no-cache' "$url" -o "$output"
  elif command -v wget >/dev/null 2>&1; then
    wget --no-cache --timeout=30 --tries=3 -O "$output" "$url"
  else
    echo "Erro: curl ou wget e obrigatorio para baixar o pacote." >&2
    exit 1
  fi
}

CACHE_BUSTER="$(date +%s)"
ARCHIVE_URL="https://codeload.github.com/${REPO_OWNER}/${REPO_NAME}/tar.gz/refs/heads/${REPO_REF}?cache=${CACHE_BUSTER}"
echo "[1/4] Baixando pacote ${REPO_OWNER}/${REPO_NAME}@${REPO_REF}"
fetch "${ARCHIVE_URL}" "${ARCHIVE_PATH}"

echo "[2/4] Extraindo pacote"
tar -xzf "${ARCHIVE_PATH}" -C "${TMP_DIR}"
PACKAGE_DIR="$(find "${TMP_DIR}" -mindepth 1 -maxdepth 1 -type d -name "${REPO_NAME}-*" | head -n 1)"

if [ -z "${PACKAGE_DIR}" ] || [ ! -d "${PACKAGE_DIR}" ]; then
  echo "Erro: nao foi possivel localizar o diretorio extraido do pacote." >&2
  exit 1
fi

# Normalize shell files only inside this extracted package. Native files are untouched.
find "${PACKAGE_DIR}" -type f -name '*.sh' -exec sed -i 's/\r$//' {} +

echo "[3/4] Executando instalador local"
chmod +x "${PACKAGE_DIR}/install.sh"
if { [ -t 1 ] || [ "${FORCE_COLOR:-0}" = "1" ]; } && [ -z "${NO_COLOR:-}" ]; then
  # pipefail retains any installer failure; awk only colors its output.
  python3 -u "${PACKAGE_DIR}/scripts/install-progress.py" --log-dir "${BACKUP_ROOT}" -- bash "${PACKAGE_DIR}/install.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_ROOT}" 2>&1 | awk -f "${PACKAGE_DIR}/scripts/install-output-colors.awk"
else
  python3 -u "${PACKAGE_DIR}/scripts/install-progress.py" --log-dir "${BACKUP_ROOT}" -- bash "${PACKAGE_DIR}/install.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_ROOT}"
fi

if { [ -t 1 ] || [ "${FORCE_COLOR:-0}" = "1" ]; } && [ -z "${NO_COLOR:-}" ]; then
  printf '\033[32m[4/4] Instalacao remota concluida em %ss\033[0m\n' "$((SECONDS - BOOTSTRAP_STARTED))"
else
  echo "[4/4] Instalacao remota concluida em $((SECONDS - BOOTSTRAP_STARTED))s"
fi
