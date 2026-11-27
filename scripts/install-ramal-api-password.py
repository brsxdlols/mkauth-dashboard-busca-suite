#!/usr/bin/env python3
"""Preserve native ramal controllers and gate disclosure of their API password."""
import hashlib,json,os,shutil,subprocess,sys,tempfile,time,ipaddress
from pathlib import Path
def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()
def literal(value):return "'"+str(value).replace('\\','\\\\').replace("'","\\'")+"'"
def atomic(path,data,mode=0o644):
 fd,name=tempfile.mkstemp(prefix='.ramal-password-',dir=str(path.parent))
 try:
  with os.fdopen(fd,'wb')as output:output.write(data)
  os.chmod(name,mode)
  if path.exists():st=path.stat();os.chown(name,st.st_uid,st.st_gid)
  os.replace(name,path)
 finally:
  if os.path.exists(name):os.unlink(name)
def main():
 admin,backup=map(Path,sys.argv[1:]);runtime=admin/'addons/shared/native_ramal_password.php';storage=admin.parent/'dados/suite-ramal-password';manifest=storage/'manifest.json'
 targets=[('ramal_alt.hhvm','form'),('executar_ramal.hhvm','save')]
 guard_source=Path(__file__).resolve().parents[1]/'admin/mka_suite_ramal_password_guard.hhvm';guard_target=admin/guard_source.name
 if not guard_source.is_file():raise RuntimeError('Guard de gravacao ausente no pacote.')
 if guard_target.is_symlink()or(guard_target.exists()and b'mka-suite-ramal-password-guard-v1'not in guard_target.read_bytes()):raise RuntimeError('Guard personalizado; arquivo preservado.')
 if not all((admin/name).is_file()for name,_ in targets):raise RuntimeError('Paginas nativas ausentes; nenhuma alteracao aplicada.')
 for path in [admin,backup,storage,manifest]+[admin/name for name,_ in targets]:
  if path.is_symlink()or any(p.is_symlink()for p in path.parents):raise RuntimeError('Caminho simbolico preservado.')
 backup.mkdir(parents=True,exist_ok=True);backup.chmod(0o700);previous=json.loads(manifest.read_text())if manifest.exists()else {};records=[]
 check=backup/'check.php';check.write_text("<?php define('MKA_RAMAL_PASSWORD_TEST',true);require "+literal(runtime)+";require "+literal(runtime.parent/'native_activity_performance.php')+";$p=$argv[1];eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));mka_ramal_password_patch($mkaActivityDecoded,$argv[2]);echo 'compatible';")
 for name,kind in targets:
  target=admin/name;old=target.read_bytes();original=target
  if b'mka-suite-ramal-password-v1'in old:
   info=previous.get('files',{}).get(name)
   if not info or digest(target)!=info['installed_sha256']:raise RuntimeError('Wrapper divergente; pagina preservada.')
   original=Path(info['original'])
   if not original.is_file()or digest(original)!=info['source_sha256']:raise RuntimeError('Original nativo divergente.')
  result=subprocess.check_output(['php',str(check),str(original),kind],stderr=subprocess.STDOUT).decode().strip()
  if result!='compatible':raise RuntimeError('Formato nativo desconhecido.')
  records.append((target,kind,old,original,digest(original)))
 verify=backup/'verify.php';verify.write_text("<?php require "+literal(runtime.parent/'native_activity_performance.php')+";$p=$argv[1];eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));if(preg_match_all('/case\\s*\\(\\s*MKAC_CRYPT::([A-Za-z_]\\w*)\\(\\s*\\$\\w+\\s*,\\s*\\$\\w+\\s*\\)\\s*==\\s*true/s',$mkaActivityDecoded,$matches)!==1)exit(2);echo $matches[1][0];")
 method=subprocess.check_output(['php',str(verify),str(admin/'executar_login.hhvm')],stderr=subprocess.STDOUT).decode().strip()
 import re
 if not re.fullmatch(r'[A-Za-z_]\w*',method):raise RuntimeError('Verificador nativo nao reconhecido.')
 storage.mkdir(parents=True,exist_ok=True);storage.chmod(0o755)
 rate=storage/'rate-limits';rate.mkdir(exist_ok=True);rate.chmod(0o700)
 import pwd
 web=pwd.getpwnam('www-data');os.chown(str(rate),web.pw_uid,web.pw_gid)
 sources={}
 if (admin.parent/'include/configure.php').is_file()and(admin.parent/'include/conexao.php').is_file():
  try:
   php="ob_start();require "+literal(admin.parent/'include/configure.php')+";require "+literal(admin.parent/'include/conexao.php')+";ob_end_clean();$r=$LOADMYSQL->query('SELECT nasname FROM nas');$ips=[];while($row=$r->fetch_row())$ips[]=$row[0];echo json_encode($ips);"
   ips=json.loads(subprocess.check_output(['php','-r',php],cwd=str(admin)).decode())
   binary=shutil.which('ip',path='/usr/sbin:/usr/bin:/sbin:/bin')
   if binary:
    for ip in ips:
     try:
      ipaddress.ip_address(ip);result=subprocess.run([binary,'-j','route','get',ip],stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=3);route=json.loads(result.stdout)[0];source=route.get('prefsrc',route.get('src'));ipaddress.ip_address(source);sources[ip]=source
     except (ValueError,KeyError,IndexError,TypeError,subprocess.SubprocessError):continue
  except (ValueError,OSError,subprocess.SubprocessError):pass
 report={'version':1,'verify_method':method,'login_sha256':digest(admin/'executar_login.hhvm'),'source_ips':sources,'source_checked_at':int(time.time()),'files':{}};applied=[]
 prior_manifest=manifest.read_bytes()if manifest.exists()else None
 try:
  prior_guard=guard_target.read_bytes()if guard_target.exists()else None
  if prior_guard is not None:(backup/'guard.before').write_bytes(prior_guard)
  atomic(guard_target,guard_source.read_bytes());applied.append((guard_target,prior_guard));report['guard_sha256']=digest(guard_target)
  for target,kind,old,original,sha in records:
   saved=storage/(target.stem+'-'+sha+'.hhvm')
   if not saved.exists():shutil.copy2(str(original),str(saved));saved.chmod(0o444)
   if digest(saved)!=sha:raise RuntimeError('Backup nativo divergente.')
   (backup/(target.name+'.before')).write_bytes(old)
   wrapper=("<?php\n/* mka-suite-ramal-password-v1 */\n$mkaRamalOriginal="+literal(saved)+";\n$mkaRamalHash="+literal(sha)+";\n$mkaRamalKind="+literal(kind)+";\nrequire "+literal(runtime)+";\n").encode()
   if target.read_bytes()!=old:raise RuntimeError('Pagina alterada durante instalacao.')
   atomic(target,wrapper,target.stat().st_mode&0o777);applied.append((target,old))
   report['files'][target.name]={'original':str(saved),'source_sha256':sha,'installed_sha256':digest(target)}
  atomic(manifest,json.dumps(report,indent=2).encode())
 except Exception:
  for target,old in reversed(applied):
   if old is None:target.unlink()
   else:atomic(target,old,target.stat().st_mode&0o777)
  if prior_manifest is None:
   if manifest.exists():manifest.unlink()
  else:atomic(manifest,prior_manifest)
  raise
 print('[ramal] Senha da API protegida: visualizacao exige Usuarios e senha do operador; gravacao nativa preservada.')
if __name__=='__main__':
 try:main()
 except Exception as error:print('[aviso] Protecao da senha do ramal nao concluida: '+str(error));raise SystemExit(1)
