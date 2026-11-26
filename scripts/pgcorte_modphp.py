"""Add scoped PHP-module configuration on legacy Apache installations."""
import os,re,tempfile
import urllib.request,urllib.error
from pathlib import Path
START=b'# BEGIN MKA-PGCORTE-MODPHP';END=b'# END MKA-PGCORTE-MODPHP'
HELPER='/usr/local/share/mkauth-pgcorte-dual/v1.3.1/native_hook.php'
def candidate(source,pattern):
 clean=re.sub(re.escape(START)+b'.*?'+re.escape(END)+b'\r?\n?',b'',source,flags=re.S)
 block=START+b'\n<FilesMatch "'+pattern.encode()+b'">\n'
 for module in ('php7_module','php_module'):
  block+=('<IfModule '+module+'>\nphp_value auto_prepend_file "'+HELPER+'"\n</IfModule>\n').encode()
 return clean.rstrip(b'\r\n')+b'\n'+block+b'</FilesMatch>\n'+END+b'\n'
def probe_admin():
 class NoRedirect(urllib.request.HTTPRedirectHandler):
  def redirect_request(self,*args):return None
 request=urllib.request.Request('http://127.0.0.1/admin/ramal_det.hhvm')
 try:
  response=urllib.request.build_opener(NoRedirect).open(request,timeout=15)
 except urllib.error.HTTPError as error:response=error
 with response:
  body=response.read(65536)
  if response.code>=500 or b'Failed opening required'in body or b'Permission denied'in body:
   raise RuntimeError('PHP recusou o helper PGCORTE; configuracao Apache revertida.')

def configure_modphp(backup,admin=Path('/opt/mk-auth/admin'),mkt=Path('/opt/mk-auth/mkt'),mods=Path('/etc/apache2/mods-enabled')):
 active=any('application/x-httpd-php' in p.read_text(errors='replace')for p in mods.glob('*.conf'))if mods.is_dir()else False
 if not active:return {'status':'not_used'}
 if not Path(HELPER).is_file():raise RuntimeError('Helper PGCORTE ausente; configuracao Apache preservada.')
 backup.mkdir(parents=True,exist_ok=True);backup.chmod(0o700);applied=[]
 try:
  for i,(root,pattern)in enumerate(((admin,r'^ramal_det\.hhvm$'),(mkt,r'^pgcorte(?:\.hhvm)?$'))):
   if not root.is_dir():continue
   target=root/'.htaccess'
   if target.is_symlink()or any(p.is_symlink()for p in target.parents):raise RuntimeError('Configuracao Apache simbolica preservada.')
   before=target.read_bytes()if target.exists()else b''
   clean=re.sub(re.escape(START)+b'.*?'+re.escape(END),b'',before,flags=re.S)
   outside=re.sub(rb'<Files(?:Match)?[^>]*>.*?</Files(?:Match)?>',b'',clean,flags=re.I|re.S)
   if re.search(rb'(?m)^\s*(?:php_value\s+auto_prepend_file|SetEnv\s+PHP_VALUE[^\n]*auto_prepend_file)',outside,re.I):raise RuntimeError('Prepend global personalizado; Apache preservado.')
   # Preserve any custom prepend for the same native endpoint.
   for section in re.findall(rb'<Files(?:Match)?[^>]*>.*?</Files(?:Match)?>',clean,re.I|re.S):
    if b'auto_prepend_file'in section and (b'ramal_det'in section or b'pgcorte'in section):raise RuntimeError('Prepend PGCORTE personalizado; Apache preservado.')
   after=candidate(before,pattern)
   if after==before:continue
   saved=backup/(str(i)+'.before');saved.write_bytes(before);saved.chmod(0o600)
   st=target.stat()if target.exists()else None
   fd,name=tempfile.mkstemp(prefix='.pgcorte-modphp-',dir=str(root))
   with os.fdopen(fd,'wb')as output:output.write(after)
   os.chmod(name,st.st_mode&0o777 if st else 0o644)
   if st:os.chown(name,st.st_uid,st.st_gid)
   os.replace(name,str(target));applied.append((target,before,st))
  probe_admin()
 except Exception:
  for target,before,st in reversed(applied):
   if st:target.write_bytes(before)
   else:target.unlink()
  raise
 return {'status':'configured','scope':['ramal_det.hhvm','pgcorte'],'helper':HELPER}
