(function(){'use strict';if(window.mkaAdminMobileLoaded)return;window.mkaAdminMobileLoaded=true;
document.documentElement.classList.add('mka-mobile-shell');
function css(id,href){if(document.getElementById(id))return;const link=document.createElement('link');link.id=id;link.rel='stylesheet';link.href=href;document.head.append(link);}
css('mka-admin-mobile-css','/admin/addons/shared/admin_mobile.css?v=20261122-1');
if(![...document.querySelectorAll('link[rel="stylesheet"]')].some(n=>/bi-icons\.css/.test(n.href)))css('mka-admin-icons','/admin/estilos/bi-icons.css');
function bind(){document.querySelectorAll('nav.navbar.is-fixed-top').forEach(nav=>{if(getComputedStyle(nav).display==='none'||nav.dataset.mkaMobileBound)return;const opener=nav.querySelector('#navbar-mka,.navbar-burger'),menu=nav.querySelector('.navbar-menu');if(!opener||!menu)return;nav.dataset.mkaMobileBound='1';opener.setAttribute('role','button');opener.tabIndex=0;opener.setAttribute('aria-label','Abrir menu principal');opener.setAttribute('aria-controls',menu.id);opener.setAttribute('aria-expanded','false');
 const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('aria-hidden','true');const path=document.createElementNS(svg.namespaceURI,'path');path.setAttribute('d','M4 6h16M4 12h16M4 18h16');svg.append(path);opener.append(svg);
 function toggle(value){menu.classList.toggle('is-active',value);opener.classList.toggle('is-active',value);opener.setAttribute('aria-expanded',String(value));}
 nav.addEventListener('click',e=>{if(innerWidth>1023)return;if(opener.contains(e.target)){e.preventDefault();e.stopImmediatePropagation();toggle(!menu.classList.contains('is-active'));return;}const link=e.target.closest('.navbar-link');if(link&&link.parentElement.classList.contains('has-dropdown')){e.preventDefault();e.stopImmediatePropagation();const open=link.parentElement.classList.toggle('is-active');link.setAttribute('aria-expanded',String(open));}},true);
 nav.addEventListener('keydown',e=>{if(innerWidth>1023)return;if((e.key==='Enter'||e.key===' ')&&e.target===opener){e.preventDefault();opener.click();}if(e.key==='Escape'&&menu.classList.contains('is-active')){e.preventDefault();toggle(false);opener.focus();}});
});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',bind);else bind();window.addEventListener('load',bind);
})();
