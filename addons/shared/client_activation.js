(function(){
'use strict';
if(window.mkaClientActivationLoaded)return;
window.mkaClientActivationLoaded=true;
document.addEventListener('click',function(event){
 var button=event.target.closest('[data-activate-client]');if(!button)return;event.preventDefault();
 if(document.querySelector('[data-activation-dialog]'))return;
 var login=button.getAttribute('data-activate-client'),raw=button.getAttribute('data-activate-url');if(!login||!raw)return;
 var url=new URL(raw,location.href);if(url.origin!==location.origin||!/^\/admin\/executar_ativacao\.(php|hhvm)$/.test(url.pathname)||url.searchParams.get('acao')!=='ativacao')return;
 function el(tag,text){var n=document.createElement(tag);if(text)n.textContent=text;return n;}
 var dialog=el('dialog');dialog.setAttribute('data-activation-dialog','');dialog.setAttribute('aria-labelledby','mka-activation-title');dialog.style.cssText='width:min(460px,92vw);padding:0;border:1px solid #c5e4d2;border-radius:14px;color:#203b30;background:white;box-shadow:0 20px 70px #132d4455;font:14px Arial,sans-serif';
 var style=el('style');style.textContent='dialog[data-activation-dialog]::backdrop{background:#18324d88}';dialog.append(style);
 var header=el('h2','Ativar cliente');header.id='mka-activation-title';header.style.cssText='margin:0;padding:18px 20px;background:#edf9f1;color:#16653d;font-size:20px';
 var content=el('div');content.style.cssText='padding:18px 20px';var name=el('strong',button.getAttribute('data-activate-name')||login);name.style.cssText='display:block;font-size:17px;color:#173c2b;overflow-wrap:anywhere';var user=el('p','Login: '+login);user.style.cssText='color:#60776b;overflow-wrap:anywhere';content.append(name,user,el('p','Deseja reativar este cadastro no MK-AUTH? Os títulos e pagamentos existentes serão mantidos.'));
 var actions=el('div');actions.style.cssText='display:flex;justify-content:flex-end;gap:10px;margin-top:20px';var cancel=el('button','Cancelar'),confirm=el('button','Ativar cliente');cancel.type=confirm.type='button';cancel.style.cssText='padding:10px 14px;border:1px solid #cad8d0;border-radius:8px;background:white;color:#365345;cursor:pointer';confirm.style.cssText='padding:10px 14px;border:0;border-radius:8px;background:#16964e;color:white;font-weight:700;cursor:pointer';actions.append(cancel,confirm);content.append(actions);dialog.append(header,content);
 var stopWatch=null;
 var previous=document.body.style.overflow;document.body.style.overflow='hidden';cancel.addEventListener('click',function(){dialog.close();});dialog.addEventListener('close',function(){if(stopWatch)stopWatch();document.body.style.overflow=previous;dialog.remove();button.focus();});
 confirm.addEventListener('click',function(){
  if(confirm.disabled)return;confirm.disabled=true;cancel.disabled=true;confirm.textContent='Ativando…';
  // Keep native redirects inside the frame; reload this exact search only after DB confirmation.
  var frame=el('iframe');frame.name='mka_activate_'+Date.now();frame.title='Resultado da ativação';frame.style.display='none';dialog.append(frame);
  stopWatch=window.mkaWatchClientAction(login,'active',function(){window.location.reload();},function(){confirm.textContent='Resultado não confirmado';cancel.disabled=false;cancel.textContent='Fechar';content.append(el('p','Não foi possível confirmar a ativação. Confira o cadastro antes de repetir a operação.'));});
  // Native activation, explicitly scoped to this one login.
  var form=el('form');form.method='POST';form.action=url.href;form.target=frame.name;var input=el('input');input.type='hidden';input.name='login[]';input.value=login;form.append(input);document.body.append(form);form.submit();
 });
 document.body.append(dialog);dialog.showModal();cancel.focus();
});
})();
