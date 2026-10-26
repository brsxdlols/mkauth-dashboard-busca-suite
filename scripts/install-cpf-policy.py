#!/usr/bin/env python3
"""Install only the CPF policy package. Never reads or runs migration SQL."""
import argparse,hashlib,json,os,re,shutil,subprocess,sys,tempfile
from pathlib import Path

def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def command(args):return subprocess.check_output(args,stderr=subprocess.STDOUT).decode('utf-8')
def php_string(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"

def main():
 ap=argparse.ArgumentParser();ap.add_argument('admin',type=Path);ap.add_argument('backup',type=Path);ap.add_argument('--dry-run',action='store_true');ap.add_argument('--storage',type=Path,default=Path('/var/lib/mkauth-suite-cpf-opcional'));a=ap.parse_args()
 root=Path(__file__).resolve().parent.parent;source=root/'addons/nn_cpf_opcional';helper=root/'scripts/cpf-native-compat.php';target=a.admin/'addons/nn_cpf_opcional';modes=['conf_opcoes','cliente_ins','cliente_alt','executar_cliente','executar_provedor']
 a.backup.mkdir(parents=True,exist_ok=True);os.chmod(a.backup,0o700);report={'status':'not_applied','files':{},'php':command(['php','-r','echo PHP_VERSION;'])}
 try:
  if target.is_symlink():raise RuntimeError('Diretorio CPF e symlink; preservado')
  if a.admin.resolve() in a.storage.resolve().parents:raise RuntimeError('Originais devem ficar fora da pasta publica')
  originals={};metadata={};adopted=[]
  manifest=target/'suite-manifest.json'
  if manifest.is_symlink():raise RuntimeError('Manifesto CPF e symlink')
  previous=json.loads(manifest.read_text()) if manifest.exists() else {}
  for mode in modes:
   p=a.admin/(mode+'.hhvm')
   if not p.is_file() or p.is_symlink():raise RuntimeError('Entrada nativa ausente ou symlink: '+mode)
   raw=p.read_bytes();text=raw.decode('latin1')
   # Preserve the already deployed reference integration, including all runtimes.
   reference=("$GLOBALS['nn_cpf_entry'] = '"+mode+"';\nrequire_once __DIR__ . '/addons/nn_cpf_opcional/policy.php';\nob_start('nn_cpf_render');\nrequire __DIR__ . '/addons/nn_cpf_opcional/native/"+mode+".runtime.php';")
   if reference in text.replace('\r\n','\n'):
    if not (target/'native'/(mode+'.runtime.php')).is_file():raise RuntimeError('Runtime original CPF ausente')
    adopted.append(mode);continue
   if 'mka-suite-cpf-policy-v1' in text:
    info=previous.get('entries',{}).get(mode,{})
    if digest(p)!=info.get('installed_sha256'):raise RuntimeError('Hook CPF modificado por outro patch: '+mode)
    original=Path(info['original']);
    if digest(original)!=info['source_sha256']:raise RuntimeError('Original CPF modificado')
    originals[mode]=original
   else:originals[mode]=p
   metadata[mode]=json.loads(command(['php',str(helper),str(originals[mode]),mode]))
  if adopted and len(adopted)!=len(modes):raise RuntimeError('Integracao de origem parcial; preservar e revisar os cinco hooks')
  methods={m['permission_method'] for m in metadata.values() if m.get('permission_method')}
  if not adopted and len(methods)!=1:raise RuntimeError('Metodo nativo de permissao ambiguo ou ausente')
  method=next(iter(methods)) if methods else None
  report['compatibility']=metadata;report['reference_preserved']=bool(adopted)
  # Refuse to overwrite customized modules from another integration.
  for name in ['policy.php','policy.js','runtime.php']:
   p=target/name
   if p.is_symlink():raise RuntimeError('Modulo CPF e symlink')
   if p.exists() and not previous:
    if name=='runtime.php':raise RuntimeError('Runtime CPF desconhecido')
    # Reference copies can be retained if they already provide the required policy.
    body=p.read_text(encoding='utf-8')
    if name=='policy.php' and not all(x in body for x in ['nn_cpf_guard_client','nn_cpf_save_policy','nn_cpf_filter_payload','nn_cpf_render','hash_equals','nn_permitir_sem_cpf']):raise RuntimeError('Modulo CPF de origem desconhecida')
   elif p.exists() and digest(p)!=previous.get('modules',{}).get(name):raise RuntimeError('Modulo CPF alterado depois da instalacao')
  if a.dry_run:
   report['status']='compatible';return
  # Back up the affected DB table and exact key before any writes.
  for name in ['cpf-policy-state.php','installer-database.php'] :shutil.copy2(root/'scripts'/name,a.backup/name)
  state=a.backup/'database-sis_opcao.json';command(['php',str(a.backup/'cpf-policy-state.php'),'backup',str(state)])
  changes={};entries={};a.storage.mkdir(parents=True,exist_ok=True);os.chmod(a.storage,0o755)
  if not adopted:
   for mode in modes:
    p=a.admin/(mode+'.hhvm');original=originals[mode];h=digest(original);saved=a.storage/(mode+'-'+h+'.hhvm')
    if not saved.exists():shutil.copy2(original,saved);os.chmod(saved,0o444)
    if digest(saved)!=h:raise RuntimeError('Backup original difere')
    wrapper="<?php\n/* mka-suite-cpf-policy-v1 */\n$nnCpfOriginal="+php_string(saved)+";\n$nnCpfMode="+php_string(mode)+";\n$nnCpfPermissionMethod="+php_string(method)+";\nrequire "+php_string(target/'runtime.php')+";\n"
    changes[p]=wrapper.encode();entries[mode]={'original':str(saved),'source_sha256':h,'installed_sha256':hashlib.sha256(changes[p]).hexdigest()}
  for name in ['policy.php','policy.js','runtime.php','README.md','INTEGRACAO.md','INTEGRACAO-SUITE.md']:
   if adopted and (target/name).exists():continue
   changes[target/name]=(source/name).read_bytes()
  module_hashes={name:hashlib.sha256(changes.get(target/name,(target/name).read_bytes() if (target/name).exists() else b'')).hexdigest() for name in ['policy.php','policy.js','runtime.php'] if target/name in changes or (target/name).exists()}
  newmanifest={'entries':entries,'modules':module_hashes,'reference_preserved':bool(adopted),'permission_method':method}
  changes[manifest]=json.dumps(newmanifest,indent=2).encode()
  rollback=[]
  for i,(p,data) in enumerate(changes.items()):
   entry={'path':str(p),'installed_sha256':hashlib.sha256(data).hexdigest(),'before':None}
   if p.exists():
    b=a.backup/(str(i)+'.before');shutil.copy2(p,b);entry['before']=str(b)
   rollback.append(entry)
  (a.backup/'files.json').write_text(json.dumps(rollback,indent=2));shutil.copy2(root/'scripts/rollback-cpf-policy.py',a.backup/'rollback-cpf-policy.py')
  applied=[]
  try:
   for p,data in changes.items():
    p.parent.mkdir(parents=True,exist_ok=True);st=p.stat() if p.exists() else (a.admin/'executar_cliente.hhvm').stat()
    fd,tmp=tempfile.mkstemp(prefix='.cpf-stage-',dir=p.parent);os.close(fd);Path(tmp).write_bytes(data);os.chmod(tmp,st.st_mode&0o777);os.chown(tmp,st.st_uid,st.st_gid)
    if p.suffix in ['.php','.hhvm']:command(['php','-l',tmp])
    os.replace(tmp,p);applied.append(p)
   command(['php',str(a.backup/'cpf-policy-state.php'),'initialize',str(state)])
  except Exception:
   for item in reversed(rollback):
    p=Path(item['path'])
    if p not in applied:continue
    if item['before']:shutil.copy2(item['before'],p)
    else:p.unlink()
   raise
  report['status']='reference_preserved' if adopted else 'installed';report['rollback']='python3 '+str(a.backup/'rollback-cpf-policy.py');report['files']=entries
 except Exception as error:
  report['reason']=str(error);raise
 finally:
  (a.backup/'cpf-install-report.json').write_text(json.dumps(report,indent=2))
  print('[cpf] '+report['status']+'; relatorio: '+str(a.backup/'cpf-install-report.json'))
if __name__=='__main__':
 try:main()
 except Exception as e:print('[cpf] Nao aplicado: '+str(e),file=sys.stderr);sys.exit(1)
