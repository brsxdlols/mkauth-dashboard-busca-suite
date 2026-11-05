#!/usr/bin/env python3
"""Provision missing reconciliation artifacts; never execute accounting at installation."""
import argparse
import json
from pathlib import Path
import re
import shutil
import subprocess
import sys

HERE = Path(__file__).resolve().parent
WORKER = 'mkauth_radius_ppp_reconcile.php'
GUARD = 'mkauth_radius_offline_guard.php'


def active_cron(text, worker):
    for line in text.splitlines():
        parts = line.strip().split(None, 6)
        if len(parts) == 7 and not line.lstrip().startswith('#') and parts[5] == 'root':
            schedule = all(re.fullmatch(r'[0-9*/,-]+', field) for field in parts[:5])
            php_call = re.search(r'(?:^|\s)(?:/[^\s]+/)?php(?:[0-9.]*)?\s', parts[6])
            if schedule and php_call and re.search(r'(?:^|[\s"\'])' + re.escape(str(worker)) + r'(?:[\s"\']|$)', parts[6]) and '--apply' in parts[6]:
                return True
    return False


def ensure(scripts, backup, cron_dir, state, admin):
    worker = scripts / WORKER
    guard = scripts / GUARD
    cron = cron_dir / 'mkauth-radius-ppp-reconcile'
    status = admin / 'addons/dashboard/radius_status.php'
    maintenance = scripts / 'mkauth-radacct-maintenance.sh'
    for path in (scripts, backup, cron_dir, state, admin, worker, guard, cron, status, maintenance):
        if path.is_symlink() or any(parent.is_symlink() for parent in path.parents):
            raise RuntimeError('Caminho simbolico recusado: ' + str(path))
    if (cron_dir / 'sistel-radius-ppp-reconcile').exists():
        raise RuntimeError('Cron legado encontrado; revisar para evitar dois reconciliadores.')
    # Never silently overwrite an existing custom/disabled cron or report it as healthy.
    if cron.exists() and not active_cron(cron.read_text(), worker):
        raise RuntimeError('Cron existente sem chamada ativa ao reconciliador; preservado para revisao.')
    backup.mkdir(parents=True, exist_ok=True)
    backup.chmod(0o700)
    artifacts = (worker, guard, cron, status, maintenance)
    metadata = []
    for i, path in enumerate(artifacts):
        row = {'path': str(path), 'existed': path.exists()}
        if path.exists():
            saved = backup / ('before-' + str(i) + '-' + path.name)
            shutil.copy2(str(path), str(saved))
            row['backup'] = str(saved)
            row['mode'] = path.stat().st_mode & 0o7777
            row['uid'], row['gid'] = getattr(path.stat(), 'st_uid', 0), getattr(path.stat(), 'st_gid', 0)
        metadata.append(row)
    manifest = backup / 'artifacts-before.json'
    manifest.write_text(json.dumps(metadata, indent=2))
    manifest.chmod(0o600)
    source = (HERE / 'install-radius-reconcile.sh').read_text(encoding='utf-8')

    def template(marker):
        match = re.search(r"<<'" + marker + r"'\n(.*?)\n" + marker + r"\n", source, re.S)
        if not match:
            raise RuntimeError('Modelo ausente: ' + marker)
        return match.group(1) + '\n'

    def install(path, content, mode):
        staged = backup / (path.name + '.checked')
        staged.write_text(content, encoding='utf-8')
        if path.suffix == '.php':
            subprocess.run(['php', '-l', str(staged)], check=True)
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(staged.read_bytes())
        path.chmod(mode)

    if worker.exists():
        subprocess.run([sys.executable, str(HERE / 'upgrade-offline-confirmation.py'), str(scripts), str(backup / 'upgrade')], check=True)
    else:
        # Stage and lint both before writing either runtime file.
        for name, content in ((WORKER, template('PHP')), (GUARD, template('GUARD_PHP'))):
            staged = backup / (name + '.staged.php')
            staged.write_text(content, encoding='utf-8')
            subprocess.run(['php', '-l', str(staged)], check=True)
        install(guard, template('GUARD_PHP'), 0o600)
        install(worker, template('PHP'), 0o700)
    state.mkdir(parents=True, exist_ok=True)
    state.chmod(0o755)
    if not cron.exists():
        install(cron, 'SHELL=/bin/sh\nPATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\n'
                + '*/2 * * * * root /usr/bin/php "' + str(worker) + '" --apply >/dev/null 2>&1\n', 0o600)
    # The source has two PHP heredocs: runtime first, status endpoint second.
    if not status.exists():
        blocks = re.findall(r"<<'PHP'\n(.*?)\nPHP\n", source, re.S)
        install(status, blocks[1] + '\n', 0o644)
    install(maintenance, (HERE / 'radacct-maintenance.sh').read_text(encoding='utf-8'), 0o700)
    for path in (worker, guard, status):
        if not path.is_file():
            raise RuntimeError('Artefato ausente: ' + str(path))
        subprocess.run(['php', '-l', str(path)], check=True)
    if not active_cron(cron.read_text(), worker):
        raise RuntimeError('Agendamento nao confirmado.')
    print('[reconcile] OK: worker, guarda e cron ativos conferidos. Agendamento existente preservado ou criado a cada 2 minutos; nenhuma sessao alterada pelo instalador.')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('scripts', type=Path)
    parser.add_argument('backup', type=Path)
    parser.add_argument('--cron-dir', type=Path, default=Path('/etc/cron.d'))
    parser.add_argument('--state', type=Path, default=Path('/var/lib/mkauth_radius_ppp_reconcile'))
    parser.add_argument('--admin', type=Path, default=Path('/opt/mk-auth/admin'))
    args = parser.parse_args()
    try:
        ensure(args.scripts, args.backup, args.cron_dir, args.state, args.admin)
    except (RuntimeError, OSError, subprocess.CalledProcessError) as error:
        print('[erro] Reconciliador nao confirmado: ' + str(error), file=sys.stderr)
        sys.exit(1)
