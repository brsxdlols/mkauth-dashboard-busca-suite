(function(){
 'use strict';
 var active=null;
 function boxes(){return Array.from(document.querySelectorAll('.login_select[data-client-uuid]'));}
 function update(){
  var total=boxes().filter(function(b){return b.checked;}).length;
  document.querySelectorAll('[data-bulk-selected]').forEach(function(n){n.textContent=total+' selecionado'+(total===1?'':'s');});
 }
 function selected(action){
  var seen=new Set();
  return boxes().filter(function(b){
   var uuid=b.getAttribute('data-client-uuid');
   if(!b.checked||!uuid||!/^[A-Za-z0-9-]{16,64}$/.test(uuid)||seen.has(uuid))return false;
   seen.add(uuid);return true;
  }).map(function(b){return {uuid:b.getAttribute('data-client-uuid'),name:b.getAttribute('data-client-name')||b.value,login:b.value};});
 }
 function node(tag,text){var n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}
 async function readJson(response){
  var data;try{data=await response.json();}catch(e){throw Error('A sessão expirou ou houve uma resposta inesperada.');}
  if(!response.ok||!data.ok)throw Error(data.message||'Operação não autorizada ou resultado não confirmado.');
  return data;
 }
 async function runOne(client,action,reason,native){
  var audit='client_manual_block_audit.php',get=await readJson(await fetch(audit+'?uuid='+encodeURIComponent(client.uuid),{credentials:'same-origin',cache:'no-store'}));
  var expected=action==='bloqueio';
  if(get.blocked===expected)return 'unchanged';
  var body=new URLSearchParams({uuid:client.uuid,action:action,reason:reason,csrf:get.csrf});
  function post(){return fetch(audit,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body.toString()}).then(readJson);}
  await post();
  var url=new URL(native.href);url.searchParams.set('acao',action);url.searchParams.set('uuid_cliente',client.uuid);
  // Same native endpoint as the individual action. Never retry a mutation.
  var response=await fetch(url.href,{credentials:'same-origin',cache:'no-store'});
  if(!response.ok)throw Error('Falha na ação nativa. Confira o cadastro antes de repetir.');
  var text=await response.text();
  if(/acesso negado/i.test(text))throw Error('O MK-AUTH recusou a operação nativa.');
  body.set('phase','confirm');await post();return 'changed';
 }
 function open(button){
  if(active)return;
  var action=button.getAttribute('data-bulk-client-block');
  if(action!=='bloqueio'&&action!=='desbloqueio')return;
  var native=new URL(button.getAttribute('data-native-block-url'),location.href);
  if(native.origin!==location.origin||!/^\/admin\/executar_bloqueio\.(?:php|hhvm)$/.test(native.pathname))return;
  var clients=selected(action),unlock=action==='desbloqueio',dialog=node('dialog');
  dialog.className='mka-bulk-block-dialog'+(unlock?' is-unlock':'');dialog.setAttribute('aria-labelledby','mka-bulk-block-title');
  var header=node('h2',unlock?'Desbloquear clientes selecionados':'Bloquear clientes selecionados');header.id='mka-bulk-block-title';
  var content=node('div');content.className='mka-bulk-block-content';
  var summary=node('p',clients.length+' cliente(s) marcado(s) nesta página. A ação usa o procedimento nativo do MK-AUTH.');summary.className='mka-bulk-summary';
  var warning=node('p',unlock?'Se ainda houver títulos vencidos e a regra de corte se aplicar, o MK-AUTH poderá bloquear novamente. Esta ação não concede prazo de confiança.':'Se o financeiro permitir o desbloqueio automático, o MK-AUTH poderá liberar novamente estes clientes. Esta ação não cria um bloqueio permanente.');warning.className='mka-bulk-warning';
  var preserve=node('p','Títulos, pagamentos e regras automáticas do sistema serão mantidos. Quem já estiver no estado solicitado será ignorado.');
  var names=node('ul');names.className='mka-bulk-client-list';clients.forEach(function(c){names.append(node('li',c.name+' · '+c.login));});
  var label=node('label','Motivo da operação');label.htmlFor='mka-bulk-reason';
  var reason=node('textarea');reason.id='mka-bulk-reason';reason.maxLength=2000;reason.rows=3;reason.required=true;
  var status=node('p',clients.length?'Confira a seleção e informe o motivo para continuar.':'Marque pelo menos um cliente na lista antes de continuar.');status.setAttribute('role','status');status.setAttribute('aria-live','polite');
  var actions=node('div');actions.className='mka-bulk-actions';
  var cancel=node('button','Cancelar'),confirm=node('button',unlock?'Confirmar desbloqueio':'Confirmar bloqueio');cancel.type=confirm.type='button';confirm.className='mka-bulk-confirm';confirm.disabled=!clients.length;
  actions.append(cancel,confirm);content.append(summary,warning,preserve,names,label,reason,status,actions);dialog.append(header,content);
  var running=false,reload=false,previous=document.body.style.overflow;
  function close(){if(!running)dialog.close();}
  cancel.addEventListener('click',function(){if(reload){location.reload();return;}close();});
  dialog.addEventListener('cancel',function(e){if(running)e.preventDefault();});
  dialog.addEventListener('close',function(){document.body.style.overflow=previous;dialog.remove();active=null;if(reload)location.reload();else button.focus();});
  confirm.addEventListener('click',async function(){
   if(running||!clients.length||confirm.disabled)return;
   if(!reason.value.trim()){reason.reportValidity();reason.focus();return;}
   running=true;confirm.disabled=true;cancel.disabled=true;reason.disabled=true;confirm.textContent='Processando…';
   var changed=0,unchanged=0,index=0;
   try{
    for(index=0;index<clients.length;index++){
     status.textContent='Processando '+(index+1)+' de '+clients.length+': '+clients[index].name;
     var result=await runOne(clients[index],action,reason.value.trim(),native);
     if(result==='changed')changed++;else unchanged++;
    }
    reload=true;status.textContent='Concluído: '+changed+' alterado(s) e '+unchanged+' já no estado solicitado.';
    // Stay on this exact filter, page and search after confirmed completion.
    location.reload();
   }catch(error){
    reload=true;status.textContent='Lote interrompido em '+clients[index].name+'. '+changed+' alteração(ões) confirmada(s), '+unchanged+' já no estado solicitado. '+error.message+' Os demais não foram processados. Não houve repetição automática.';
    confirm.hidden=true;cancel.textContent='Atualizar lista';
   }finally{running=false;cancel.disabled=false;}
  });
  active=dialog;document.body.append(dialog);document.body.style.overflow='hidden';dialog.showModal();(clients.length?reason:cancel).focus();
 }
 document.addEventListener('click',function(event){
  var select=event.target.closest('[data-bulk-select]');
  if(select){event.preventDefault();var all=boxes(),value=all.some(function(b){return !b.checked;});all.forEach(function(b){b.checked=value;});var master=document.getElementById('selectall');if(master){master.checked=value;master.indeterminate=false;}update();return;}
  var button=event.target.closest('[data-bulk-client-block]');if(button){event.preventDefault();open(button);}
 });
 document.addEventListener('change',function(){update();});
 update();
}());
