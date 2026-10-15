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
curl --fail --silent --show-error --location --retry 3 --connect-timeout 15 --max-time 120 "$url" -o "$backup/github-install.download"
tr -d '\r' < "$backup/github-install.download" > "$backup/github-install.sh"
chmod 0600 "$backup/github-install.sh"
sh -n "$backup/github-install.sh"
# The official package replaces radius/. Run it BEFORE the suite modal overlay.
MKAUTH_ROOT="$(dirname "$admin")" MKAUTH_ADMIN="$admin" MKAUTH_BACKUP_ROOT="$backup" \
  sh "$backup/github-install.sh" > "$backup/install.log" 2>&1 || {
    echo "[erro] Atualizacao Radius falhou; consulte $backup/install.log" >&2
    exit 1
  }
for file in index.php logs_data.php radius_lib.php client_target.php radius.css; do
  if [ ! -f "$admin/addons/radius/$file" ]; then
    echo "[erro] Pacote Radius incompleto: $file ausente. Consulte $backup/install.log" >&2
    exit 1
  fi
done
echo "[radius] Pacote completo instalado. Log: $backup/install.log"
