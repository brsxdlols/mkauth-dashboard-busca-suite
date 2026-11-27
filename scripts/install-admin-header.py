#!/usr/bin/env python3
"""Install the shared administrative header loader without replacing native pages."""
import hashlib,json,os,re,sys,tempfile
from pathlib import Path
START=b'/* MKA-ADMIN-HEADER-START */';END=b'/* MKA-ADMIN-HEADER-END */'
BLOCK=START+b"\n;(function(){if(window.mkaAdminHeaderLoader==='20261126-2')return;window.mkaAdminHeaderLoader='20261126-2';var s=document.createElement('script');s.src='/admin/addons/shared/admin_header.js?v=20261126-2';document.head.appendChild(s);})();\n"+END+b'\n'
HOME_START=b'# BEGIN MKA-DASHBOARD-HOME';HOME_END=b'# END MKA-DASHBOARD-HOME'
HOME_BLOCK=HOME_START+b'\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$\nRewriteRule ^(?:index\\.(?:php|hhvm))?$ /admin/addons/dashboard/ [R=302,L,NE]\n</IfModule>\n'+HOME_END+b'\n'
def patch_home(source):
 clean=re.sub(re.escape(HOME_START)+b'.*?'+re.escape(HOME_END)+b'\\r?\\n?',b'',source,flags=re.S)
 return HOME_BLOCK+clean.lstrip(b'\\r\\n')
def patch(source):return re.sub(re.escape(START)+b'.*?'+re.escape(END)+b'\r?\n?',b'',source,flags=re.S).rstrip(b'\r\n')+b'\n'+BLOCK
def atomic(path,data):
 fd,name=tempfile.mkstemp(prefix='.admin-header-',dir=str(path.parent))
 try:
  with os.fdopen(fd,'wb')as output:output.write(data)
  if path.exists():
   stat=path.stat();os.chmod(name,stat.st_mode&0o7777);os.chown(name,stat.st_uid,stat.st_gid)
  else:os.chmod(name,0o644)
  os.replace(name,path)
 finally:
  if os.path.exists(name):os.unlink(name)
def main():
 admin,backup=map(Path,sys.argv[1:]);changes=[]
 if not (admin/'addons/dashboard/index.php').is_file():raise RuntimeError('Dashboard ausente; inicio preservado.')
 home=admin/'.htaccess'
 if home.is_symlink():raise RuntimeError('Configuracao de inicio simbolica preservada.')
 old=home.read_bytes()if home.exists()else None;changes.append((home,old,patch_home(old or b'')))
 for relative in ['addons/addon.js','scripts/mk-auth.js']:
  path=admin/relative
  if path.is_symlink()or any(p.is_symlink()for p in path.parents):raise RuntimeError('Loader simbolico preservado.')
  if not path.exists()and relative.startswith('scripts/'):continue
  old=path.read_bytes()if path.exists()else None;changes.append((path,old,patch(old or b'')))
 backup.mkdir(parents=True,exist_ok=True);backup.chmod(0o700);records=[];applied=[]
 try:
  for i,(path,old,new)in enumerate(changes):
   if old is not None:(backup/str(i)).write_bytes(old);(backup/str(i)).chmod(0o600)
   records.append({'path':str(path),'before':str(i)if old is not None else None,'installed_sha256':hashlib.sha256(new).hexdigest()})
   if(path.read_bytes()if path.exists()else None)!=old:raise RuntimeError('Loader alterado durante instalacao.')
   path.parent.mkdir(parents=True,exist_ok=True);atomic(path,new);applied.append((path,old))
  (backup/'files.json').write_text(json.dumps(records,indent=2))
 except Exception:
  for path,old in reversed(applied):
   if old is None:path.unlink()
   else:atomic(path,old)
  raise
 print('[applications] Cabecalho compartilhado integrado a todas as paginas administrativas nativas.')
if __name__=='__main__':main()

