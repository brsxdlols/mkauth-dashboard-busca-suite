#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
script_dir="$(cd "$(dirname "$0")" && pwd)"
php "$script_dir/install-support-attendance.php"
target="$admin/scripts/mk-auth.js"
if [ -f "$target" ] && ! grep -Fq 'mka-support-attendance-prefill' "$target"; then
  mkdir -p "$backup"
  chmod 0700 "$backup"
  cp -a "$target" "$backup/mk-auth.js"
  printf '\n%s\n' ';(function(){if(!/\/suporte_ins\.(hhvm|php)$/.test(location.pathname)||!new URLSearchParams(location.search).has("mka_atendimento"))return;function load(){if(document.getElementById("mka-support-attendance-prefill"))return;var s=document.createElement("script");s.id="mka-support-attendance-prefill";s.src="/admin/addons/shared/support_attendance_prefill.js?v=20261015-1";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
fi
if [ -f "$target" ] && grep -Fq 'support_attendance_prefill.js?v=20261015-1' "$target"; then
  mkdir -p "$backup";chmod 0700 "$backup";[ -f "$backup/mk-auth.js" ] || cp -a "$target" "$backup/mk-auth.js"
  sed -i 's@support_attendance_prefill.js?v=20261015-1@support_attendance_prefill.js?v=20261015-9@g' "$target"
fi
