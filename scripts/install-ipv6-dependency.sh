#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
if [ "$admin" != /opt/mk-auth/admin ]; then
  echo '[ipv6] Destino alternativo: instalador oficial nao executado (destino fixo /opt/mk-auth/admin).'
  exit 0
fi
mkdir -p "$backup"
chmod 0700 "$backup"
url='https://raw.githubusercontent.com/brsxdlols/mkauth-ipv6-addon/main/installers/install-mkauth.sh'
echo '[ipv6] Instalando/atualizando o painel pelo instalador oficial.'
curl --fail --silent --show-error --location --retry 3 --connect-timeout 15 --max-time 120 "$url" -o "$backup/install-mkauth.download"
tr -d '\r' < "$backup/install-mkauth.download" > "$backup/install-mkauth.sh"
chmod 0600 "$backup/install-mkauth.sh"
sh -n "$backup/install-mkauth.sh"
sh "$backup/install-mkauth.sh" > "$backup/install.log" 2>&1 || {
  echo "[erro] Atualizacao IPv6 falhou; consulte $backup/install.log" >&2
  exit 1
}
echo "[ipv6] Atualizacao concluida. Log: $backup/install.log"
