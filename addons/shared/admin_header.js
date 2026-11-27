(function(){'use strict';
const version='20261127-1';if(window.mkaAdminHeaderVersion===version)return;window.mkaAdminHeaderVersion=version;
if(typeof location!=='undefined'&&/\/admin\/ramal_alt\.hhvm$/.test(location.pathname)){const script=document.createElement('script');script.src='/admin/addons/shared/ramal_api_password.js?v=20261127-1';script.defer=true;document.head.append(script);}
const styleId='mka-admin-header-style';if(!document.getElementById(styleId)){const link=document.createElement('link');link.id=styleId;link.rel='stylesheet';link.href='/admin/addons/shared/admin_header.css?v='+version;document.head.append(link);}
let state=null,busy=false,sessionUrl='/admin/addons/dashboard/web_sessions.php';
function element(tag,text){const node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;}
function icon(name){const node=element('i');node.className=name;node.setAttribute('aria-hidden','true');return node;}
function paint(data){if(!state||!data)return;if(data.session_url)sessionUrl=data.session_url;state.users.title=String(data.users)+' usuários distintos em '+String(data.sessions)+' sessões web.';state.mine.title='Sessões abertas somente pelo usuário atual.';state.users.textContent=String(data.sessions).padStart(2,'0');state.mine.textContent=String(data.my_sessions).padStart(2,'0');}
function open(view){window.open(sessionUrl+'?scope='+(view==='users'?'all':'mine')+'&v=20261127-1','_blank','width=500, height=650, scrollbars=YES');}
function actionRow(label,kind,iconName,counter){const row=element('span');row.className='mka-admin-session-row';row.dataset.mkaSessionView=kind;row.setAttribute('role','button');row.tabIndex=0;row.append(icon(iconName),element('strong',label),counter);row.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();row.click();}});return row;}
function homeLink(link){link.href='/admin/addons/dashboard/';link.title='Voltar ao início';link.setAttribute('aria-label','Voltar ao início');link.removeAttribute('onclick');}
function mountLogo(root){
 const logo=root&&root.querySelector('.level-left img');
 if(logo){let link=logo.closest('a');if(!link){link=element('a');logo.parentNode.insertBefore(link,logo);link.append(logo);}link.classList.add('mka-admin-logo-home');homeLink(link);}
 if(typeof document.querySelectorAll==='function')for(const link of document.querySelectorAll('.navbar-brand>a.navbar-item'))if(link.querySelector('img'))homeLink(link);
}
function mount(){
 const root=document.getElementById('systopo');mountLogo(root);if(!root)return false;root.classList.add('mka-admin-header');
 if(root.dataset.mkaAdminHeaderBound)return true;
 const opener=root.querySelector('#run_open_sessoes'),old=root.querySelector('#UUE_SESSOES');if(!opener||!old)return false;
 root.dataset.mkaAdminHeaderBound='1';
 const users=element('span','…'),mine=element('span','…');users.id='mkaDashboardWebSessions';mine.id='mkaDashboardWebSessionsTotal';
 users.title='Total de sessões web de todos os usuários do painel.';mine.title='Somente as sessões do usuário atual.';
 opener.classList.add('mka-admin-session-links');opener.replaceChildren(actionRow('Conexões de usuários:','users','bi-people-fill',users),actionRow('Minhas sessões:','mine','bi-pc-display-horizontal',mine));
 opener.addEventListener('click',event=>{const row=event.target.closest('[data-mka-session-view]');if(!row)return;event.preventDefault();event.stopImmediatePropagation();open(row.dataset.mkaSessionView);},true);
 state={users:users,mine:mine};paint(window.mkaAdminHeaderInitialCounts);refresh();return true;
}
async function refresh(){if(!state||busy||document.hidden)return;busy=true;const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),8000);try{const response=await fetch('/admin/addons/dashboard/web_session_counts.php',{credentials:'same-origin',cache:'no-store',headers:{'X-MKA-Session-Heartbeat':'1'},signal:controller.signal});if(response.ok){const data=await response.json();if(Number.isInteger(data.users)&&Number.isInteger(data.my_sessions))paint(data);}}catch(error){/* Preserve the last successful count. */}finally{clearTimeout(timer);busy=false;}}
function start(){if(mount())return;const observer=new MutationObserver(()=>{if(mount())observer.disconnect();});if(document.body)observer.observe(document.body,{childList:true,subtree:true});setTimeout(()=>observer.disconnect(),30000);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
window.addEventListener('load',mount);window.addEventListener('focus',refresh);setInterval(refresh,30000);
})();


