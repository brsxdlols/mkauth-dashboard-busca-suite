(function(){
 'use strict';
 const script=document.currentScript,reportUrl=script&&script.getAttribute('data-report-url');
 let monitor=null,previousFocus=null;
 window.mkaOpenClientMonitor=function(login,name){
  if(monitor)monitor.close();previousFocus=document.activeElement;
  const dialog=document.createElement('dialog'),header=document.createElement('header'),title=document.createElement('span'),close=document.createElement('button'),frame=document.createElement('iframe');
  dialog.className='mka-search-traffic-modal';dialog.setAttribute('aria-label','Monitor de tráfego de '+login);title.textContent=(name||login)+' · '+login;close.type='button';close.textContent='Fechar';frame.title='Monitor de tráfego de '+login;
  frame.src='/admin/addons/mapa-clientes/monitor/?embed=1&login='+encodeURIComponent(login);header.append(title,close);dialog.append(header,frame);document.body.append(dialog);monitor=dialog;
  close.addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>{frame.src='about:blank';dialog.remove();if(monitor===dialog)monitor=null;if(previousFocus&&previousFocus.isConnected)previousFocus.focus();});dialog.showModal();close.focus();return false;
 };
 document.addEventListener('click',function(event){
  const link=event.target.closest('a[href]');if(!link||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||event.button>0)return;
  const url=new URL(link.getAttribute('href'),document.baseURI);
  if(url.origin!==location.origin||!url.pathname.endsWith('/det_conn.php'))return;
  const login=url.searchParams.get('login');if(!login||!window.mkaClientConnectionLog)return;
  event.preventDefault();window.mkaClientConnectionLog(login,link,reportUrl);
 });
 window.addEventListener('pagehide',()=>{if(monitor)monitor.close();});
}());
