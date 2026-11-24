(function(){'use strict';if(window.mkaAdminMobileVersion&&window.mkaAdminMobileVersion>='20261124-1')return;window.mkaAdminMobileVersion='20261124-1';window.mkaAdminMobileLoaded=true;
document.documentElement.classList.add('mka-mobile-shell');
var menuVersion='nova',preview=new URLSearchParams(location.search).get('preview');
try{var cached=localStorage.getItem('mka_mobile_menu_version');if(cached==='legada'||cached==='nova')menuVersion=cached;}catch(e){}
function applyMenuVersion(value,remember){if(value!=='nova'&&value!=='legada')return;var changed=menuVersion!==value;menuVersion=value;document.documentElement.classList.toggle('mka-mobile-menu-legacy',value==='legada');document.documentElement.classList.toggle('mka-mobile-menu-new',value==='nova');
 if(changed&&innerWidth<=1023)document.querySelectorAll('nav.navbar .has-dropdown').forEach(n=>{n.classList.remove('is-active');var link=n.querySelector('.navbar-link');if(link)link.setAttribute('aria-expanded',String(value==='legada'));});
 if(remember)try{localStorage.setItem('mka_mobile_menu_version',value);}catch(e){}
}
applyMenuVersion(menuVersion,false);
function css(id,href){const existing=document.getElementById(id);if(existing){if(existing.getAttribute('href')!==href)existing.setAttribute('href',href);return;}const link=document.createElement('link');link.id=id;link.rel='stylesheet';link.href=href;document.head.append(link);}
css('mka-admin-mobile-css','/admin/addons/shared/admin_mobile.css?v=20261124-1');
if(![...document.querySelectorAll('link[rel="stylesheet"]')].some(n=>/bi-icons\.css/.test(n.href)))css('mka-admin-icons','/admin/estilos/bi-icons.css');
function bind(){document.querySelectorAll('nav.navbar.is-fixed-top').forEach(nav=>{if(getComputedStyle(nav).display==='none'||nav.dataset.mkaMobileVersion==='20261124-1')return;const opener=nav.querySelector('#navbar-mka,.navbar-burger'),menu=nav.querySelector('.navbar-menu');if(!opener||!menu)return;nav.dataset.mkaMobileBound='1';nav.dataset.mkaMobileVersion='20261124-1';opener.setAttribute('role','button');opener.tabIndex=0;opener.setAttribute('aria-label','Abrir menu principal');opener.setAttribute('aria-controls',menu.id);opener.setAttribute('aria-expanded',String(menu.classList.contains('is-active')));
 nav.querySelectorAll('.has-dropdown>.navbar-link').forEach(link=>link.setAttribute('aria-expanded',String((innerWidth<=1023&&menuVersion==='legada')||link.parentElement.classList.contains('is-active'))));
 if(!opener.querySelector('svg')){const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('aria-hidden','true');const path=document.createElementNS(svg.namespaceURI,'path');path.setAttribute('d','M4 6h16M4 12h16M4 18h16');svg.append(path);opener.append(svg);}
 function toggle(value){menu.classList.toggle('is-active',value);opener.classList.toggle('is-active',value);opener.setAttribute('aria-expanded',String(value));}
 if(nav.mkaMobileHandlers){window.removeEventListener('click',nav.mkaMobileHandlers.click,true);window.removeEventListener('keydown',nav.mkaMobileHandlers.keydown,true);}
 const onClick=e=>{if(innerWidth>1023||!e.target||e.target.nodeType!==1||!nav.contains(e.target))return;if(opener.contains(e.target)){e.preventDefault();e.stopImmediatePropagation();toggle(!menu.classList.contains('is-active'));return;}const link=e.target.closest('.navbar-link');if(link&&link.parentElement.classList.contains('has-dropdown')){e.preventDefault();e.stopImmediatePropagation();if(menuVersion==='legada'){link.setAttribute('aria-expanded','true');return;}const open=link.parentElement.classList.toggle('is-active');link.setAttribute('aria-expanded',String(open));}};
 const onKey=e=>{if(innerWidth>1023||!e.target||e.target.nodeType!==1||!nav.contains(e.target))return;if((e.key==='Enter'||e.key===' ')&&e.target===opener){e.preventDefault();e.stopImmediatePropagation();opener.click();}if(e.key==='Escape'&&menu.classList.contains('is-active')){e.preventDefault();e.stopImmediatePropagation();toggle(false);opener.focus();}};
 nav.mkaMobileHandlers={click:onClick,keydown:onKey};window.addEventListener('click',onClick,true);window.addEventListener('keydown',onKey,true);
});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',bind);else bind();window.addEventListener('load',bind);
function loadMenuPreferences(){if(!document.querySelector('nav.navbar.is-fixed-top'))return;var preferences='/admin/addons/shared/mobile_menu_preferences.php';if(preview&&/^[a-f0-9]{32}$/.test(preview))preferences+='?preview='+encodeURIComponent(preview);
fetch(preferences,{credentials:'same-origin',cache:'no-store'}).then(r=>{if(!r.ok)throw Error('Menu indisponível');return r.json();}).then(data=>applyMenuVersion(data.version,!preview)).catch(()=>{/* Keep the last mode or the default Nova when offline. */});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',loadMenuPreferences,{once:true});else loadMenuPreferences();
})();
