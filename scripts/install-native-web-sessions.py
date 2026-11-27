#!/usr/bin/env python3
"""Preserve the native session controller and add an escaped owner label only."""
import hashlib,json,os,shutil,subprocess,sys,tempfile
from pathlib import Path
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def literal(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
def main():
    admin=Path(sys.argv[1]);backup=Path(sys.argv[2]);target=admin/'sessoes.hhvm';runtime=admin/'addons/shared/native_web_sessions.php'
    if not target.is_file():print('[sessoes] Modo compativel da suite: lista nativa ausente; metadados e deslogar gerenciados pelo addon.');return
    if target.is_symlink()or any(p.is_symlink()for p in target.parents):raise RuntimeError('Lista simbolica preservada.')
    backup.mkdir(parents=True,exist_ok=True);backup.chmod(0o700)
    storage=admin.parent/'dados/suite-native-sessions';manifest=storage/'manifest.json'
    for path in (storage,manifest,backup):
        if path.is_symlink()or any(p.is_symlink()for p in path.parents):raise RuntimeError('Caminho simbolico preservado.')
    old=target.read_bytes()
    if b'mka-suite-native-web-sessions-v1'in old:
        info=json.loads(manifest.read_text())
        if digest(target)!=info['installed_sha256']:raise RuntimeError('Lista alterada por outro modulo; preservada.')
        original=Path(info['original'])
        if digest(original)!=info['source_sha256']:raise RuntimeError('Original nativo divergente.')
    else:original=target
    # Decode only. Never invoke authentication, enumeration or logout at installation.
    check=backup/'check.php'
    check.write_text("<?php define('MKA_WEB_SESSIONS_TEST',true);require "+literal(runtime)+";require "+literal(runtime.parent/'native_activity_performance.php')+";$p=$argv[1];eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));mka_web_sessions_patch($mkaActivityDecoded);echo 'compatible';")
    result=subprocess.check_output(['php',str(check),str(original)],stderr=subprocess.STDOUT).decode().strip()
    if result!='compatible':raise RuntimeError('Lista nativa incompativel.')
    storage.mkdir(parents=True,exist_ok=True);storage.chmod(0o755)
    saved=storage/('sessoes-'+digest(original)+'.hhvm')
    if not saved.exists():shutil.copy2(original,saved);saved.chmod(0o444)
    if digest(saved)!=digest(original):raise RuntimeError('Backup divergente.')
    wrapper=("<?php\n/* mka-suite-native-web-sessions-v1 */\n$mkaSessionsOriginal="+literal(saved)+";\n$mkaSessionsOriginalHash="+literal(digest(saved))+";\nrequire "+literal(runtime)+";\n").encode()
    (backup/'sessoes.before').write_bytes(old)
    fd,name=tempfile.mkstemp(prefix='.sessions-',dir=str(admin));os.close(fd)
    try:
        Path(name).write_bytes(wrapper);subprocess.check_call(['php','-l',name],stdout=subprocess.DEVNULL)
        if target.read_bytes()!=old:raise RuntimeError('Lista alterada durante instalacao.')
        st=target.stat();os.chmod(name,st.st_mode&0o7777);os.chown(name,st.st_uid,st.st_gid);os.replace(name,target)
        manifest.write_text(json.dumps({'original':str(saved),'source_sha256':digest(saved),'installed_sha256':digest(target)},indent=2))
    finally:
        if os.path.exists(name):os.unlink(name)
    print('[sessoes] Usuario identificado em cada sessao; autenticacao e acao Deslogar nativas preservadas.')
if __name__=='__main__':
    try:main()
    except Exception as e:print('[aviso] Lista de sessoes preservada: '+str(e));raise SystemExit(1)

