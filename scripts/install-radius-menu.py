#!/usr/bin/env python3
"""Add a single Radius entry to the existing Applications menu, preserving its other entries."""
import shutil,sys
from pathlib import Path
MARKER='/* MKA-SUITE-RADIUS-APPLICATIONS-MENU */'
BLOCK=r'''
/* MKA-SUITE-RADIUS-APPLICATIONS-MENU */
;(function(){
 function addRadius(){
  var groups=Array.from(document.querySelectorAll('.navbar-start .has-dropdown'));
  function groupMenu(pattern){var group=groups.find(function(n){var label=n.querySelector('.navbar-link');return label&&pattern.test(label.textContent);});return group?group.querySelector('.navbar-dropdown'):null;}
  var applications=document.getElementById('menu_amsis')||groupMenu(/APLICATIVOS|ADDONS/i),provider=document.getElementById('menu_provedor')||groupMenu(/PROVEDOR/i);
  function ensure(menu){if(!menu)return false;if(Array.from(menu.querySelectorAll('a')).some(function(a){return /\/addons\/radius\/?(?:[?#]|$)/.test(a.getAttribute('href')||'');}))return true;var link=document.createElement('a');link.className='navbar-item';link.href='/admin/addons/radius/';link.textContent='LOG RADIUS';menu.appendChild(link);return true;}
  var appReady=ensure(applications),providerReady=ensure(provider);return appReady&&providerReady;
 }
 function start(){if(addRadius())return;var root=document.querySelector('.navbar-start')||document.body;if(!root||typeof MutationObserver==='undefined')return;var observer=new MutationObserver(function(){if(addRadius())observer.disconnect();});observer.observe(root,{childList:true,subtree:true});window.setTimeout(function(){observer.disconnect();},30000);}
 if(document.readyState==='complete')start();else window.addEventListener('load',start,{once:true});
})();
'''
def patch(text):
    if MARKER in text:
        start=text.index(MARKER);end=text.index('})();',start)+len('})();')
        return text[:start]+BLOCK.strip()+text[end:]
    return text.rstrip()+'\n'+BLOCK

def patch_bytes(source):
    # The owned block is ASCII. Do not decode/re-encode custom/native scripts:
    # legacy MK-AUTH installations may use Latin-1, CP1252 or mixed bytes.
    marker=MARKER.encode('ascii');block=BLOCK.strip().encode('ascii')
    if marker in source:
        start=source.index(marker);closing=source.index(b'})();',start)+len(b'})();')
        return source[:start]+block+source[closing:]
    return source+(b'\n' if source and not source.endswith(b'\n') else b'')+block+b'\n'
def install(admin,backup):
    backup=Path(backup)
    changes=[]
    # Native admin script is loaded independently of custom addon.js code.
    # A broken third-party addon must not prevent the Radius shortcut.
    for relative in ['addons/addon.js','scripts/mk-auth.js']:
        path=Path(admin)/relative
        if path.is_symlink():raise ValueError('Link simbólico preservado: '+str(path))
        if not path.exists() and relative.startswith('scripts/'):
            print('Aviso: carregador administrativo ausente; somente addon.js integrado.')
            continue
        old=path.read_bytes() if path.exists() else b'';new=patch_bytes(old)
        if new!=old:
            changes.append((path,relative,new))
    # Validate both scripts before modifying either one.
    for path,relative,new in changes:
        backup.mkdir(parents=True,exist_ok=True)
        if path.exists():shutil.copy2(path,backup/(relative.replace('/','_')))
        path.write_bytes(new)
    print('Atalhos LOG RADIUS nos menus Aplicativos e Provedor preparados, com carregamento administrativo independente.')
if __name__=='__main__':install(sys.argv[1],sys.argv[2])
