#!/usr/bin/env python3
"""Upgrade an existing reconciler without running it or changing its cron."""
import pathlib, re, shutil, subprocess, sys
scripts=pathlib.Path(sys.argv[1]); backup=pathlib.Path(sys.argv[2])
target=scripts/'mkauth_radius_ppp_reconcile.php'
if not target.is_file():
    print('[reconcile] Rotina ausente; nenhuma rotina nova criada.'); sys.exit(0)
old=target.read_bytes(); text=old.decode('utf-8')
source=(pathlib.Path(__file__).parent/'install-radius-reconcile.sh').read_text()
guard=re.search(r"<<'GUARD_PHP'\n(.*?)\nGUARD_PHP",source,re.S).group(1)+'\n'
if 'offline_guard(' not in text:
    anchor='    foreach ($active as $ppp) {'
    if text.count(anchor)!=1 or text.count('$onlyLogin = null;')!=1:
        print('[reconcile] Versao desconhecida preservada; atualizacao manual necessaria.');sys.exit(0)
    text=text.replace('$onlyLogin = null;', "require_once __DIR__.'/mkauth_radius_offline_guard.php';\n$onlyLogin = null;",1)
    text=text.replace(anchor,'    offline_guard($db,$router,$active,$apply,$onlyLogin,$stats);\n'+anchor)
if 'flock(' not in text:
    text=text.replace('$onlyLogin = null;', "$runLock=fopen('/var/lib/mkauth_radius_ppp_reconcile/run.lock','c');\nif(!$runLock || !flock($runLock,LOCK_EX|LOCK_NB))exit(0);\n$onlyLogin = null;",1)
helper=scripts/'mkauth_radius_offline_guard.php'
if target.read_bytes()==text.encode() and helper.is_file() and helper.read_bytes()==guard.encode():
    print('[reconcile] Confirmacao offline ja atualizada.');sys.exit(0)
backup.mkdir(parents=True,exist_ok=True); backup.chmod(0o700)
for f in (target,helper):
    if f.exists():shutil.copy2(f,backup/f.name)
for name,data in (('reconciler.checked.php',text),('guard.checked.php',guard)):
    stage=backup/name;stage.write_text(data);subprocess.run(['php','-l',str(stage)],check=True)
if target.read_bytes()!=old:raise RuntimeError('Reconciler changed during upgrade')
helper.write_bytes(guard.encode());helper.chmod(0o600);target.write_bytes(text.encode())
print('[reconcile] Confirmacao offline atualizada; cron existente preservado; nenhuma sessao alterada na instalacao.')
