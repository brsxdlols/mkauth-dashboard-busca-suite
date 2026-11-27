#!/bin/sh
set -eu
BACKUP=${1:?Informe o diretorio de backup}
[ "$(id -u)" -eq 0 ] || { echo '[erro] Contratos requer root.'; exit 1; }
mkdir -p "$BACKUP"
chmod 0700 "$BACKUP"
BOOTSTRAP="$BACKUP/github-install.sh"
URL='https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh'
echo '[contratos] Instalando/atualizando pelo instalador oficial; modelos e documentos existentes serao preservados.'
if command -v curl >/dev/null 2>&1; then
 curl -fsSL --retry 3 --connect-timeout 15 "$URL" -o "$BOOTSTRAP"
else
 wget -qO "$BOOTSTRAP" "$URL"
fi
chmod 0600 "$BOOTSTRAP"
sh -n "$BOOTSTRAP"
if CONTRATOS_REF= sh "$BOOTSTRAP" > "$BACKUP/install.log" 2>&1; then
 chmod 0600 "$BACKUP/install.log"
 cat "$BACKUP/install.log"
 echo '[contratos] Addon oficial atualizado; modelos e documentos anteriores preservados.'
else
 status=$?
 chmod 0600 "$BACKUP/install.log"
 tail -n 80 "$BACKUP/install.log"
 echo "[erro] Addon de contratos nao concluido. Consulte $BACKUP/install.log"
 exit "$status"
fi
