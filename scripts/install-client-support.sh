#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
target="$admin/scripts/mk-auth.js"
if [ -f "$target" ] && ! grep -Fq 'mka-client-support-loader' "$target"; then
  mkdir -p "$backup";chmod 0700 "$backup";cp -a "$target" "$backup/mk-auth.js"
  printf '\n%s\n' ';(function(){if(window.top!==window||!/\/cliente_det\.(hhvm|php)$/.test(location.pathname))return;function load(){if(document.getElementById("mka-client-support-loader"))return;var s=document.createElement("script");s.id="mka-client-support-loader";s.src="/admin/addons/shared/client_support.js?v=20261015-9";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
fi
