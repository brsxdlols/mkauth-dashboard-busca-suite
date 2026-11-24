#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
case "$admin" in
  /*/admin) ;;
  *) echo '[erro] O instalador Radius requer um caminho absoluto terminado em /admin.' >&2; exit 1 ;;
esac
mkdir -p "$backup"
chmod 0700 "$backup"
url='https://raw.githubusercontent.com/brsxdlols/mkauth-radius-logs/main/installers/github-install.sh'
echo '[radius] Instalando/atualizando o pacote completo pelo instalador oficial.'
curl --fail --silent --show-error --location --retry 3 --connect-timeout 15 --max-time 120 -H 'Cache-Control: no-cache' "$url?cache=$(date +%s)" -o "$backup/github-install.download"
tr -d '\r' < "$backup/github-install.download" > "$backup/github-install.sh"
chmod 0600 "$backup/github-install.sh"
sh -n "$backup/github-install.sh"
# The official package owns all Radius runtime files and its independent shortcut.
MKAUTH_ROOT="$(dirname "$admin")" MKAUTH_ADMIN="$admin" MKAUTH_BACKUP_ROOT="$backup" \
  sh "$backup/github-install.sh" > "$backup/install.log" 2>&1 || {
    echo "[erro] Atualizacao Radius falhou; consulte $backup/install.log" >&2
    exit 1
  }
for file in index.php logs_data.php radius_lib.php client_target.php radius.css access.php standalone_access.php live.php history.php current_state.php preferences.php connection_settings_api.php connection_settings_model.php client_links.php client_status.php client_status.js radius_log_reader.php assets/connection_settings.js assets/connection_settings.css assets/radius_preferences.js package_features.json; do
  if [ ! -f "$admin/addons/radius/$file" ]; then
    echo "[erro] Pacote Radius incompleto: $file ausente. Consulte $backup/install.log" >&2
    exit 1
  fi
done
python3 - "$admin/addons/radius/package_features.json" <<'PY'
import json, sys
with open(sys.argv[1]) as source: features = json.load(source)
required = ('complete_standalone', 'provider_shortcut', 'current_state', 'preferences', 'connection_tools', 'accounting_read_only')
if any(features.get(key) is not True for key in required):
    raise SystemExit('[erro] O pacote oficial Radius ainda nao atende ao contrato completo; nenhuma copia da suite sera aplicada.')
print('[radius] Contrato independente validado: ' + str(features.get('version', '?')))
PY
echo "[radius] Pacote completo instalado. Log: $backup/install.log"

