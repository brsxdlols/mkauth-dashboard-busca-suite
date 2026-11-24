#!/usr/bin/env python3
"""Fix the known native manual-disconnect timestamp without running a disconnect."""
import argparse, hashlib, json, os, shutil, subprocess, tempfile
from pathlib import Path

MARKER=b'mka-native-disconnect-clock-v1'

def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()
def quoted(value):return "'"+str(value).replace('\\','\\\\').replace("'","\\'")+"'"
def atomic(path,data,mode=0o644):
    fd,name=tempfile.mkstemp(prefix='.suite-disconnect-',dir=str(path.parent))
    try:
        with os.fdopen(fd,'wb') as f:f.write(data)
        os.chmod(name,mode);os.replace(name,path)
    finally:
        if os.path.exists(name):os.unlink(name)

def main():
    ap=argparse.ArgumentParser();ap.add_argument('admin',type=Path);ap.add_argument('backup',type=Path);ap.add_argument('--dry-run',action='store_true');a=ap.parse_args()
    a.backup.mkdir(parents=True,exist_ok=True);a.backup.chmod(0o700)
    root=Path(__file__).resolve().parent.parent
    candidates=[a.admin.parent/p/'freeradius.class' for p in ('libs','classes')]
    candidates=[p for p in candidates if p.is_file()]
    if len(candidates)!=1:raise RuntimeError('Classe nativa ausente ou ambigua; preservada.')
    entry=candidates[0];storage=a.admin.parent/'dados/suite-native-disconnect'
    manifest=storage/'manifest.json'
    for p in (entry,storage,manifest,a.backup):
        if p.is_symlink() or any(x.is_symlink() for x in p.parents):raise RuntimeError('Caminho simbolico recusado: '+str(p))
    old=entry.read_bytes();original=entry
    if MARKER in old:
        meta=json.loads(manifest.read_text());original=Path(meta['original'])
        if str(original.parent)!=str(storage) or original.is_symlink() or digest(original)!=meta['source_sha256'] or digest(entry)!=meta['installed_sha256']:raise RuntimeError('Hook/original nativo alterado; preservado para revisao.')
    helper=root/'addons/shared/native_disconnect_clock.php'
    # Capture the decoded/patched class for linting. Never invoke any method.
    checker=a.backup/'check.php'
    checker.write_text("<?php define('MKA_NATIVE_DISCONNECT_TEST',true);require "+quoted(helper)+";$p=$argv[1];ob_start();eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));ob_end_clean();eval(mka_native_disconnect_second_stage($mkaActivityDecoded,true));$b=$GLOBALS['mkaNativeDisconnectBody'];file_put_contents($argv[2],'<?php '.$b);chmod($argv[2],0600);echo hash('sha256',$b);",encoding='utf8')
    checked=a.backup/'decoded.checked.php'
    result=subprocess.run(['php',str(checker),str(original),str(checked)],capture_output=True,text=True)
    if result.returncode or not checked.is_file():raise RuntimeError('Formato nativo nao reconhecido; nenhuma substituicao aplicada. '+result.stderr[-350:])
    subprocess.run(['php','-l',str(checked)],check=True)
    if a.dry_run:print('[desconexao] Consulta conhecida e PHP validado; nenhuma sessao ou arquivo nativo alterado.');return
    storage.mkdir(mode=0o755,parents=True,exist_ok=True);storage.chmod(0o755)
    saved=storage/('freeradius-'+digest(original)+'.class')
    if not saved.exists():atomic(saved,original.read_bytes(),0o444)
    if digest(saved)!=digest(original):raise RuntimeError('Backup original divergente.')
    runtime=a.admin/'addons/shared/native_disconnect_clock.php'
    for p in (runtime,):
        if p.is_symlink() or any(x.is_symlink() for x in p.parents):raise RuntimeError('Runtime simbolico recusado.')
    wrapper=('<?php /* '+MARKER.decode()+' */\n$mkaNativeDisconnectOriginal='+quoted(saved)+';\nrequire '+quoted(runtime)+';\n').encode()
    before={'entry':str(entry),'source_sha256':digest(original),'original':str(saved),'installed_sha256':hashlib.sha256(wrapper).hexdigest()}
    shutil.copy2(entry,a.backup/'freeradius.class.before')
    if manifest.exists():shutil.copy2(manifest,a.backup/'manifest.before.json')
    if runtime.exists():shutil.copy2(runtime,a.backup/'runtime.before.php')
    installed=[]
    try:
        if entry.read_bytes()!=old:raise RuntimeError('Classe nativa alterada durante a validacao.')
        atomic(runtime,helper.read_bytes());installed.append(runtime)
        atomic(entry,wrapper);installed.append(entry)
        # Autoload and native class definition only; no method is called.
        subprocess.run(['php','-r',"require "+quoted(entry)+";if(!class_exists('MKAC_FREERADIUS',false))exit(1);"],check=True,capture_output=True)
        atomic(manifest,json.dumps(before,indent=2).encode())
    except Exception:
        atomic(entry,(a.backup/'freeradius.class.before').read_bytes())
        prior=a.backup/'runtime.before.php'
        if prior.exists():atomic(runtime,prior.read_bytes())
        elif runtime in installed:runtime.unlink()
        raise
    print('[desconexao] Encerramento manual usa horario do banco e preserva Stop ja recebido; nenhuma desconexao executada.')

if __name__=='__main__':
    try:main()
    except (RuntimeError,OSError,ValueError,subprocess.SubprocessError) as e:
        print('[aviso] Correcao nativa de desconexao nao aplicada: '+str(e));raise SystemExit(1)
