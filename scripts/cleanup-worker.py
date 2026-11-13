#!/usr/bin/env python3
"""Bounded MK-AUTH housekeeping. Default is a report; --apply enables cleanup."""
import argparse
import datetime
import fcntl
import json
import os
from pathlib import Path
import sqlite3
import stat
import subprocess
import time


def safe(root, relative):
    path = root / relative
    current = root
    if root.is_symlink():
        raise RuntimeError('Raiz simbolica recusada')
    for part in Path(relative).parts:
        if part in ('..', '.'):
            raise RuntimeError('Caminho invalido')
        current = current / part
        if current.is_symlink():
            raise RuntimeError('Link recusado: '+str(current))
    return path


def old_file(path, cutoff):
    try:
        info = path.lstat()
        return stat.S_ISREG(info.st_mode) and info.st_mtime < cutoff and info.st_atime < cutoff
    except FileNotFoundError:
        return False


def temporary_files(root, days, apply, report):
    cutoff = time.time() - days * 86400
    opened = set()
    # Preserve even old files currently held open (including noatime mounts).
    for process in Path('/proc').glob('[0-9]*'):
        try:
            for fd in (process/'fd').iterdir():
                try:
                    info = fd.stat()
                    opened.add((info.st_dev, info.st_ino))
                except OSError:
                    pass
        except OSError:
            pass
    candidates = []
    # Never sweep /var/tmp PHP sessions or unrelated system/application files.
    for name in ('opt/mk-auth/tmp_bot', 'opt/mk-auth/tmp_eml', 'opt/mk-auth/tmp_sql'):
        directory = safe(root, name)
        if not directory.is_dir():
            continue
        for current, dirs, files in os.walk(str(directory), followlinks=False):
            dirs[:] = [d for d in dirs if not (Path(current)/d).is_symlink()]
            candidates.extend(Path(current)/f for f in files if old_file(Path(current)/f, cutoff))
    for name in ('tmp', 'var/tmp'):
        directory = safe(root, name)
        if directory.is_dir():
            candidates.extend(p for p in directory.glob('mkauth-*') if old_file(p, cutoff))
    report['temporary_candidates'] = len(candidates)
    report['temporary_removed'] = 0
    for path in candidates:
        if apply and old_file(path, cutoff):
            info = path.stat()
            if (info.st_dev, info.st_ino) in opened:
                continue
            path.unlink()
            report['temporary_removed'] += 1


def backup_retention(root, keep, days, apply, report):
    directory = safe(root, 'opt/mk-auth/bckp')
    database = safe(root, 'opt/mk-auth/dados/backup.db')
    if not directory.is_dir() or not database.is_file():
        report['backups'] = 'Diretorio ou indice ausente; preservados'
        return
    db = sqlite3.connect(str(database), timeout=2)
    moved = []
    quarantine = None
    committed = False
    try:
        if db.execute('PRAGMA quick_check').fetchall() != [('ok',)]:
            raise RuntimeError('backup.db sem integridade; limpeza cancelada')
        columns = {r[1] for r in db.execute('PRAGMA table_info(backup)')}
        if not {'uuid', 'arquivo', 'tipo', 'data', 'descricao'} <= columns:
            raise RuntimeError('Esquema de backup desconhecido; preservado')
        # A reserved lock serializes selection/deletion with index writers.
        db.execute('BEGIN IMMEDIATE')
        rows = db.execute("SELECT rowid,uuid,arquivo,tipo,data FROM backup WHERE descricao='backup automatico' AND tipo='maz' ORDER BY datetime(data) DESC,rowid DESC").fetchall()
        cutoff = time.time() - days * 86400
        selected = []
        for row in rows[keep:]:
            rowid, uuid, name, kind, date = row
            if not isinstance(name, str) or not name or '/' in name or '\\' in name or name in ('.', '..') or '\x00' in name:
                continue
            path = directory / (name+'.maz')
            if path.is_symlink():
                continue
            # Do not remove an archive still referenced by another (possibly manual) entry.
            if db.execute("SELECT count(*) FROM backup WHERE arquivo=? AND tipo='maz'", (name,)).fetchone()[0] != 1:
                continue
            if path.exists():
                if not old_file(path, cutoff):
                    continue
            else:
                try:
                    if datetime.datetime.fromisoformat(str(date)).timestamp() >= cutoff:
                        continue
                except (ValueError, TypeError):
                    continue
            selected.append((rowid, path))
        report['automatic_backups'] = len(rows)
        report['backup_candidates'] = len(selected)
        report['backup_entries_removed'] = 0
        if not apply or not selected:
            db.rollback()
            return
        snapshots = safe(root, 'opt/mk-auth/backups/suite-cleanup')
        snapshots.mkdir(parents=True, exist_ok=True, mode=0o700)
        os.chmod(str(snapshots), 0o700)
        snapshot = snapshots/'backup-index.previous.sqlite'
        if snapshot.is_symlink():
            raise RuntimeError('Snapshot simbolico recusado')
        # SQLite backup API includes WAL contents; never cp a live database or chmod 666.
        staging = snapshots/('index-'+str(os.getpid())+'.sqlite')
        if staging.exists() or staging.is_symlink():
            raise RuntimeError('Snapshot temporario ja existe')
        source = sqlite3.connect(str(database), timeout=2)
        dest = sqlite3.connect(str(staging))
        try:
            source.backup(dest)
        finally:
            dest.close()
            source.close()
        os.chmod(str(staging), 0o600)
        os.replace(str(staging), str(snapshot))
        quarantine = directory/('.suite-cleanup-'+str(os.getpid()))
        quarantine.mkdir(mode=0o700)
        for rowid, path in selected:
            if path.exists():
                target = quarantine/path.name
                path.rename(target)
                moved.append((path, target))
            db.execute("DELETE FROM backup WHERE rowid=? AND descricao='backup automatico' AND tipo='maz'", (rowid,))
        db.commit()
        committed = True
        report['backup_entries_removed'] = len(selected)
        for original, target in moved:
            target.unlink()
        quarantine.rmdir()
        report['index_snapshot'] = str(snapshot)
    except Exception:
        if not committed:
            for original, target in reversed(moved):
                if target.exists():
                    target.rename(original)
            db.rollback()
            if quarantine and quarantine.exists():
                quarantine.rmdir()
        raise
    finally:
        db.close()


def docker_cleanup(apply, enabled, report):
    if not enabled:
        report['docker'] = 'Desativado na configuracao'
        return
    if not apply:
        report['docker'] = 'Simulacao: containers parados, imagens dangling e cache sem uso ha 168h; volumes preservados'
        return
    import shutil
    if not shutil.which('docker'):
        report['docker'] = 'Docker ausente'
        return
    # No volume prune, service restart, daemon.json edit or running-container removal.
    for args in (['container', 'prune'], ['image', 'prune'], ['builder', 'prune']):
        result = subprocess.run(['docker']+args+['-f', '--filter', 'until=168h'], stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True, timeout=120)
        report['docker_'+args[0]] = {'status': result.returncode, 'output': result.stdout[-4000:]}
        if result.returncode:
            raise RuntimeError('Docker '+args[0]+' nao concluido; confira o log')


def rotate_log(root, apply, report):
    config = safe(root, 'etc/logrotate.d/mkauth-suite-cleanup')
    if root == Path('/') and apply and Path('/usr/sbin/logrotate').is_file() and config.is_file():
        subprocess.run(['/usr/sbin/logrotate', str(config)], check=True, timeout=30)
        report['logrotate'] = 'Rotacao diaria sem forcar; 7 arquivos retidos'


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--root', default='/')
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    root = Path(os.path.abspath(args.root))
    # /var/run is normally a symlink to /run on Debian; use its physical directory.
    lock = safe(root, 'run/mkauth-limpeza.lock')
    lock.parent.mkdir(parents=True, exist_ok=True)
    descriptor = os.open(str(lock), os.O_CREAT | os.O_WRONLY | getattr(os, 'O_NOFOLLOW', 0), 0o600)
    with os.fdopen(descriptor, 'w') as stream:
        try:
            fcntl.flock(stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            print('Limpeza ja em execucao; ignorada')
            return
        config = {'keep_backups': 15, 'temporary_days': 7, 'docker': True}
        path = safe(root, 'etc/mkauth-suite-cleanup.json')
        if path.exists():
            config.update(json.loads(path.read_text()))
        keep, days = int(config['keep_backups']), int(config['temporary_days'])
        if keep < 15 or days < 7:
            raise RuntimeError('Retencao minima: 15 backups e 7 dias')
        report = {'time': datetime.datetime.now().isoformat(), 'apply': args.apply, 'errors': []}
        for action in (lambda: rotate_log(root, args.apply, report),
                       lambda: temporary_files(root, days, args.apply, report),
                       lambda: backup_retention(root, keep, days, args.apply, report),
                       lambda: docker_cleanup(args.apply and root == Path('/'), bool(config['docker']), report)):
            try:
                action()
            except Exception as error:
                report['errors'].append(str(error))
        print(json.dumps(report, ensure_ascii=False))
        if report['errors']:
            raise SystemExit(1)


if __name__ == '__main__':
    main()
