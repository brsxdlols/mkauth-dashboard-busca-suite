#!/usr/bin/env python3
"""Apply only validated legacy dashboard calculation fixes, preserving custom pages."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import stat
import tempfile

BASELINES = {
    'graf_periodo.php': {'d3fc2ec85c0d32aff9fe997c5e33d304b95dcfdc2f32145884e5a196473fdf8c'},
    'cli_periodo.php': {'7d2ced80b0894055eb3146ea462984eddfa75f79ddc7a3eae67825f73bcccc0d'},
}
def fingerprint(data):
    return hashlib.sha256(data.replace(b'\r\n', b'\n')).hexdigest()

def apply(target, source, backup, dry_run=False):
    target=Path(target).resolve();source=Path(source).resolve()
    if not target.is_dir() or not source.is_dir():
        raise ValueError('Diretorio da Dashboard ou arquivos da correcao indisponiveis.')
    plan=[]
    for name, accepted in BASELINES.items():
        file=target/name;donor=source/name
        if file.is_symlink() or donor.is_symlink():
            raise ValueError('Link simbolico inesperado: '+name)
        current=file.read_bytes();updated=donor.read_bytes()
        if fingerprint(current)==fingerprint(updated):
            continue
        if fingerprint(current) not in accepted:
            raise ValueError('Arquivo personalizado ou versao desconhecida preservada: '+name)
        plan.append((file,current,updated))
    if dry_run:
        return {'status':'validated','files':[p.name for p,_,_ in plan]}
    if not plan:
        return {'status':'already_applied','files':[]}
    backup=Path(backup).resolve();backup.mkdir(parents=True,exist_ok=False,mode=0o700)
    for file,current,_ in plan:
        shutil.copy2(file,backup/file.name)
    (backup/'manifest.json').write_text(json.dumps({'target':str(target),'files':[p.name for p,_,_ in plan]},indent=2))
    applied=[]
    try:
        for file,current,updated in plan:
            if file.is_symlink() or file.read_bytes()!=current:
                raise RuntimeError('Arquivo mudou durante a aplicacao: '+file.name)
            info=file.stat();fd,staged=tempfile.mkstemp(prefix='.'+file.name+'.',dir=target)
            try:
                with os.fdopen(fd,'wb') as stream:
                    stream.write(updated);stream.flush();os.fsync(stream.fileno())
                os.chmod(staged,stat.S_IMODE(info.st_mode))
                if hasattr(os,'chown'):os.chown(staged,info.st_uid,info.st_gid)
                os.replace(staged,file);applied.append(file)
            finally:
                if os.path.exists(staged):os.unlink(staged)
    except Exception:
        for file in applied:shutil.copy2(backup/file.name,file)
        raise
    return {'status':'applied','files':[p.name for p,_,_ in plan],'backup':str(backup)}

if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('target',nargs='?',default='/opt/mk-auth/admin/addons/dashboard')
    parser.add_argument('--source',default=str(Path(__file__).resolve().parents[1]/'addons/dashboard'))
    parser.add_argument('--backup',default='/opt/mk-auth/backups/dashboard-calculations-'+__import__('datetime').datetime.now().strftime('%Y%m%d_%H%M%S'))
    parser.add_argument('--dry-run',action='store_true')
    args=parser.parse_args()
    print(json.dumps(apply(args.target,args.source,args.backup,args.dry_run)))
