#!/usr/bin/env bash
set -euo pipefail
admin="${1:-/opt/mk-auth/admin}"
backup="${2:?Informe o diretorio de backup}"
case "$admin" in /*/admin) ;; *) echo '[erro] Diretorio administrativo invalido.' >&2; exit 1;; esac
# Official release reviewed together with this suite. Update after validating a new release.
ref='65e625ef8bdd8a1047d85955b8fe2199aaad5199'
mkdir -p "$backup"
chmod 0700 "$backup"
php_bin=''
for candidate in php /usr/bin/php8.4 /usr/bin/php8.3 /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php8.0 /usr/bin/php7.4 /usr/bin/php7.3; do
  resolved="$(command -v "$candidate" 2>/dev/null || true)"
  [ -n "$resolved" ] || continue
  if "$resolved" -r 'exit(PHP_VERSION_ID >= 70300 ? 0 : 1);' >/dev/null 2>&1; then php_bin="$resolved"; break; fi
done
if [ -z "$php_bin" ]; then
  echo '[aviso] Caixa oficial requer PHP CLI 7.3+; Caixa existente preservado. Nenhum PHP do sistema foi alterado.'
  printf '%s\n' 'skipped: PHP CLI 7.3+ unavailable' > "$backup/install.log"
  exit 0
fi
# Select a compatible CLI only for this child process, without changing system alternatives.
mkdir -p "$backup/bin"
if [ ! -e "$backup/bin/php" ] && [ ! -L "$backup/bin/php" ]; then ln -s "$php_bin" "$backup/bin/php"; fi
url="https://raw.githubusercontent.com/brsxdlols/mkauth-relatorio-caixa/$ref/install.sh"
echo '[caixa] Instalando/atualizando pelo instalador oficial; configuracoes e modelos preservados.'
curl --fail --silent --show-error --location --retry 3 --connect-timeout 15 --max-time 120 "$url" -o "$backup/install.download"
tr -d '\r' < "$backup/install.download" > "$backup/install.sh"
chmod 0600 "$backup/install.sh"
sh -n "$backup/install.sh"
if ! PATH="$backup/bin:$PATH" sh "$backup/install.sh" --ref "$ref" --root "$(dirname "$admin")" --no-menu > "$backup/install.log" 2>&1; then
  echo "[erro] Atualizacao do Caixa falhou; consulte $backup/install.log" >&2
  exit 1
fi
for file in index.php config.php rollback.sh; do
  [ -f "$admin/addons/rel_caixa/$file" ] || { echo "[erro] Caixa incompleto: $file" >&2; exit 1; }
done
printf '%s\n' "$ref" > "$backup/source-commit"
echo "[caixa] Instalador oficial concluido. Log: $backup/install.log"
