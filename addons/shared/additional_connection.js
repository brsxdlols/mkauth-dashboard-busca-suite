(function(){
'use strict';
var dialog,content,title,frame,menu,back,status,trigger,card,saved,request,serial=0,active;
function el(tag,text){var n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}
function button(label,action){var b=el('button',label);b.type='button';b.addEventListener('click',action);return b;}
function init(){
 dialog=el('dialog');dialog.className='mka-additional-modal';dialog.setAttribute('aria-label','Conexão do adicional');
 var header=el('header');title=el('h2');back=button('Voltar às opções',showMenu);back.hidden=true;
 header.append(title,back,button('Fechar ×',function(){dialog.close();}));menu=el('section');menu.className='mka-additional-menu';status=el('p');status.setAttribute('role','status');content=el('div');content.className='mka-additional-content';
 frame=el('iframe');frame.title='Ações do adicional';frame.setAttribute('sandbox','allow-forms allow-scripts allow-same-origin allow-modals allow-downloads');frame.hidden=true;
 content.append(menu,status,frame);dialog.append(header,content);document.body.append(dialog);
 dialog.addEventListener('close',function(){serial++;if(request)request.abort();frame.src='about:blank';active=null;document.body.style.overflow=saved||'';if(trigger&&trigger.isConnected)trigger.focus({preventScroll:true});var old=card;window.setTimeout(function(){if(old&&(!dialog.open||old!==card))old.classList.remove('additional-client-selected');},2000);});
 frame.addEventListener('load',function(){
  try{var doc=frame.contentDocument;if(!doc||!active)return;doc.querySelectorAll('.navbar,#systopop,footer').forEach(function(n){n.hidden=true;});doc.querySelectorAll('form').forEach(function(n){n.target='_self';});
   frame.contentWindow.open=function(url){if(url)frame.contentWindow.location.href=url;return frame.contentWindow;};
   doc.addEventListener('keydown',function(e){if(e.key==='Escape'){e.preventDefault();e.stopPropagation();showMenu();}});
   if(active.view==='edit'&&active.blockPending){var id=active.blockPending;active.blockPending=null;var link=doc.getElementById(id);if(!link){status.textContent='Esta ação não está disponível no cadastro nativo.';return;}link.click();}
  }catch(e){status.textContent='Não foi possível carregar esta ação.';}
 });
}
function openFrame(label,url){active.view='native';title.textContent=label+' · '+active.data.additional.username;menu.hidden=true;status.textContent='';frame.hidden=false;back.hidden=false;frame.src=url;}
function showMenu(){if(!active)return;frame.hidden=true;frame.src='about:blank';back.hidden=true;menu.hidden=false;load(active.uuid);}
function render(data){
 active.data=data;var a=data.additional;title.textContent=a.nome||a.username;menu.replaceChildren();
 var summary=el('div');summary.className='mka-additional-summary';summary.append(el('strong',a.username),el('p',(a.bloqueado==='sim'?'Bloqueado':'Liberado')+' · Plano: '+(a.plano||'Não informado')),el('p','Titular: '+a.parent_login));menu.append(summary);
 var actions=el('div');actions.className='mka-additional-actions';
 if(data.can_edit)actions.append(button('Alterar cadastro',function(){openFrame('Alterar cadastro',data.edit_url);active.view='edit';}));
 actions.append(button('LOG RADIUS',function(){openFrame('LOG RADIUS','../radius/live.php?login='+encodeURIComponent(a.username));}),button('Últimas conexões',function(){openFrame('Últimas conexões','client_connections_popup.php?login='+encodeURIComponent(a.username));}),button('Diagnosticar / reparar',function(){openFrame('Diagnosticar / reparar','client_diagnostic.php?additional='+encodeURIComponent(active.uuid));}));
 if(data.can_edit)actions.append(button(a.bloqueado==='sim'?'Desbloquear adicional':'Bloquear adicional',function(){var id=a.bloqueado==='sim'?'link_desbloqueio':'link_bloqueio';openFrame(a.bloqueado==='sim'?'Desbloquear adicional':'Bloquear adicional',data.edit_url);active.view='edit';active.blockPending=id;}));
 menu.append(actions,el('p','As ações são aplicadas à conexão adicional. Use Alterar cadastro para acessar Dados, Conexão e Endereço.'));status.textContent='';
}
function load(uuid){
 if(request)request.abort();request=new AbortController();var version=++serial;status.textContent='Consultando adicional…';
 fetch('additional_connection_api.php?uuid='+encodeURIComponent(uuid),{credentials:'same-origin',cache:'no-store',signal:request.signal}).then(function(r){return r.json().then(function(d){if(!r.ok)throw Error(d.error||'Não foi possível consultar o adicional.');return d;});}).then(function(d){if(version===serial&&dialog.open&&active)render(d);}).catch(function(e){if(e.name!=='AbortError'&&version===serial)status.textContent=e.message;});
}
window.addEventListener('message',function(event){if(active&&event.origin===location.origin&&event.source===frame.contentWindow&&event.data&&event.data.type==='mka-content-modal-close')showMenu();});
document.addEventListener('click',function(event){var target=event.target.closest('[data-additional-uuid]');if(!target||event.button>0||event.ctrlKey||event.metaKey||event.altKey||event.shiftKey)return;event.preventDefault();if(!dialog)init();if(dialog.open)dialog.close();trigger=target;card=target.closest('.client-row');if(card)card.classList.add('additional-client-selected');saved=document.body.style.overflow;document.body.style.overflow='hidden';active={uuid:target.getAttribute('data-additional-uuid'),view:'menu'};frame.hidden=true;menu.hidden=false;menu.replaceChildren();back.hidden=true;title.textContent='Conexão do adicional';dialog.showModal();load(active.uuid);});
}());
