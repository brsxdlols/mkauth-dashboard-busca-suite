#!/usr/bin/env bash
set -euo pipefail
ADMIN="$1"
BACKUP="$2"
SOURCE="$(cd "$(dirname "$0")" && pwd)"
mkdir -p "$BACKUP" /opt/mk-auth/scripts
chmod 0700 "$BACKUP"
for name in server-timezone-worker.py server-timezone-db.php installer-database.php; do
  if [ -f "/opt/mk-auth/scripts/$name" ]; then cp -a "/opt/mk-auth/scripts/$name" "$BACKUP/$name.before"; fi
  install -m 0700 "$SOURCE/$name" "/opt/mk-auth/scripts/$name"
done
cp "$ADMIN/addons/shared/server_timezones.json" /opt/mk-auth/scripts/server_timezones.json
chmod 0600 /opt/mk-auth/scripts/server_timezones.json
if [ -f /etc/cron.d/mkauth-server-timezone ]; then cp -a /etc/cron.d/mkauth-server-timezone "$BACKUP/cron.before"; fi
printf '%s\n' 'SHELL=/bin/sh' 'PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin' '* * * * * root /usr/bin/python3 /opt/mk-auth/scripts/server-timezone-worker.py >/dev/null 2>&1' > /etc/cron.d/mkauth-server-timezone
chmod 0644 /etc/cron.d/mkauth-server-timezone
# Detection is automatic; system changes start only after an explicit save.
python3 /opt/mk-auth/scripts/server-timezone-worker.py --check
