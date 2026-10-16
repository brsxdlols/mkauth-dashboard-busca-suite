#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
script_dir="$(cd "$(dirname "$0")" && pwd)"
asset="$script_dir/../addons/shared/receive_notice.js"
if [ ! -f "$asset" ]; then echo '[erro] receive_notice.js ausente do pacote.' >&2; exit 1; fi
mkdir -p "$backup" "$admin/addons/shared"
chmod 0700 "$backup"
if [ -f "$admin/addons/shared/receive_notice.js" ]; then cp -an "$admin/addons/shared/receive_notice.js" "$backup/receive_notice.js"; fi
if ! cmp -s "$asset" "$admin/addons/shared/receive_notice.js"; then install -m 644 "$asset" "$admin/addons/shared/receive_notice.js"; fi
installed=0
for name in mk-auth.js jquery.js; do
 target="$admin/scripts/$name"
 [ -f "$target" ] || continue
 if ! grep -Fq 'mka-receive-notice' "$target"; then
  cp -an "$target" "$backup/$name"
  printf '\n%s\n' ';(function(){if(!/\/receber\.(hhvm|php)$/.test(location.pathname))return;function load(){if(document.getElementById("mka-receive-notice"))return;var s=document.createElement("script");s.id="mka-receive-notice";s.src="/admin/addons/shared/receive_notice.js?v=20261016-1";document.head.appendChild(s);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",load);else load();})();' >> "$target"
 elif grep -Fq 'receive_notice.js?v=20261015-1' "$target"; then
  cp -an "$target" "$backup/$name"
  sed -i 's@receive_notice.js?v=20261015-1@receive_notice.js?v=20261016-1@g' "$target"
 fi
 installed=$((installed+1))
done
if [ "$installed" -eq 0 ]; then echo '[erro] Nenhum carregador nativo disponivel.' >&2; exit 1; fi
echo '[receber] Tarja e informacao de dias bloqueados removidas da exibicao; valores do titulo preservados.'
