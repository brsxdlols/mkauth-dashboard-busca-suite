(function(){
'use strict';
if(window.mkaIpConflictsLoaded)return;window.mkaIpConflictsLoaded=true;
var dialog,list,status,signature='',dismissed='',waiting=false,oldOverflow='';
function node(tag,text){var n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}
function init(){dialog=node('dialog');dialog.className='mka-ip-conflicts';dialog.setAttribute('aria-label','Alerta crítico: IP duplicado');var head=node('header'),title=node('h2','Atenção: IP duplicado em conexões ativas'),close=node('button','Fechar ×');close.type='button';head.append(title,close);status=node('p','Dois ou mais logins estão usando o mesmo IP no mesmo concentrador. Analise os cadastros e confirme as sessões no MikroTik antes de corrigir.');list=node('div');list.className='mka-ip-conflicts-list';dialog.append(head,status,list);document.body.append(dialog);dialog.addEventListener('cancel',function(e){e.preventDefault();});close.addEventListener('click',dismiss);dialog.addEventListener('close',function(){document.body.style.overflow=oldOverflow;});}
function dismiss(){dismissed=signature;if(dialog&&dialog.open)dialog.close();}
function render(groups){
 if(!dialog)init();list.replaceChildren();
 groups.forEach(function(g){var card=node('section');card.append(node('h3','IP '+g.ip+' · Concentrador '+g.nas));g.clients.forEach(function(c){var row=node('div');row.className='mka-ip-conflict-client';var detail=node('div');detail.append(node('strong',c.name||c.login),node('p',c.login+(c.additional?' · Adicional':'')+' · '+(c.fixed_matches?'IP fixado no cadastro':c.fixed_ip&&c.fixed_ip!=='0.0.0.0'?'IP do cadastro: '+c.fixed_ip:'Sem IP fixado no cadastro')));var link=node('a','Analisar cliente');link.href='/admin/addons/busca_inteligente/index.php?busca='+encodeURIComponent(c.login);link.addEventListener('click',dismiss);row.append(detail,link);card.append(row);});list.append(card);});
 if(!dialog.open){oldOverflow=document.body.style.overflow;document.body.style.overflow='hidden';dialog.showModal();}
}
async function check(){if(waiting||document.hidden)return;waiting=true;try{var r=await fetch('/admin/addons/dashboard/ip_conflicts.php',{credentials:'same-origin',cache:'no-store'});if(!r.ok)return;var data=await r.json(),groups=data.conflicts||[];var next=JSON.stringify(groups.map(function(g){return [g.nas,g.ip,g.clients.map(function(c){return c.login;})];}));if(!groups.length){signature='';dismissed='';return;}if(next===signature)return;signature=next;if(signature!==dismissed)render(groups);}catch(e){}finally{waiting=false;}}
check();window.setInterval(check,60000);document.addEventListener('visibilitychange',check);
}());
