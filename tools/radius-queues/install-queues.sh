#!/usr/bin/env bash
set -euo pipefail
umask 077
repo='brsxdlols/mkauth-dashboard-busca-suite'
prefix='/opt/mk-auth/tools/radius-queues'
bin_dir='/usr/local/sbin'
while (($#)); do
  case "$1" in
    --prefix) prefix="$2"; shift 2 ;;
    --bin-dir) bin_dir="$2"; shift 2 ;;
    *) printf 'Opcao desconhecida: %s\n' "$1" >&2; exit 2 ;;
  esac
done
[[ "$prefix" = /* && "$bin_dir" = /* ]] || { echo 'Use caminhos absolutos.' >&2; exit 2; }
for cmd in curl python3 php ssh sshpass ssh-keyscan ssh-keygen radclient; do
  command -v "$cmd" >/dev/null || { printf 'Dependencia ausente: %s. Instale-a antes de continuar.\n' "$cmd" >&2; exit 1; }
done
php -r 'exit(PHP_VERSION_ID >= 70100 && extension_loaded("mysqli") ? 0 : 1);' || { echo 'PHP CLI 7.1+ com mysqli necessario.' >&2; exit 1; }
ref="${MKA_QUEUES_REF:-}"
if [[ -z "$ref" ]]; then
  ref="$(curl -fsSL --connect-timeout 10 --max-time 45 "https://api.github.com/repos/$repo/git/ref/heads/main" | python3 -c 'import json,sys;print(json.load(sys.stdin)["object"]["sha"])')"
fi
[[ "$ref" =~ ^[a-f0-9]{40}$ ]] || { echo 'Commit de origem invalido.' >&2; exit 1; }
stage="$(mktemp -d)"
trap 'rm -rf -- "$stage"' EXIT
for name in sincronizar-queues.py queues-db.php test-sincronizar-queues.py README.md; do
  curl -fsSL --connect-timeout 10 --max-time 60 "https://raw.githubusercontent.com/$repo/$ref/tools/radius-queues/$name" -o "$stage/$name"
done
php -l "$stage/queues-db.php" >/dev/null
python3 -m py_compile "$stage/sincronizar-queues.py"
python3 "$stage/test-sincronizar-queues.py"
mkdir -p -- "$prefix/releases" "$bin_dir"
release="$prefix/releases/$ref"
if [[ -d "$release" ]]; then
  for name in sincronizar-queues.py queues-db.php test-sincronizar-queues.py README.md; do
    cmp "$stage/$name" "$release/$name" >/dev/null || { echo 'Versao existente difere; instalacao abortada.' >&2; exit 1; }
  done
else
  mkdir -- "$release"
  for name in sincronizar-queues.py queues-db.php test-sincronizar-queues.py README.md; do install -m 600 "$stage/$name" "$release/$name"; done
fi
if [[ -e "$prefix/current" && ! -L "$prefix/current" ]]; then echo 'current existe e nao e link; preservado.' >&2; exit 1; fi
launcher="$bin_dir/mkauth-queues"
if [[ -e "$launcher" ]] && ! grep -q 'MKAUTH-QUEUES-LAUNCHER' "$launcher"; then echo 'Comando existente preservado; escolha outro --bin-dir.' >&2; exit 1; fi
if [[ -L "$prefix/current" ]]; then readlink "$prefix/current" > "$prefix/previous-release.txt"; fi
ln -s "$release" "$prefix/.current-$$"
mv -Tf -- "$prefix/.current-$$" "$prefix/current"
printf '#!/bin/sh\n# MKAUTH-QUEUES-LAUNCHER\nexec python3 "%s/current/sincronizar-queues.py" "$@"\n' "$prefix" > "$stage/launcher"
install -m 755 "$stage/launcher" "$launcher"
printf 'Instalado: %s\nCommit: %s\nAuditar: %s\nAplicar apos revisar: %s --apply\n' "$prefix" "$ref" "$launcher" "$launcher"
echo 'Nao foram alterados clientes, RADIUS ou roteadores. Nenhum cron foi criado.'
echo 'Hosts SSH novos precisam de chave conhecida ou --host-key IP=SHA256:...; veja README.'
