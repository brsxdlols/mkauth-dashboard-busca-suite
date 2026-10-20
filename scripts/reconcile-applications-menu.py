#!/usr/bin/env python3
"""Reconcile only the verified legacy Applications injector; preserve custom menus."""
import hashlib, re, shutil, sys
from pathlib import Path
BASELINE = '91358e9b8d9be1e4cb33774f759a139438b92c9dace8027c2d6a03d9d6e73518'
MARKER = '/* MKA-SUITE-APPLICATIONS-REUSE-V1 */'
def patch(source):
    text=source.decode('utf-8').replace('\r','')
    if MARKER in text:return source
    if hashlib.sha256(text.encode()).hexdigest()!=BASELINE:return None
    match=re.search(r"\$\('\.navbar-start'\)\.prepend\(`([\s\S]*?)`\);",text)
    if not match:raise ValueError('Unexpected legacy menu structure')
    body=MARKER+r"""
(function(){
 function integrate(){
  var nav=document.querySelector('.navbar-start');if(!nav)return;
  var holder=document.createElement('div');holder.innerHTML=`LEGACY_HTML`;
  var created=holder.firstElementChild;
  var existing=Array.from(nav.children).find(function(node){
   var label=node.querySelector('.navbar-link');return label&&label.textContent.toUpperCase().includes('APLICATIVOS');
  });
  if(!existing){nav.prepend(created);return;}
  var menu=existing.querySelector('.navbar-dropdown');if(!menu)return;
  function route(a){var value=(a.getAttribute('href')||'')+' '+(a.getAttribute('onclick')||'');var m=value.match(/addons\/[a-zA-Z0-9_./-]+/);return m?m[0].replace(/\/+$/,''):null;}
  var known=new Set(Array.from(menu.querySelectorAll('a')).map(route).filter(Boolean));
  created.querySelectorAll('.navbar-dropdown a').forEach(function(a){var key=route(a);if(key&&!known.has(key)){menu.appendChild(a);known.add(key);}});
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',integrate);else integrate();
})();
""".replace('LEGACY_HTML',match.group(1))
    return (text[:match.start()]+body+text[match.end():]).encode('utf-8')
def install(admin,backup):
    file=Path(admin)/'addons/addon_aplicativos.js'
    if not file.is_file() or file.is_symlink():return 'absent-or-symlink-preserved'
    old=file.read_bytes();new=patch(old)
    if new is None:return 'unknown-version-preserved'
    if new==old:return 'already-applied'
    backup=Path(backup);backup.mkdir(parents=True,exist_ok=True)
    saved=backup/'addon_aplicativos.js'
    if saved.exists():raise ValueError('Backup already exists')
    shutil.copy2(file,saved)
    file.write_bytes(new)
    return 'reuses-existing-menu'
if __name__=='__main__':print('[applications]',install(sys.argv[1],sys.argv[2]))
