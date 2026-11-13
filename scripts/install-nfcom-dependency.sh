#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
if [ "$admin" != /opt/mk-auth/admin ]; then
  echo '[nfcom] Destino alternativo: pacote oficial nao executado (destino fixo /opt/mk-auth/admin).'
  exit 0
fi
mkdir -p "$backup"
chmod 0700 "$backup"
url='https://raw.githubusercontent.com/brsxdlols/mkauth-gerador-nfcom/main/install-all.sh'
echo '[nfcom] Instalando/atualizando NFCOM, DICI e Relatorio de Trafego pelos instaladores oficiais.'
curl --fail --silent --show-error --location --retry 3 --connect-timeout 15 --max-time 120 "$url" -o "$backup/install-all.download"
tr -d '\r' < "$backup/install-all.download" > "$backup/install-all.sh"
chmod 0600 "$backup/install-all.sh"
sh -n "$backup/install-all.sh"
if ! sh "$backup/install-all.sh" > "$backup/install.log" 2>&1; then
  echo "[erro] Pacote NFCOM/DICI/Trafego nao concluido; consulte $backup/install.log. Os backups oficiais foram preservados." >&2
  exit 1
fi
echo "[nfcom] NFCOM, DICI e Relatorio de Trafego instalados. Log: $backup/install.log"
