(function(){
 'use strict';
 let showValues=true,active=null,generation=0,timer=null,controller=null,busy=false,paused=false,saved=[];
 function el(tag,cls,text){const node=document.createElement(tag);if(cls)node.className=cls;if(text!==undefined)node.textContent=text;return node;}
 function sameLogin(a,b){return typeof a==='string'&&a.trim().toLowerCase()===String(b).trim().toLowerCase();}
 function stop(){closeClientLog();generation++;clearTimeout(timer);timer=null;if(controller)controller.abort();controller=null;busy=false;if(active&&active.panel)active.panel.remove();active=null;saved=[];}
 function mergeEvents(previous,rows,login){
  const merged=new Map();
  for(const row of [...previous,...(Array.isArray(rows)?rows:[])])if(row&&sameLogin(row.login,login))merged.set(row.event_key||row.line,row);
  const all=[...merged.values()];
  return all.filter(row=>row.source!=='accounting_start'||!all.some(other=>other.source==='radius'&&other.type==='conectados'&&Math.abs(other.event_time-row.event_time)<=30)).sort((a,b)=>(b.event_time||0)-(a.event_time||0)).slice(0,5);
 }
 let logModal=null,logFocus=null;
 function closeClientLog(){if(!logModal)return;const modal=logModal;logModal=null;modal.remove();if(logFocus&&logFocus.isConnected)logFocus.focus();logFocus=null;}
 function openClientLog(link,login){
  const url=new URL(link.getAttribute('href'),document.baseURI);if(url.origin!==location.origin||!url.pathname.endsWith('/relatorios_u.hhvm'))return;
  closeClientLog();logFocus=link;const modal=el('dialog','mka-client-log-modal'),header=el('header'),title=el('span','','Log do cliente — '+login),close=el('button','','Fechar'),frame=el('iframe');
  modal.setAttribute('aria-label','Log do cliente '+login);close.type='button';close.setAttribute('aria-label','Fechar log do cliente');frame.title='Log do cliente '+login;frame.src=url.href;
  header.append(title,close);modal.append(header,frame);document.body.append(modal);logModal=modal;
  close.addEventListener('click',()=>modal.close());modal.addEventListener('close',closeClientLog);modal.addEventListener('click',event=>{if(event.target===modal){const rect=modal.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)modal.close();}});modal.showModal();close.focus();
 }
 function decorateDetails(box){
  const popup=box.closest('.popup'),grid=popup&&popup.querySelector('.popup-grid');
  if(!grid)return;
  for(const link of grid.querySelectorAll('.client-log-btn'))if(!link._mkaLogModal){link._mkaLogModal=true;link.addEventListener('click',function(event){event.preventDefault();openClientLog(this,box.getAttribute('data-traffic-login')||'');});}
  if(grid.querySelector('.mka-details-toggle'))return;
  const heading=popup.querySelector('h3');
  if(heading){const label=el('b','','Nome'),name=el('span','',heading.textContent);label.setAttribute('data-info','Nome');name.setAttribute('data-info','Nome');grid.prepend(label,name);}
  const more=el('button','mka-details-toggle','Ver mais');more.type='button';more.setAttribute('aria-expanded','false');
  more.addEventListener('click',function(){const expanded=this.getAttribute('aria-expanded')!=='true';this.setAttribute('aria-expanded',String(expanded));this.textContent=expanded?'Ver menos':'Ver mais';grid.classList.toggle('mka-details-expanded',expanded);});grid.append(more);
 }
 function decorate(box){
  decorateDetails(box);
  if(box.querySelector('.mka-monitor-controls'))return;
  const controls=el('div','mka-monitor-controls'),keys=el('div','mka-traffic-keys');
  for(const [kind,text] of [['down','↓ DOWN · Download'],['up','↑ UP · Upload']]){
   const toggle=el('button','mka-traffic-'+kind,text);toggle.type='button';toggle.setAttribute('aria-pressed','true');toggle.title='Mostrar ou ocultar '+(kind==='down'?'Download':'Upload');
   toggle.addEventListener('click',function(){const visible=this.getAttribute('aria-pressed')!=='true';this.setAttribute('aria-pressed',String(visible));box.classList.toggle('mka-traffic-hide-'+kind,!visible);});keys.append(toggle);
  }
  showValues=true;
  const label=el('label'),check=el('input');check.type='checkbox';check.checked=true;
  label.append(check,el('span','','Mostrar valores no gráfico'));
  const collect=el('button','mka-traffic-pause','Pausar coleta');collect.type='button';collect.setAttribute('aria-pressed','false');collect.addEventListener('click',function(){if(!window.mkaMapTrafficControl)return;const stopped=window.mkaMapTrafficControl.pause(this.getAttribute('aria-pressed')!=='true');this.setAttribute('aria-pressed',String(stopped));this.textContent=stopped?'Retomar coleta':'Pausar coleta';box.classList.toggle('mka-traffic-paused',stopped);});controls.append(keys,label,collect);box.querySelector('.traffic-mini-chart').before(controls);
  box.classList.toggle('mka-traffic-hide-values',!showValues);
  check.addEventListener('change',function(){showValues=this.checked;box.classList.toggle('mka-traffic-hide-values',!showValues)});
  const legend=box.querySelector('.traffic-mini-legend');
  if(legend){
   const rx=legend.querySelector('.traffic-rx'),tx=legend.querySelector('.traffic-tx');
   if(rx&&tx){const down=el('span','mka-traffic-down','↓ Download '),up=el('span','mka-traffic-up','↑ Upload ');down.append(rx,document.createTextNode(' Mbps'));up.append(tx,document.createTextNode(' Mbps'));legend.replaceChildren(down,up);legend.classList.add('mka-monitor-legend');}
  }
 }
 function sync(box){
  if(!document.body.classList.contains('monitor-client')){if(active)stop();return;}
  box=box||document.querySelector('.monitor-client .traffic-mini');
  if(!box)return;
  const login=box.getAttribute('data-traffic-login');
  if(!login)return;
  decorate(box);
  if(active&&active.box===box&&active.login===login&&active.panel.isConnected)return;
  stop();paused=false;
  const panel=el('section','mka-radius-monitor'),heading=el('header','mka-radius-heading'),title=el('h2','','Log RADIUS — '+login),pause=el('button','','Pausar');
  pause.type='button';pause.setAttribute('aria-pressed','false');heading.append(title,pause);
  const note=el('p','mka-radius-note','Últimos 5 eventos do login · atualização a cada 3 segundos'),status=el('p','mka-radius-update','Carregando…'),current=el('div','mka-radius-current','Consultando estado atual…'),events=el('div','mka-radius-events');
  panel.append(heading,note,status,current,events);box.closest('.popup').append(panel);
  active={box,login,panel,status,current,events,first:true};
  pause.addEventListener('click',function(){paused=!paused;this.textContent=paused?'Retomar':'Pausar';this.setAttribute('aria-pressed',String(paused));clearTimeout(timer);if(paused){if(controller)controller.abort();status.textContent='Monitoramento pausado';}else poll();});
  poll();
 }
 async function poll(){
  if(!active||paused||document.hidden||busy)return;
  const job=active,version=generation;
  if(!job.panel.isConnected){stop();return;}
  busy=true;const request=new AbortController();controller=request;const timeout=setTimeout(()=>request.abort(),8000);
  try{
   const url='/admin/addons/radius/live.php?data=1&limit=100&login='+encodeURIComponent(job.login)+(job.first?'&history=1':'');
   const response=await fetch(url,{credentials:'same-origin',cache:'no-store',signal:request.signal});
   if(!response.ok)throw Error('Acesso ao Log RADIUS indisponível ('+response.status+')');
   const data=await response.json();if(generation!==version||active!==job||paused)return;
   job.first=false;saved=mergeEvents(saved,data.rows,job.login);
   const state=data.current&&sameLogin(data.current.login,job.login)?data.current:null;
   job.current.className='mka-radius-current'+(state&&state.state==='online'?' is-online':state&&state.state==='offline'?' is-offline':'');
   job.current.textContent=state?(state.state==='online'?'CONECTADO — ONLINE':state.state==='offline'?'DESCONECTADO — OFFLINE':'Estado atual indisponível')+'\n'+(state.name?state.name+' · ':'')+job.login+' · Consultado em '+(state.checked_at||data.updated_at||''):'Estado atual indisponível';
   job.events.replaceChildren();
   for(const row of saved){const article=el('article','mka-radius-event'+(row.type==='conectados'?' is-online':['erros','desconectados'].includes(row.type)?' is-error':'')),meta=el('div','mka-radius-meta');meta.append(el('span','',row.label||row.type),el('div','',(row.name?row.name+' · ':'')+job.login));article.append(meta,el('code','',row.line||''));job.events.append(article);}
   if(!saved.length)job.events.append(el('p','mka-radius-note','Nenhum evento localizado para este login.'));
   job.status.textContent=data.error||('Atualizado em '+(data.updated_at||new Date().toLocaleTimeString('pt-BR')));
  }catch(error){if(generation===version&&active===job&&!paused&&error.name!=='AbortError')job.status.textContent=error.message;}
  finally{clearTimeout(timeout);if(generation===version){busy=false;controller=null;if(active&&!paused&&!document.hidden)timer=setTimeout(poll,3000);}}
 }
 document.addEventListener('visibilitychange',function(){clearTimeout(timer);if(document.hidden){if(controller)controller.abort();}else if(!busy)poll();});
 window.addEventListener('pagehide',stop);
 window.mkaMapMonitor={sync,stop};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>sync());else sync();
}());
