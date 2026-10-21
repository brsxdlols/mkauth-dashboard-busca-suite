#!/usr/bin/env python3
"""Add a single Radius entry to the existing Applications menu, preserving its other entries."""
import shutil,sys
from pathlib import Path
MARKER='/* MKA-SUITE-RADIUS-APPLICATIONS-MENU */'
BLOCK=r'''
/* MKA-SUITE-RADIUS-APPLICATIONS-MENU */
(function(){
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
def install(admin,backup):
    path=Path(admin)/'addons/addon.js';backup=Path(backup)
    if path.is_symlink():raise ValueError('addon.js é link simbólico; preservado.')
    old=path.read_text(encoding='utf-8') if path.exists() else '';new=patch(old)
    if new!=old:
        backup.mkdir(parents=True,exist_ok=True)
        if path.exists():shutil.copy2(path,backup/'addon.js')
        path.write_bytes(new.encode('utf-8'))
    print('Atalhos LOG RADIUS nos menus Aplicativos e Provedor preparados.')
if __name__=='__main__':install(sys.argv[1],sys.argv[2])
