#!/usr/bin/env python3
"""Set recognized native unblock jobs to one minute without inventing a cron."""
import argparse
import json
import os
from pathlib import Path
import re
import shlex
import shutil
import subprocess
import tempfile

FINANCIAL_JOBS = frozenset(('/opt/mk-auth/scripts/liberar.php', '/opt/mk-auth/scripts/desbloqueio.php'))
JOBS = FINANCIAL_JOBS | frozenset(('/opt/mk-auth/scripts/desbloq_obs.php',))


def native_job(command):
    try:
        words = shlex.split(command.decode('utf-8', errors='surrogateescape'))
    except (ValueError, UnicodeDecodeError):
        return None
    if not words:
        return None
    executable = words[0]
    if executable in JOBS:
        return executable
    if Path(executable).name == 'php':
        rest = words[1:]
        while rest and rest[0] in ('-q', '-f'):
            rest = rest[1:]
        if rest and rest[0] in JOBS:
            return rest[0]
    return None


def patch(data):
    lines, changes, recognized = [], [], []
    for number, line in enumerate(data.splitlines(keepends=True), 1):
        fields = line.split(None, 6)
        if not fields or fields[0].startswith(b'#') or len(fields) != 7:
            lines.append(line)
            continue
        job = native_job(fields[6].rstrip(b'\r\n'))
        if job is None:
            lines.append(line)
            continue
        if fields[5] != b'root':
            raise RuntimeError('Usuario personalizado no cron nativo; arquivo preservado.')
        minute = fields[0]
        if minute not in (b'*', b'*/1'):
            interval = re.fullmatch(rb'\*/([1-9][0-9]?)', minute)
            if not interval or int(interval.group(1)) > 59:
                raise RuntimeError('Agendamento personalizado na linha '+str(number)+'; arquivo preservado.')
            match = re.match(rb'^([ \t]*)(\S+)', line)
            line = line[:match.start(2)] + b'*/1' + line[match.end(2):]
            changes.append({'line': number, 'job': job, 'before': minute.decode('ascii'), 'after': '*/1'})
        recognized.append(job)
        lines.append(line)
    if not recognized:
        raise RuntimeError('Nenhuma chamada nativa liberar.php/desbloqueio.php/desbloq_obs.php reconhecida; arquivo preservado.')
    if not FINANCIAL_JOBS.intersection(recognized):
        raise RuntimeError('Rotina financeira liberar.php/desbloqueio.php ausente neste arquivo; agendamento preservado.')
    return b''.join(lines), changes


def atomic(path, data, metadata):
    descriptor, name = tempfile.mkstemp(prefix='.desbloqueio-', dir=str(path.parent))
    try:
        with os.fdopen(descriptor, 'wb') as output:
            output.write(data)
            output.flush()
            os.fsync(output.fileno())
        os.chown(name, metadata.st_uid, metadata.st_gid)
        os.chmod(name, metadata.st_mode & 0o7777)
        os.replace(name, path)
    finally:
        if os.path.exists(name):
            os.unlink(name)


def restart_cron():
    service = shutil.which('service')
    command = [service, 'cron', 'restart'] if service else ['systemctl', 'restart', 'cron']
    result = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    if result.returncode:
        raise RuntimeError('Falha ao reiniciar cron: '+result.stdout.decode('utf-8', errors='replace').strip())


def apply(backup, cron_file=Path('/etc/cron.d/desbloqueio')):
    backup, cron_file = Path(backup), Path(cron_file)
    if cron_file.is_symlink() or any(p.is_symlink() for p in cron_file.parents):
        raise RuntimeError('Cron simbolico preservado.')
    if not cron_file.exists():
        print('[rotinas] Cron nativo /etc/cron.d/desbloqueio ausente; nenhum arquivo foi criado.')
        return 'absent'
    if not cron_file.is_file():
        raise RuntimeError('Caminho do cron nao e arquivo regular.')
    before = cron_file.read_bytes()
    patched, changes = patch(before)
    if not changes:
        print('[rotinas] Desbloqueio financeiro nativo ja agendado a cada 1 minuto.')
        return 'unchanged'
    if backup.is_symlink() or any(p.is_symlink() for p in backup.parents):
        raise RuntimeError('Diretorio de backup simbolico recusado.')
    backup.mkdir(parents=True, exist_ok=True)
    backup.chmod(0o700)
    saved = backup/'desbloqueio.before'
    descriptor = os.open(str(saved), os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(descriptor, 'wb') as output:
        output.write(before)
    metadata = cron_file.stat()
    report = {'path': str(cron_file), 'changes': changes, 'uid': metadata.st_uid, 'gid': metadata.st_gid, 'mode': metadata.st_mode & 0o7777, 'completed': False}
    try:
        if cron_file.read_bytes() != before:
            raise RuntimeError('Cron alterado durante a validacao; nenhuma substituicao aplicada.')
        atomic(cron_file, patched, metadata)
        try:
            restart_cron()
        except Exception:
            atomic(cron_file, before, metadata)
            raise
        report['completed'] = True
    finally:
        record = backup/'cron.json'
        record.write_text(json.dumps(report, indent=2), encoding='utf-8')
        record.chmod(0o600)
    print('[rotinas] Desbloqueio financeiro e observacao ajustados para 1 minuto; cron reiniciado. Backup: '+str(saved))
    return 'changed'


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('backup', type=Path)
    args = parser.parse_args()
    try:
        if os.geteuid() != 0:
            raise RuntimeError('Execute como root.')
        apply(args.backup)
    except Exception as error:
        print('[aviso] Agendamento de desbloqueio nao confirmado: '+str(error))
        raise SystemExit(1)
