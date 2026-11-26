#!/usr/bin/env python3
"""Repair addon ownership/traversal; preserve private paths and symbolic links."""
import argparse, json, os, pwd, stat, sys
from pathlib import Path

NAMES = ('dashboard', 'busca_inteligente', 'dashboard-legado', 'busca_inteligente-legado', 'shared', 'rel_desativados')
PRIVATE = frozenset(('backup', 'backups', 'private', '.private', '.git', '.svn', '.ssh'))

def apply(root, backup, owner=None, all_addons=False):
    root, backup = Path(root), Path(backup)
    if root.is_symlink() or any(p.is_symlink() for p in root.parents) or not root.is_dir() or root.name != 'addons':
        raise RuntimeError('Diretorio addons invalido; permissoes nao alteradas.')
    account = None
    for name in ([owner] if owner else ['www-data', 'apache']):
        try: account = pwd.getpwnam(name); break
        except KeyError: pass
    if account is None: raise RuntimeError('Usuario web nao encontrado; revise as permissoes.')
    targets = [root]
    names = sorted(p.name for p in root.iterdir() if p.name.lower() not in PRIVATE) if all_addons else NAMES
    for name in names:
        base = root / name
        if base.is_symlink():
            if all_addons: continue
            raise RuntimeError('Addon da suite e link simbolico: '+name)
        if all_addons and base.is_file():
            targets.append(base);continue
        if not base.is_dir(): continue
        for directory, dirs, files in os.walk(base, followlinks=False):
            dirs[:] = [d for d in dirs if not (Path(directory)/d).is_symlink() and d.lower() not in PRIVATE]
            targets.append(Path(directory))
            targets.extend(Path(directory)/f for f in files if not (Path(directory)/f).is_symlink() and f.lower() not in PRIVATE)
    changes = []
    for path in targets:
        info = path.stat(); mode = stat.S_IMODE(info.st_mode)
        relative = path.relative_to(root)
        # Temporary customer exports stay restricted; public directories are traversable.
        wanted = (0o770 if 'tmp' in relative.parts else 0o755) if path.is_dir() else mode
        if (info.st_uid, info.st_gid, mode) != (account.pw_uid, account.pw_gid, wanted):
            changes.append(dict(path=str(relative), uid=info.st_uid, gid=info.st_gid, mode=mode, wanted=wanted))
    if not changes:
        print('[OK] Permissoes dos addons '+('instalados' if all_addons else 'da suite')+' ja corretas.'); return
    if backup.is_symlink() or any(p.is_symlink() for p in backup.parents):raise RuntimeError('Backup simbolico recusado.')
    backup.mkdir(parents=True, exist_ok=True); backup.chmod(0o700)
    snapshot = backup/'addon-permissions.json'
    if snapshot.exists(): raise RuntimeError('Backup de permissoes ja existe; use outro diretorio.')
    descriptor = os.open(str(snapshot), os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, 'w') as stream: json.dump({'scope':'all-addons','changes':changes} if all_addons else changes, stream)
    for item in changes:
        path = root/item['path']
        os.chown(path, account.pw_uid, account.pw_gid, follow_symlinks=False)
        if path.is_dir(): path.chmod(item['wanted'])
    print('[OK] Permissoes dos addons '+('instalados' if all_addons else 'da suite')+' ajustadas para '+account.pw_name+'; backup: '+str(snapshot))

def restore(root, snapshot):
    root = Path(root)
    if root.is_symlink() or not root.is_dir() or root.name != 'addons':
        raise RuntimeError('Diretorio addons invalido.')
    data = json.loads(Path(snapshot).read_text())
    all_addons = isinstance(data,dict) and data.get('scope') == 'all-addons'
    rows = data['changes'] if all_addons else data
    if not isinstance(rows,list):raise RuntimeError('Backup de permissoes invalido.')
    for row in rows:
        relative = Path(row['path'])
        if relative.is_absolute() or '..' in relative.parts or (relative.parts and not all_addons and relative.parts[0] not in NAMES):
            raise RuntimeError('Caminho invalido no backup.')
        path = root/relative
        if path.is_symlink() or any(p.is_symlink() for p in path.parents) or root.resolve() not in (path.resolve(), *path.resolve().parents):
            raise RuntimeError('Link ou caminho fora de addons.')
    for row in rows:
        path = root/row['path']
        os.chown(path, row['uid'], row['gid'], follow_symlinks=False)
        path.chmod(row['mode'])
    print('[OK] Permissoes anteriores restauradas.')

if __name__ == '__main__':
    try:
        if sys.argv[1] == '--restore': restore(sys.argv[2], sys.argv[3])
        else:
            parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('root');parser.add_argument('backup');parser.add_argument('--all-addons',action='store_true');args=parser.parse_args()
            apply(args.root,args.backup,all_addons=args.all_addons)
    except Exception as error:
        print('[ERRO] Permissoes dos addons: '+str(error), file=sys.stderr); sys.exit(1)

