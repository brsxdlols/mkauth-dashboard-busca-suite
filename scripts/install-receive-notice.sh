#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
target="$admin/scripts/mk-auth.js"
if [ ! -f "$target" ]; then
  echo '[aviso] scripts/mk-auth.js ausente; aviso de recebimento nao ajustado.'
  exit 0
fi
if ! grep -Fq 'mka-receive-notice' "$target"; then
  mkdir -p "$backup"
  chmod 0700 "$backup"
  cp -a "$target" "$backup/mk-auth.js"
  printf '\n%s\n' ';(function(){if(!/\/receber\.(hhvm|php)$/.test(location.pathname))return;function load(){if(document.getElementById("mka-receive-notice"))return;var s=document.createElement("script");s.id="mka-receive-notice";s.src="/admin/addons/shared/receive_notice.js?v=20261015-1";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
fi
echo '[receber] Aviso de calculo por dias bloqueados removido da exibicao; valores do titulo preservados.'
