#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
  echo "Execute como root." >&2
  exit 1
fi

LOG_FILE=/var/log/mkauth_radius_ppp_reconcile.log
HELPER=/opt/mk-auth/scripts/mkauth-radius-log-cleanup.sh
CRON_FILE=/etc/cron.d/mkauth-radius-log-cleanup
# Free space before writing the helper or cron, even on an already full disk.
[ ! -L "$LOG_FILE" ] || { echo "Erro: log e link simbolico." >&2; exit 1; }
if [ -f "$LOG_FILE" ]; then
  truncate -s 0 -- "$LOG_FILE"
fi
mkdir -p /opt/mk-auth/scripts

cat > "$HELPER" <<'SH'
#!/bin/sh
set -eu
LOG_FILE=/var/log/mkauth_radius_ppp_reconcile.log
# Never follow a symlink or touch anything other than this regular log file.
[ ! -L "$LOG_FILE" ] || exit 1
[ -f "$LOG_FILE" ] || exit 0
case "${1:-size}" in
  daily) truncate -s 0 -- "$LOG_FILE" ;;
  size)
    bytes=$(stat -c %s -- "$LOG_FILE")
    if [ "$bytes" -ge 52428800 ]; then
      truncate -s 0 -- "$LOG_FILE"
    fi
    ;;
  *) exit 2 ;;
esac
SH
chmod 0750 "$HELPER"
sh -n "$HELPER"

cat > "$CRON_FILE" <<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# Midnight in the server timezone; no archive copies consume disk space.
0 0 * * * root /opt/mk-auth/scripts/mkauth-radius-log-cleanup.sh daily
# Also protect against excessive growth within the same day.
*/5 * * * * root /opt/mk-auth/scripts/mkauth-radius-log-cleanup.sh size
CRON
chmod 0644 "$CRON_FILE"

# Release existing log space now, without removing the file or changing its inode.
"$HELPER" daily
echo "Log zerado (se existente): $LOG_FILE. Historico anterior descartado."
echo "Limpeza diaria: 00:00 no horario do servidor."
echo "Protecao adicional: verifica a cada 5 minutos e zera a partir de 50 MiB."
echo "Cron instalado: $CRON_FILE"
