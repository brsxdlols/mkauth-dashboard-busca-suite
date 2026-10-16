#!/usr/bin/env bash
set -euo pipefail

exec 8>/var/lock/mkauth-suite-install.lock
flock -n 8 || { echo 'Outra instalacao da suite esta em andamento.' >&2; exit 1; }

TARGET_ADMIN_DIR="${1:-/opt/mk-auth/admin}"
TARGET_ADDONS_DIR="${TARGET_ADMIN_DIR}/addons"
BACKUP_ROOT="${2:-/opt/mk-auth/backups/codex-install}"
STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="${BACKUP_ROOT}/${STAMP}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Release accumulated reconcile logs before generating installation backups.
bash "${SCRIPT_DIR}/scripts/install-radius-log-cleanup.sh"

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

  # Performance installation never invokes a writer against accounting.
  # Existing reconciliation jobs are preserved; new ones require separate maintenance.
  echo "[info] reconciliador disponivel no pacote; execucao e instalacao de cron nao realizadas nesta etapa."
  cp "${SCRIPT_DIR}/scripts/radacct-maintenance.sh" /opt/mk-auth/scripts/mkauth-radacct-maintenance.sh
  chmod 700 /opt/mk-auth/scripts/mkauth-radacct-maintenance.sh
}

remove_manual_block_guard() {
  echo "[7/8] Removendo a protecao de bloqueio persistente"
  local installer="${SCRIPT_DIR}/scripts/install-manual-block-guard.sh"
  if [ ! -f "${installer}" ]; then
    echo "[aviso] rotina de remocao da protecao de bloqueio nao encontrada."
    return 0
  fi
  bash "${installer}" "${BACKUP_DIR}/manual-block"
}

check_online_offline() {
  local checker="${SCRIPT_DIR}/scripts/check-online-offline.sh"
  local report="${BACKUP_DIR}/online-offline-check.log"
  echo "[checagem] Verificando online/offline sem alterar o banco"
  if [ ! -f "$checker" ]; then
    echo "[aviso] ferramenta de checagem online/offline ausente."
    return 0
  fi
  mkdir -p /opt/mk-auth/scripts
  cp "$checker" /opt/mk-auth/scripts/mkauth-check-online-offline.sh
  chmod 700 /opt/mk-auth/scripts/mkauth-check-online-offline.sh
  local status=0
  if command -v timeout >/dev/null 2>&1; then
    timeout 45s bash "$checker" "${TARGET_ADDONS_DIR}/shared/online_rule.php" > "$report" 2>&1 || status=$?
  else
    echo "[aviso] timeout ausente; checagem automatica nao executada." > "$report"
    status=1
  fi
  cat "$report"
  if [ "$status" -ne 0 ]; then
    echo "[aviso] checagem online/offline incompleta (codigo $status). Consulte $report."
  fi
}

echo "[1/8] Validando caminhos"
test -d "${TARGET_ADMIN_DIR}"
for addon in rel_clientes rel_caixa graf_faturamento radius; do
  test -f "${SCRIPT_DIR}/addons/${addon}/index.php"
  lint_file "${SCRIPT_DIR}/addons/${addon}/index.php"
done
mkdir -p "${BACKUP_DIR}"

# Preserve the exact, previously tested direct rule instead of downgrading it.
if command -v python3 >/dev/null 2>&1 && [ -f "${TARGET_ADDONS_DIR}/busca_inteligente/index.php" ]; then
  python3 - "${TARGET_ADDONS_DIR}/busca_inteligente/index.php" "${BACKUP_DIR}/existing-online-rule" <<'PY'
from pathlib import Path
import re, sys
text=Path(sys.argv[1]).read_text(encoding='utf-8-sig')
match=re.search(r'\$onlineClientSql\s*=\s*"(.*?)";',text,re.S)
known="(EXISTS (SELECT 1 FROM radacct r WHERE r.acctstoptime IS NULL AND r.username=c.login) OR EXISTS (SELECT 1 FROM sis_adicional a INNER JOIN radacct r ON r.username=a.username AND r.acctstoptime IS NULL WHERE a.login=c.login))"
direct=bool(match and re.sub(r'\s+','',match.group(1))==re.sub(r'\s+','',known)) or "define('MKA_SEARCH_PREEXISTING_DIRECT',true)" in text
Path(sys.argv[2]).write_text('direct' if direct else 'default')
PY
fi

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

# Install the complete Radius addon first; it replaces radius/ and must not erase
# the suite live.php/current_state.php/suite-live.css overlay installed below.
bash "${SCRIPT_DIR}/scripts/install-radius-dependency.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/radius-package"

echo "[3/8] Instalando arquivos"
for addon in rel_clientes rel_caixa graf_faturamento radius; do
  if [ -d "${TARGET_ADDONS_DIR}/${addon}" ]; then
    cp -a "${TARGET_ADDONS_DIR}/${addon}" "${BACKUP_DIR}/addons/${addon}"
  fi
  mkdir -p "${TARGET_ADDONS_DIR}/${addon}"
  if [ "$addon" = radius ] && [ -f "${TARGET_ADDONS_DIR}/radius/index.php" ] && ! grep -Fq "require __DIR__.'/live.php';" "${TARGET_ADDONS_DIR}/radius/index.php"; then
    # Preserve the standalone Radius Logs addon and its existing settings/API.
    cp -a "${SCRIPT_DIR}/addons/radius/live.php" "${SCRIPT_DIR}/addons/radius/history.php" "${SCRIPT_DIR}/addons/radius/current_state.php" "${SCRIPT_DIR}/addons/radius/preferences.php" "${TARGET_ADDONS_DIR}/radius/"
    if [ ! -f "${TARGET_ADDONS_DIR}/radius/radius_lib.php" ]; then
      cp -a "${SCRIPT_DIR}/addons/radius/radius_lib.php" "${TARGET_ADDONS_DIR}/radius/"
    fi
  else
    cp -a "${SCRIPT_DIR}/addons/${addon}/." "${TARGET_ADDONS_DIR}/${addon}/"
  fi
done
# Keep the suite modal stylesheet separate from a standalone Radius addon.
cp "${SCRIPT_DIR}/addons/radius/radius.css" "${TARGET_ADDONS_DIR}/radius/suite-live.css"
# Grant only the web user's read access to the RADIUS log, including rotation.
for web_user in www-data apache; do
  id "$web_user" >/dev/null 2>&1 || continue
  for radius_log in /var/log/freeradius/radius.log /var/log/radius/radius.log; do
    [ -f "$radius_log" ] || continue
    if command -v setfacl >/dev/null 2>&1; then
      setfacl -m "u:$web_user:--x" "$(dirname "$radius_log")"
      setfacl -m "u:$web_user:r--" "$radius_log"
      setfacl -m "d:u:$web_user:r-X" "$(dirname "$radius_log")"
    else
      echo "[aviso] Confira a leitura de $radius_log pelo usuario $web_user; setfacl indisponivel."
    fi
  done
  break
done
# Keep uploaded contracts outside addon directories replaced by upgrades.
mkdir -p /opt/mk-auth/contract-uploads
chmod 0770 /opt/mk-auth/contract-uploads
if id www-data >/dev/null 2>&1; then
  chown www-data:www-data /opt/mk-auth/contract-uploads
elif id apache >/dev/null 2>&1; then
  chown apache:apache /opt/mk-auth/contract-uploads
fi
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
if [ -f "${BACKUP_DIR}/existing-online-rule" ] && [ "$(cat "${BACKUP_DIR}/existing-online-rule")" = direct ]; then
  python3 - "${TARGET_ADDONS_DIR}/busca_inteligente/index.php" <<'PY'
from pathlib import Path
import sys
p=Path(sys.argv[1]);text=p.read_text(encoding='utf-8')
needle='$onlineClientSql = mka_search_online_family_sql($link);'
if text.count(needle)!=1: raise SystemExit('Regra preexistente nao pode ser preservada automaticamente; revisar backup.')
p.write_text(text.replace(needle,"define('MKA_SEARCH_PREEXISTING_DIRECT',true);\n    "+needle),encoding='utf-8')
PY
  echo '[OK] Regra direta preexistente preservada; nao foi substituida pela regra normalizada.'
fi

echo "[5/8] Validando instalacao"
lint_file "${TARGET_ADMIN_DIR}/index.hhvm"
# Lint only the entry points. Third-party/legacy helper files can have syntax
# intended for another PHP release and must not abort an otherwise valid install.
lint_file "${TARGET_ADDONS_DIR}/shared/layout_mode.php"
lint_file "${TARGET_ADDONS_DIR}/shared/installations.php"
lint_file "${TARGET_ADDONS_DIR}/shared/client_support.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_support_api.php"
lint_file "${TARGET_ADDONS_DIR}/shared/visit_reminders.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/visit_reminders_api.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/instalacoes.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/installation_schedule_api.php"
lint_file "${TARGET_ADDONS_DIR}/shared/attendance_map.php"
lint_file "${TARGET_ADDONS_DIR}/shared/online_rule.php"
lint_file "${TARGET_ADDONS_DIR}/shared/search_performance.php"
lint_file "${TARGET_ADDONS_DIR}/shared/native_activity_performance.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/performance.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/performance_api.php"
lint_file "${TARGET_ADDONS_DIR}/radius/live.php"
lint_file "${TARGET_ADDONS_DIR}/radius/current_state.php"
lint_file "${TARGET_ADDONS_DIR}/radius/index.php"
lint_file "${TARGET_ADDONS_DIR}/radius/logs_data.php"
lint_file "${TARGET_ADDONS_DIR}/radius/client_links.php"
lint_file "${TARGET_ADDONS_DIR}/radius/client_status.php"
lint_file "${TARGET_ADDONS_DIR}/radius/preferences.php"
lint_file "${TARGET_ADDONS_DIR}/shared/client_update_audit.php"
lint_file "${TARGET_ADDONS_DIR}/shared/manual_block_audit.php"
lint_file "${TARGET_ADDONS_DIR}/shared/client_action_access.php"
lint_file "${TARGET_ADDONS_DIR}/shared/block_forecast.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_block_forecast.php"
lint_file "${TARGET_ADDONS_DIR}/shared/contract_attachment.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/contract_attachment.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/contract_archive.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/multi.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/multi_client_row.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/index.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/graphs.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/graphs_frame.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/delete_installation_request.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard/mkauth_dashboard_top.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/exibir_resultados.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/chamados_abertos.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/attendance_api.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/atendimentos.php"
lint_file "${TARGET_ADDONS_DIR}/shared/attendance_history.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/ticket_schedule_api.php"
lint_file "${TARGET_ADDONS_DIR}/shared/ticket_schedule.php"
lint_file "${TARGET_ADDONS_DIR}/shared/ticket_board.php"
lint_file "${TARGET_ADDONS_DIR}/shared/support_attendance.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/agenda_settings_api.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_support_api.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/priority_colors_api.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/visit_agenda_api.php"
lint_file "${TARGET_ADDONS_DIR}/shared/agenda_settings.php"
lint_file "${TARGET_ADDONS_DIR}/shared/client_support.php"
lint_file "${TARGET_ADDONS_DIR}/shared/priority_colors.php"
lint_file "${TARGET_ADDONS_DIR}/shared/ticket_notes.php"
lint_file "${TARGET_ADDONS_DIR}/shared/visit_windows.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/search_stats.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_connections.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_pdf.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_photo.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente/client_manual_block_audit.php"
lint_file "${TARGET_ADDONS_DIR}/dashboard-legado/index.php"
lint_file "${TARGET_ADDONS_DIR}/busca_inteligente-legado/index.php"

echo '[info] sincronizacao adicional existente preservada; nao instalar novos workers durante o diagnostico de desempenho.'
remove_manual_block_guard
install_reconcile
check_online_offline

bash "${SCRIPT_DIR}/scripts/install-payment-notifications.sh" "${TARGET_ADMIN_DIR}"
bash "${SCRIPT_DIR}/scripts/install-visit-reminders.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/visit-reminders"
bash "${SCRIPT_DIR}/scripts/install-support-attendance.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/support-attendance"
bash "${SCRIPT_DIR}/scripts/install-client-support.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/client-support"

php "${SCRIPT_DIR}/scripts/patch-payment-trigger.php" "${BACKUP_DIR}/payment-trigger"
bash "${SCRIPT_DIR}/scripts/install-receive-notice.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/receive-notice"
bash "${SCRIPT_DIR}/scripts/install-ipv6-dependency.sh" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/ipv6"

echo "[8/8] Finalizado"
echo "Backup salvo em: ${BACKUP_DIR}"
echo "Instalacao concluida em: ${TARGET_ADMIN_DIR}"

# Authenticated HTTP measurements: no invented operator, cookie or session.
mkdir -p /var/lib/mkauth-suite-performance
chmod 2770 /var/lib/mkauth-suite-performance
if getent group www-data >/dev/null 2>&1; then chgrp www-data /var/lib/mkauth-suite-performance; fi
if command -v python3 >/dev/null 2>&1; then
  python3 "${SCRIPT_DIR}/scripts/install-performance-apparmor.py" "${BACKUP_DIR}/apparmor" || echo '[aviso] AppArmor preservado; o diagnostico pode nao conseguir gravar arquivos privados.'
fi
touch /var/lib/mkauth-suite-performance/optimization.lock
chmod 0660 /var/lib/mkauth-suite-performance/optimization.lock
if getent group www-data >/dev/null 2>&1; then chgrp www-data /var/lib/mkauth-suite-performance/optimization.lock; fi
cp "${SCRIPT_DIR}/scripts/search-performance.py" /opt/mk-auth/scripts/mkauth-search-performance.py
chmod 0700 /opt/mk-auth/scripts/mkauth-search-performance.py
echo "[desempenho] Validando e otimizando consultas Online/Offline automaticamente"
if command -v php >/dev/null 2>&1; then
  php "${SCRIPT_DIR}/scripts/optimize-native-activity-install.php" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}" || echo '[aviso] Atividades nativas preservadas; consulte native-activity-performance.json no backup.'
  php "${SCRIPT_DIR}/scripts/optimize-search-install.php" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}" || echo '[aviso] Otimizacao nao concluida; consulte o relatorio de desempenho no backup.'
else
  echo '[aviso] PHP ausente; otimizacao automatica nao executada.'
fi
echo "Nova analise disponivel no botao Executar analise de desempenho da Dashboard e Busca."

if command -v python3 >/dev/null 2>&1; then
  MONITOR_UPDATER_DIR="$(dirname "${TARGET_ADMIN_DIR}")/scripts/mkauth-suite-monitor"
  mkdir -p "${MONITOR_UPDATER_DIR}"
  chmod 0700 "${MONITOR_UPDATER_DIR}"
  for monitor_file in install-map-monitor.py patch-map-traffic.py standalone-monitor-entry.php; do
    install -m 0600 "${SCRIPT_DIR}/scripts/${monitor_file}" "${MONITOR_UPDATER_DIR}/${monitor_file}"
  done
  python3 "${MONITOR_UPDATER_DIR}/install-map-monitor.py" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/monitor-package" || echo '[aviso] Monitor preservado ou indisponivel; confira o acesso ao GitHub e o log acima.'
  python3 "${SCRIPT_DIR}/scripts/patch-map-traffic.py" "${TARGET_ADMIN_DIR}" "${BACKUP_DIR}/map-traffic" || echo '[aviso] Grafico do Mapa preservado; patch nao aplicado. Consulte o log.'
fi
