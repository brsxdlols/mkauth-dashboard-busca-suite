#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
script_dir="$(cd "$(dirname "$0")" && pwd)"
php "$script_dir/install-visit-reminders.php"
target="$admin/scripts/mk-auth.js"
if [ -f "$target" ] && ! grep -Fq 'mka-visit-reminders-loader' "$target"; then
  mkdir -p "$backup";chmod 0700 "$backup";cp -a "$target" "$backup/mk-auth.js"
  printf '\n%s\n' ';(function(){function load(){if(window.top!==window||document.getElementById("mka-visit-reminders-loader"))return;var s=document.createElement("script");s.id="mka-visit-reminders-loader";s.src="/admin/addons/shared/visit_reminders.js?v=20261015-1";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
fi
