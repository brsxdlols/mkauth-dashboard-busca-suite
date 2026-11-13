#!/usr/bin/env python3
"""Install housekeeping files with a reversible snapshot; never run deletions here."""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys

FILES = ('usr/local/lib/mkauth-suite/cleanup.py', 'usr/local/sbin/mkauth-limpeza-diaria.sh',
         'etc/cron.d/mkauth-limpeza', 'etc/logrotate.d/mkauth-suite-cleanup',
         'etc/mkauth-suite-cleanup.json')


def target(root, name):
    result = root/name
    for path in [result]+list(result.parents):
        if path.is_symlink():
            raise RuntimeError('Link recusado: '+str(path))
        if path == root:
            break
    return result


def put(path, content, mode):
    path.parent.mkdir(parents=True, exist_ok=True)
    stage = path.with_name(path.name+'.suite-new')
    if stage.exists() or stage.is_symlink():
        raise RuntimeError('Arquivo temporario existente: '+str(stage))
    descriptor = os.open(str(stage), os.O_CREAT | os.O_EXCL | os.O_WRONLY, mode)
    with os.fdopen(descriptor, 'wb') as stream:
        stream.write(content)
    os.chmod(str(stage), mode)
    os.replace(str(stage), str(path))


def restore(root, backup):
    records = json.loads((backup/'manifest.json').read_text())
    for record in records:
        if record['path'] not in FILES:
            raise RuntimeError('Manifesto invalido')
        path = target(root, record['path'])
        if record['exists']:
            put(path, (backup/record['path']).read_bytes(), record['mode'])
            os.chown(str(path), record['uid'], record['gid'])
        elif path.exists():
            path.unlink()


def install(root, backup):
    worker = Path(__file__).with_name('cleanup-worker.py').read_bytes()
    compile(worker, 'cleanup-worker.py', 'exec')
    if backup.exists():
        raise RuntimeError('Diretorio de backup ja existe')
    records = []
    for name in FILES:
        path = target(root, name)
        record = {'path': name, 'exists': path.exists()}
        if path.exists():
            if not path.is_file():
                raise RuntimeError('Destino nao e arquivo: '+str(path))
            info = path.stat()
            record.update(mode=info.st_mode & 0o777, uid=info.st_uid, gid=info.st_gid)
        records.append(record)
    backup.mkdir(parents=True, mode=0o700)
    for record in records:
        if record['exists']:
            copy = backup/record['path']
            copy.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(str(root/record['path']), str(copy))
    (backup/'manifest.json').write_text(json.dumps(records))
    config = target(root, FILES[4])
    try:
        put(target(root, FILES[0]), worker, 0o700)
        wrapper = b'''#!/bin/sh
set -eu
umask 027
exec /usr/bin/python3 /usr/local/lib/mkauth-suite/cleanup.py --apply >> /var/log/mkauth-suite-cleanup.log 2>&1
'''
        put(target(root, FILES[1]), wrapper, 0o755)
        # Do not overlap syslog/Apache/RADIUS rotation owned by the OS or other addons.
        rotate = b'''/var/log/mkauth-suite-cleanup.log {
    daily
    rotate 7
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
}
'''
        put(target(root, FILES[3]), rotate, 0o644)
        if not config.exists():
            put(config, b'{"keep_backups":15,"temporary_days":7,"docker":true}\n', 0o600)
        if root == Path('/'):
            if Path('/usr/sbin/logrotate').is_file():
                subprocess.run(['/usr/sbin/logrotate', '-d', str(target(root, FILES[3]))],
                               check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
            # Simulation only; the installer never deletes customer/system data.
            result = subprocess.run([sys.executable, str(target(root, FILES[0]))],
                                    stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True, timeout=60)
            (backup/'initial-report.json').write_text(result.stdout)
            if result.returncode:
                raise RuntimeError('Simulacao falhou; consulte '+str(backup/'initial-report.json'))
        # Activate the schedule only after validation and the non-destructive report.
        put(target(root, FILES[2]), b'30 7 * * * root /usr/local/sbin/mkauth-limpeza-diaria.sh\n', 0o644)
    except Exception:
        restore(root, backup)
        raise
    print('[limpeza] Rotina instalada: diariamente as 07:30; primeira execucao apenas simulada. Backup: '+str(backup))


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('admin')
    parser.add_argument('backup')
    parser.add_argument('--root', default='/')
    parser.add_argument('--restore', action='store_true')
    args = parser.parse_args()
    if args.admin != '/opt/mk-auth/admin':
        print('[limpeza] Destino alternativo; agendamento de sistema nao instalado.')
        sys.exit(0)
    if os.geteuid() != 0:
        raise SystemExit('Execute como root')
    root, backup = Path(os.path.abspath(args.root)), Path(os.path.abspath(args.backup))
    if args.restore:
        restore(root, backup)
    else:
        install(root, backup)
