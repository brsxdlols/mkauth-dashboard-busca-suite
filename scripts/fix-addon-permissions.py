#!/usr/bin/env python3
"""Normalize suite ownership and directory traversal; preserve unrelated addons."""
import json, os, pwd, stat, sys
from pathlib import Path

NAMES = ('dashboard', 'busca_inteligente', 'dashboard-legado', 'busca_inteligente-legado', 'shared')

def apply(root, backup, owner=None):
    root, backup = Path(root), Path(backup)
    if root.is_symlink() or not root.is_dir() or root.name != 'addons':
        raise RuntimeError('Diretorio addons invalido; permissoes nao alteradas.')
    account = None
    for name in ([owner] if owner else ['www-data', 'apache']):
        try: account = pwd.getpwnam(name); break
        except KeyError: pass
    if account is None: raise RuntimeError('Usuario web nao encontrado; revise as permissoes.')
    targets = [root]
    for name in NAMES:
        base = root / name
        if base.is_symlink(): raise RuntimeError('Addon da suite e link simbolico: '+name)
        if not base.is_dir(): continue
        for directory, dirs, files in os.walk(base, followlinks=False):
            dirs[:] = [d for d in dirs if not (Path(directory)/d).is_symlink()]
            targets.append(Path(directory))
            targets.extend(Path(directory)/f for f in files if not (Path(directory)/f).is_symlink())
    changes = []
    for path in targets:
        info = path.stat(); mode = stat.S_IMODE(info.st_mode)
        relative = path.relative_to(root)
        # Temporary customer exports stay restricted; public directories are traversable.
        wanted = (0o770 if 'tmp' in relative.parts else 0o755) if path.is_dir() else mode
        if (info.st_uid, info.st_gid, mode) != (account.pw_uid, account.pw_gid, wanted):
            changes.append(dict(path=str(relative), uid=info.st_uid, gid=info.st_gid, mode=mode, wanted=wanted))
    if not changes:
        print('[OK] Permissoes dos addons da suite ja corretas.'); return
    backup.mkdir(parents=True, exist_ok=True); backup.chmod(0o700)
    snapshot = backup/'addon-permissions.json'
    if snapshot.exists(): raise RuntimeError('Backup de permissoes ja existe; use outro diretorio.')
    descriptor = os.open(str(snapshot), os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, 'w') as stream: json.dump(changes, stream)
    for item in changes:
        path = root/item['path']
        os.chown(path, account.pw_uid, account.pw_gid, follow_symlinks=False)
        if path.is_dir(): path.chmod(item['wanted'])
    print('[OK] Permissoes dos addons da suite ajustadas para '+account.pw_name+'; backup: '+str(snapshot))

def restore(root, snapshot):
    root = Path(root)
    if root.is_symlink() or not root.is_dir() or root.name != 'addons':
        raise RuntimeError('Diretorio addons invalido.')
    rows = json.loads(Path(snapshot).read_text())
    for row in rows:
        relative = Path(row['path'])
        if relative.is_absolute() or '..' in relative.parts or (relative.parts and relative.parts[0] not in NAMES):
            raise RuntimeError('Caminho invalido no backup.')
        path = root/relative
        if path.is_symlink() or root.resolve() not in (path.resolve(), *path.resolve().parents):
            raise RuntimeError('Link ou caminho fora de addons.')
    for row in rows:
        path = root/row['path']
        os.chown(path, row['uid'], row['gid'], follow_symlinks=False)
        path.chmod(row['mode'])
    print('[OK] Permissoes anteriores restauradas.')

if __name__ == '__main__':
    try:
        if sys.argv[1] == '--restore': restore(sys.argv[2], sys.argv[3])
        else: apply(sys.argv[1], sys.argv[2])
    except Exception as error:
        print('[ERRO] Permissoes dos addons: '+str(error), file=sys.stderr); sys.exit(1)
