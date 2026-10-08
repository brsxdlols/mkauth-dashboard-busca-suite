#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
script_dir="$(cd "$(dirname "$0")" && pwd)"
# Additive migration: no historical backfill and no changes to invoices.
mysql -uroot -pvertrigo mkradius < "$script_dir/payment-events.sql"
target="$admin/scripts/mk-auth.js"
if [ -f "$target" ] && ! grep -q 'mka-payment-notifications' "$target"; then
 cp -p "$target" "$target.before-payment-notifications-$(date +%Y%m%d%H%M%S)"
 printf '\n%s\n' ';(function(){function load(){if(document.getElementById("mka-payment-notifications"))return;var s=document.createElement("script");s.id="mka-payment-notifications";s.src="/admin/addons/shared/payment_notifications.js";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
fi
if [ -f "$target" ]; then
 sed -i -E 's@/admin/addons/shared/payment_notifications\.js(\?v=[A-Za-z0-9_-]+)?@/admin/addons/shared/payment_notifications.js?v=20261007b@g' "$target"
fi
install -m 0700 "$script_dir/test-payment-notification.php" /root/test-payment-notification.php
