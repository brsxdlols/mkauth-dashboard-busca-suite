#!/usr/bin/env bash
set -euo pipefail

# Compatibility entry point: updates must remove older persistent guards.
# Preserve audit history and current client states. Native MK-AUTH owns blocking.
backup="${1:-/opt/mk-auth/backups/manual-block-removal-$(date +%Y%m%d_%H%M%S)}"
mkdir -p "$backup"
mysqldump -uroot -pvertrigo --no-data --no-create-info --triggers mkradius sis_cliente > "$backup/triggers-before.sql"
test -s "$backup/triggers-before.sql"
for path in /etc/cron.d/mkauth-manual-block-enforcer /etc/cron.d/mkauth-manual-block-enforcer.disabled /opt/mk-auth/scripts/manual-block-enforcer.php; do
  if [ -f "$path" ]; then cp -a --parents "$path" "$backup/"; fi
done
mysql -uroot -pvertrigo mkradius <<'SQL'
DROP TRIGGER IF EXISTS tig_guard_dashboard_manual_block;
SQL

rm -f /etc/cron.d/mkauth-manual-block-enforcer /etc/cron.d/mkauth-manual-block-enforcer.disabled
rm -f /opt/mk-auth/scripts/manual-block-enforcer.php
echo "Protecao de bloqueio persistente removida; bloqueio gerenciado pelo MK-AUTH."
