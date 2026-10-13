#!/usr/bin/env bash
set -euo pipefail

# Compatibility entry point: updates must remove older persistent guards.
# Preserve audit history and current client states. Native MK-AUTH owns blocking.
mysql -uroot -pvertrigo mkradius <<'SQL'
DROP TRIGGER IF EXISTS tig_guard_dashboard_manual_block;
SQL

rm -f /etc/cron.d/mkauth-manual-block-enforcer /etc/cron.d/mkauth-manual-block-enforcer.disabled
rm -f /opt/mk-auth/scripts/manual-block-enforcer.php
echo "Protecao de bloqueio persistente removida; bloqueio gerenciado pelo MK-AUTH."
