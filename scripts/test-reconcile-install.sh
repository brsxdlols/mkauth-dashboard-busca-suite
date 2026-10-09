#!/bin/sh
# Isolated tests of the early installer check. Never install, connect or alter accounting.
set -eu
dir=$(mktemp -d)
trap 'rm -rf -- "$dir"' EXIT HUP INT TERM
source_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
awk '/# Install log protection/{exit} {print}' "$source_dir/install-radius-reconcile.sh" |
  sed "s|manifest=/var/lib/mkauth_radius_ppp_reconcile/installed.sha256|manifest=$dir/installed.sha256|; s|/etc/cron.d/sistel-radius-ppp-reconcile|$dir/legacy-cron|" > "$dir/check.sh"
printf '\nexit 42\n' >> "$dir/check.sh"
run_expect() {
  expected=$1; label=$2
  actual=0
  sh "$dir/check.sh" > "$dir/output" 2>&1 || actual=$?
  if [ "$actual" != "$expected" ]; then cat "$dir/output"; echo "FAIL $label: $actual != $expected"; exit 1; fi
  echo "PASS $label"
}
run_expect 42 fresh-install
printf 'fixture\n' > "$dir/artifact"
sha256sum "$dir/artifact" > "$dir/installed.sha256"
sha256sum "$dir/check.sh" | cut -d ' ' -f 1 > "$dir/installed.sha256.installer"
# Same normalized configuration expression, without running the installer body.
awk '/^configuration_id=/{print; print "printf '\''%s\\n'\'' \"$configuration_id\""; exit}' "$dir/check.sh" > "$dir/config.sh"
sh "$dir/config.sh" > "$dir/installed.sha256.configuration"
run_expect 0 already-applied
run_expect 0 repeated-check
API_PORT=12345; export API_PORT
run_expect 42 changed-configuration
unset API_PORT
printf 'changed\n' >> "$dir/artifact"
run_expect 42 changed-installed-file
sha256sum "$dir/artifact" > "$dir/installed.sha256"
touch "$dir/legacy-cron"
run_expect 42 legacy-cron-present
rm "$dir/legacy-cron"
printf '\n# version changed\n' >> "$dir/check.sh"
run_expect 42 changed-installer
