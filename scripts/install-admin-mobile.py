#!/usr/bin/env python3
import re,sys,shutil,os,tempfile
from pathlib import Path
START=b'/* MKA-ADMIN-MOBILE-START */';END=b'/* MKA-ADMIN-MOBILE-END */'
BLOCK=START+b"\n;(function(){if(window.mkaAdminMobileLoaderVersion==='20261124-1')return;window.mkaAdminMobileLoaderVersion='20261124-1';window.mkaAdminMobileLoader=true;var s=document.createElement('script');s.src='/admin/addons/shared/admin_mobile.js?v=20261124-1';document.head.appendChild(s);})();\n"+END+b'\n'
def patch(source):
 source=re.sub(re.escape(START)+b'.*?'+re.escape(END)+b'\r?\n?',b'',source,flags=re.S)
 return source+(b'\n' if source and not source.endswith(b'\n') else b'')+BLOCK

def main():
 admin,backup=Path(sys.argv[1]),Path(sys.argv[2]);backup.mkdir(parents=True,exist_ok=True)
 for rel in ['addons/addon.js','scripts/mk-auth.js']:
  path=admin/rel
  if path.is_symlink():raise RuntimeError('Link simbolico preservado')
  if not path.exists() and rel.startswith('scripts/'):continue
  source=path.read_bytes() if path.exists() else b''
  if path.exists():shutil.copy2(str(path),str(backup/rel.replace('/','_')))
  path.parent.mkdir(parents=True,exist_ok=True);st=path.stat() if path.exists() else None
  fd,tmp=tempfile.mkstemp(prefix='.admin-mobile-',dir=str(path.parent));os.close(fd)
  try:
   Path(tmp).write_bytes(patch(source));os.chmod(tmp,(st.st_mode&0o777)if st else 0o644)
   if st:os.chown(tmp,st.st_uid,st.st_gid)
   os.replace(tmp,path)
  finally:
   if os.path.exists(tmp):os.unlink(tmp)
 print('[applications] Cabecalho e menu administrativo mobile integrados.')
if __name__=='__main__':main()
