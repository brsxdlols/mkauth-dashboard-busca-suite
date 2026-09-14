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

install_client_audit_hook() {
  local target="${TARGET_ADMIN_DIR}/scripts/mk-auth.js"
  local hook_line=';document.addEventListener("DOMContentLoaded",function(){if(!document.getElementById("mka-client-update-audit")){var s=document.createElement("script");s.id="mka-client-update-audit";s.src="/admin/addons/shared/client_update_audit.js?v=2";document.head.appendChild(s);}});'

  if [ ! -f "${target}" ]; then
    echo "[aviso] scripts/mk-auth.js nao encontrado; auditoria de alteracoes nao foi ativada."
    return 0
  fi

  if grep -q "mka-client-update-audit" "${target}"; then
    sed -i 's/client_update_audit\.js?v=[0-9][0-9]*/client_update_audit.js?v=2/g' "${target}"
    return 0
  fi

  printf '%s\n' "${hook_line}" >> "${target}"
}

install_branding() {
  local footer="${TARGET_ADMIN_DIR}/rodape.php"

  if [ -f "${footer}" ]; then
    sed -i "s#REDECLOUD &copy; <?= date('Y') ?> - Desenvolvido por redecloud.net.br\.#VPSCLOUD - BRUNO FONTES NETWORK CONSULTING \&copy; <?= date('Y') ?> -#g" "${footer}"
    sed -i 's/REDECLOUD/VPSCLOUD - BRUNO FONTES NETWORK CONSULTING/g; s/redecloud\.net\.br/VPSCLOUD - BRUNO FONTES NETWORK CONSULTING/g' "${footer}"
  fi

  if [ -d "${TARGET_ADDONS_DIR}" ]; then
    find "${TARGET_ADDONS_DIR}" -type f -name manifest.json -exec \
      sed -i 's/"author": "REDECLOUD"/"author": "VPSCLOUD - BRUNO FONTES NETWORK CONSULTING"/g' {} +
  fi
}

apply_dashboard_online_additional_fix() {
  echo "[4/8] Aplicando correcao de online para clientes adicionais"

  if ! command -v python3 >/dev/null 2>&1; then
    echo "[aviso] python3 nao encontrado; os arquivos empacotados ja possuem a correcao online."
    return 0
  fi

  python3 - "${TARGET_ADDONS_DIR}/dashboard/index.php" "${TARGET_ADDONS_DIR}/dashboard/nav/index.php" <<'PY'
from pathlib import Path
import re
import sys

old_additional = (
    "SELECT cli_add.login as login_add FROM sis_adicional cli_add "
    "LEFT JOIN sis_cliente c ON cli_add.login = c.login "
    "WHERE $grupos c.cli_ativado LIKE 's'"
)
new_additional = (
    "SELECT cli_add.username as login_add FROM sis_adicional cli_add "
    "LEFT JOIN sis_cliente c ON cli_add.login = c.login "
    "WHERE $grupos c.cli_ativado LIKE 's'"
)
online_pattern = re.compile(
    r'\$query_clientes_online\s*=\s*mysqli_query\(\$conn,\s*"'
    r'SELECT r\.username FROM radacct r(?: FORCE INDEX \(acctstoptime\))? '
    r'LEFT JOIN sis_cliente c ON c\.login = r\.username '
    r'WHERE \$grupos r\.acctstoptime IS NULL "\);'
)
new_online = (
    '$query_clientes_online = mysqli_query($conn, '
    '"SELECT r.username FROM radacct r FORCE INDEX (acctstoptime) '
    'WHERE r.acctstoptime IS NULL");'
)

for value in sys.argv[1:]:
    path = Path(value)
    if not path.is_file():
        print(f"[aviso] {path}: arquivo nao encontrado; ignorado")
        continue
    text = path.read_text(encoding="utf-8", errors="surrogateescape")
    changes = text.count(old_additional)
    text = text.replace(old_additional, new_additional)
    text, online_changes = online_pattern.subn(new_online, text)
    changes += online_changes
    if changes:
        path.write_text(text, encoding="utf-8", errors="surrogateescape")
        print(f"[ok] {path}: {changes} correcao(oes) aplicada(s)")
    elif new_additional in text or new_online in text:
        print(f"[ok] {path}: correcao ja aplicada")
    else:
        print(f"[info] {path}: consultas alvo nao existem nesta versao")
PY
}

install_additional_block_patch() {
  echo "[6/8] Instalando propagacao de bloqueio para adicionais"
  local patch_ref="${MKAUTH_TOOLKIT_REF:-agent/additional-block-patch}"
  local patch_url="https://raw.githubusercontent.com/brsxdlols/mkauth-toolkit/${patch_ref}/installers/install-additional-block.sh"
  local patch_installer
  patch_installer="$(mktemp /tmp/install-additional-block.XXXXXX.sh)"

  if command -v curl >/dev/null 2>&1; then
    if ! curl -fsSL --retry 3 --connect-timeout 15 "${patch_url}" -o "${patch_installer}"; then
      echo "[aviso] nao foi possivel baixar o patch de bloqueio dos adicionais."
      rm -f -- "${patch_installer}"
      return 0
    fi
  elif command -v wget >/dev/null 2>&1; then
    if ! wget -q --timeout=15 --tries=3 -O "${patch_installer}" "${patch_url}"; then
      echo "[aviso] nao foi possivel baixar o patch de bloqueio dos adicionais."
      rm -f -- "${patch_installer}"
      return 0
    fi
  else
    echo "[aviso] curl/wget ausente; patch de bloqueio dos adicionais nao instalado."
    rm -f -- "${patch_installer}"
    return 0
  fi

  chmod 0750 "${patch_installer}"
  if ! MKAUTH_TOOLKIT_REF="${patch_ref}" bash "${patch_installer}"; then
    echo "[aviso] o patch de bloqueio dos adicionais nao e compativel com este esquema; dashboard e busca permanecem instaladas."
  fi
  rm -f -- "${patch_installer}"
}

install_reconcile() {
  echo "[8/8] Instalando reconcile de Radius"
  local reconcile_installer="${SCRIPT_DIR}/scripts/install-radius-reconcile.sh"

  if [ ! -f "${reconcile_installer}" ]; then
    echo "[aviso] instalador do reconcile nao foi encontrado no pacote."
    return 0
  fi

  if ! bash "${reconcile_installer}"; then
    echo "[aviso] instalacao do reconcile retornou erro; continuei com a dashboard instalada."
  fi
}

install_manual_block_enforcer() {
  echo "[7/8] Protegendo bloqueios manuais contra desbloqueio automatico"
  local source="${SCRIPT_DIR}/scripts/manual-block-enforcer.php"
  local target="/opt/mk-auth/scripts/manual-block-enforcer.php"
  if [ ! -f "${source}" ]; then
    echo "[aviso] verificador de bloqueio manual nao encontrado no pacote."
    return 0
  fi
  install -o root -g root -m 0750 "${source}" "${target}"
  cat > /etc/cron.d/mkauth-manual-block-enforcer <<'CRON'
* * * * * root /usr/bin/php /opt/mk-auth/scripts/manual-block-enforcer.php >/dev/null 2>&1
CRON
  chmod 0644 /etc/cron.d/mkauth-manual-block-enforcer
  /usr/bin/php "${target}" || true
}

echo "[1/8] Validando caminhos"
test -d "${TARGET_ADMIN_DIR}"
mkdir -p "${BACKUP_DIR}"

echo "[2/8] Gerando backup"
mkdir -p "${BACKUP_DIR}/admin" "${BACKUP_DIR}/addons"
if [ -f "${TARGET_ADMIN_DIR}/index.hhvm" ]; then
  cp -a "${TARGET_ADMIN_DIR}/index.hhvm" "${BACKUP_DIR}/admin/index.hhvm"
fi
if [ -f "${TARGET_ADMIN_DIR}/scripts/mk-auth.js" ]; then
  mkdir -p "${BACKUP_DIR}/admin/scripts"
  cp -a "${TARGET_ADMIN_DIR}/scripts/mk-auth.js" "${BACKUP_DIR}/admin/scripts/mk-auth.js"
fi
if [ -f "${TARGET_ADMIN_DIR}/rodape.php" ]; then
  cp -a "${TARGET_ADMIN_DIR}/rodape.php" "${BACKUP_DIR}/admin/rodape.php"
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

echo "[3/8] Instalando arquivos"
mkdir -p "${TARGET_ADDONS_DIR}"
cp -a "${SCRIPT_DIR}/admin/index.hhvm" "${TARGET_ADMIN_DIR}/index.hhvm"
rm -rf "${TARGET_ADDONS_DIR}/dashboard"
cp -a "${SCRIPT_DIR}/addons/dashboard" "${TARGET_ADDONS_DIR}/dashboard"
rm -rf "${TARGET_ADDONS_DIR}/busca_inteligente"
cp -a "${SCRIPT_DIR}/addons/busca_inteligente" "${TARGET_ADDONS_DIR}/busca_inteligente"
mkdir -p "${TARGET_ADDONS_DIR}/busca_inteligente/tmp"
chmod 0770 "${TARGET_ADDONS_DIR}/busca_inteligente/tmp"
if id www-data >/dev/null 2>&1; then
  chown www-data:www-data "${TARGET_ADDONS_DIR}/busca_inteligente/tmp"
elif id apache >/dev/null 2>&1; then
  chown apache:apache "${TARGET_ADDONS_DIR}/busca_inteligente/tmp"
fi
rm -rf "${TARGET_ADDONS_DIR}/dashboard-legado"
cp -a "${SCRIPT_DIR}/addons/dashboard-legado" "${TARGET_ADDONS_DIR}/dashboard-legado"
rm -rf "${TARGET_ADDONS_DIR}/busca_inteligente-legado"
cp -a "${SCRIPT_DIR}/addons/busca_inteligente-legado" "${TARGET_ADDONS_DIR}/busca_inteligente-legado"
rm -rf "${TARGET_ADDONS_DIR}/shared"
cp -a "${SCRIPT_DIR}/addons/shared" "${TARGET_ADDONS_DIR}/shared"
install_client_audit_hook
install_branding
apply_dashboard_online_additional_fix

echo "[5/8] Validando instalacao"
lint_file "${TARGET_ADMIN_DIR}/index.hhvm"
# Lint only the entry points. Third-party/legacy helper files can have syntax
# intended for another PHP release and must not abort an otherwise valid install.
lint_file "${TARGET_ADDONS_DIR}/shared/layout_mode.php"
lint_file "${TARGET_ADDONS_DIR}/shared/client_update_audit.php"
lint_file "${TARGET_ADDONS_DIR}/shared/manual_block_audit.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/index.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/delete_installation_request.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/mkauth_dashboard_top.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/exibir_resultados.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_connections.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_pdf.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_photo.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_manual_block_audit.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard-legado/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente-legado/index.php"

install_additional_block_patch
install_manual_block_enforcer
install_reconcile

echo "[8/8] Finalizado"
echo "Backup salvo em: ${BACKUP_DIR}"
echo "Instalacao concluida em: ${TARGET_ADMIN_DIR}"
