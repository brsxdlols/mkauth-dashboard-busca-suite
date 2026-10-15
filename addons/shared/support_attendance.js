(function(){
 'use strict';
 const endpoint='/admin/addons/busca_inteligente/attendance_api.php';
 let modal,form,clientInput,results,client=null,csrf='',requestKey='',busy=false,lookup=null,lookupTimer=null,notice,choices,history,historySequence=0,current=null;
 const labels={open:'Em atendimento',resolved:'Resolvido',finished:'Finalizado sem OS',service:'Encaminhado para OS'};
 function node(tag,cls,text){const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;}
 function button(text,handler,cls){const el=node('button',cls,text);el.type='button';el.onclick=handler;return el;}
 async function api(url,options){const response=await fetch(url,{credentials:'same-origin',cache:'no-store',...options});let data;try{data=await response.json();}catch(e){throw Error('Não foi possível acessar o registro. Confira sua sessão.');}if(!response.ok)throw Error(data.error||'Não foi possível concluir.');if(data.csrf)csrf=data.csrf;return data;}
 function date(value){if(!value)return '';return String(value).replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}).*/,'$3/$2/$1 $4');}
 function newKey(){return Date.now().toString(36)+'_'+Math.random().toString(36).slice(2)+'_'+Math.random().toString(36).slice(2);}
 function title(text){modal.querySelector('h2').textContent=text;}
 function top(){modal.querySelector('.attendance-body').scrollTop=0;}
 function renderRecord(record,message){
  current=record;client={uuid_cliente:record.client_uuid,nome:record.client_name,login:record.login};form.hidden=true;choices.hidden=true;results.replaceChildren();title('Atendimento #'+record.id);
  notice.replaceChildren(node('h3','attendance-result '+(record.outcome==='resolved'?'is-resolved':''),message||('Atendimento #'+record.id+' · '+labels[record.outcome])));
  notice.append(node('p','',record.client_name+' · '+record.login),node('strong','',record.subject),node('p','attendance-notes',record.notes),node('p','attendance-hint','Registrado em '+date(record.created_at)+' por '+record.operator+(record.updated_at?' · Atualizado em '+date(record.updated_at)+' por '+record.updated_by:'')));
  const actions=node('div','attendance-result-actions');actions.append(button('Voltar aos chamados',()=>modal.close(),'attendance-primary'));
  if(record.can_edit){actions.append(button('Editar registro',()=>edit(record)));actions.append(button(record.outcome==='open'?'Continuar atendimento':'Reabrir atendimento',()=>record.outcome==='open'?edit(record):mutate('reopen',record)));}
  if(record.outcome==='service'){const link=node('a','attendance-primary','Abrir ordem de serviço');link.href=record.service_url;link.target='_blank';link.rel='noopener';actions.append(link);notice.append(node('p','','Revise e grave a ordem de serviço no formulário nativo.'));}
  notice.append(actions);top();actions.querySelector('button').focus();loadHistory();
 }
 function edit(record){
  current=record;client={uuid_cliente:record.client_uuid,nome:record.client_name,login:record.login};form.hidden=false;notice.replaceChildren();choices.hidden=true;results.replaceChildren();
  clientInput.value=record.client_name+' · '+record.login;clientInput.readOnly=true;clientInput.setCustomValidity('');form.elements.subject.value=record.subject;form.elements.notes.value=record.notes;
  form.querySelector('[type=submit]').textContent=record.outcome==='open'?'Concluir atendimento':'Salvar alterações';form.querySelector('[data-save-draft]').hidden=record.outcome!=='open';
  form.querySelector('.attendance-hint').textContent=record.outcome==='open'?'Salve o relato ou conclua escolhendo o resultado.':'A edição preserva o resultado atual: '+labels[record.outcome]+'.';title('Editar atendimento #'+record.id);top();form.elements.notes.focus();
 }
 async function loadHistory(){
  const sequence=++historySequence;history.replaceChildren(node('p','','Carregando histórico…'));
  try{const data=await api(endpoint+'?action=history'+(client?'&uuid='+encodeURIComponent(client.uuid_cliente):''));if(sequence!==historySequence)return;history.replaceChildren();
   if(!data.records.length)history.append(node('p','','Nenhum atendimento registrado neste histórico.'));
   for(const record of data.records){const item=node('details','attendance-record'),summary=node('summary','','#'+record.id+' · '+record.client_name+' · '+labels[record.outcome]);item.append(summary,node('p','',date(record.created_at)+' · '+record.operator),node('strong','',record.subject),node('p','attendance-notes',record.notes));
    const actions=node('div','attendance-result-actions');actions.append(button('Ver registro',()=>renderRecord(record)));if(record.can_edit){actions.append(button('Editar',()=>edit(record)));actions.append(button(record.outcome==='open'?'Continuar':'Reabrir',()=>record.outcome==='open'?edit(record):mutate('reopen',record)));}item.append(actions);history.append(item);}
  }catch(error){if(sequence===historySequence)history.replaceChildren(node('p','',error.message));}
 }
 function chooseClient(value){client=value;clientInput.value=value.nome+' · '+value.login;results.replaceChildren();clientInput.setCustomValidity('');loadHistory();}
 async function search(q,exact){
  if(lookup)lookup.abort();lookup=new AbortController();const controller=lookup;
  try{const data=await api(endpoint+'?action=clients&q='+encodeURIComponent(q),{signal:controller.signal});if(controller!==lookup)return;
   const selected=exact&&data.clients.find(item=>item.login===exact);if(selected){chooseClient(selected);return;}results.replaceChildren();if(!data.clients.length)results.append(node('p','','Nenhum cliente permitido encontrado.'));
   for(const value of data.clients)results.append(button(value.nome+' · '+value.login,()=>chooseClient(value)));
  }catch(error){if(error.name!=='AbortError')notice.textContent=error.message;}
 }
 function setBusy(value){busy=value;for(const el of modal.querySelectorAll('button:not([data-close])'))el.disabled=value;}
 async function mutate(action,record,outcome){
  if(busy)return;if(action!=='reopen'&&!form.reportValidity())return;setBusy(true);
  const payload={csrf,action,id:record.id,version:Number(record.version),subject:action==='reopen'?record.subject:form.elements.subject.value.trim(),notes:action==='reopen'?record.notes:form.elements.notes.value.trim(),outcome:outcome||record.outcome};
  try{const data=await api(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});setBusy(false);if(action==='reopen'){edit(data.record);notice.textContent='Atendimento #'+data.id+' reaberto. O histórico anterior foi preservado.';loadHistory();}else renderRecord(data.record,action==='edit'?'Alterações salvas · '+labels[data.outcome]:'Atendimento #'+data.id+' concluído · '+labels[data.outcome]);}
  catch(error){setBusy(false);notice.textContent=error.message;}
 }
 async function save(outcome){
  if(current){await mutate('finish',current,outcome);return;}if(busy||!client||!form.reportValidity())return;setBusy(true);notice.textContent='Salvando atendimento…';
  try{const data=await api(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,uuid:client.uuid_cliente,subject:form.elements.subject.value.trim(),notes:form.elements.notes.value.trim(),outcome,key:requestKey})});setBusy(false);renderRecord(data.record,'Atendimento #'+data.id+' registrado · '+labels[data.outcome]);}
  catch(error){setBusy(false);notice.textContent=error.message;}
 }
 function build(){
  modal=node('dialog','attendance-modal');modal.setAttribute('aria-label','Registrar e consultar atendimento');
  modal.innerHTML='<header><h2>Registrar atendimento</h2><button type="button" data-close aria-label="Fechar atendimento">Fechar</button></header><div class="attendance-body"><p>Registre o contato com o cliente e escolha o resultado ao concluir.</p><form><label>Cliente<input name="client" type="search" placeholder="Busque por nome ou login e selecione" autocomplete="off" required></label><div class="attendance-clients" role="group" aria-label="Clientes encontrados"></div><label>Assunto<input name="subject" maxlength="255" required placeholder="Ex.: cliente sem conexão"></label><label>Relato do atendimento<textarea name="notes" maxlength="8000" rows="5" required placeholder="O que foi informado, verificado e orientado?"></textarea></label><p class="attendance-hint">O registro será salvo ao escolher o resultado abaixo.</p><button type="submit" class="attendance-primary">Concluir atendimento</button> <button type="button" data-save-draft hidden>Salvar relato</button><section class="attendance-choices" hidden><h3>Como este atendimento terminou?</h3><button type="button" data-outcome="resolved">Resolvido</button><button type="button" data-outcome="finished">Finalizar sem OS</button><button type="button" data-outcome="service">Continuar para ordem de serviço</button></section></form><div class="attendance-notice" role="status"></div><details class="attendance-history"><summary>Histórico de atendimentos</summary><div></div></details></div>';
  document.body.append(modal);form=modal.querySelector('form');clientInput=form.elements.client;results=modal.querySelector('.attendance-clients');notice=modal.querySelector('.attendance-notice');choices=modal.querySelector('.attendance-choices');history=modal.querySelector('.attendance-history>div');
  modal.querySelector('[data-close]').onclick=()=>modal.close();modal.addEventListener('close',()=>{historySequence++;clearTimeout(lookupTimer);if(lookup)lookup.abort();});
  clientInput.oninput=()=>{client=null;clientInput.setCustomValidity('');results.replaceChildren();clearTimeout(lookupTimer);if(clientInput.value.trim().length>=2)lookupTimer=setTimeout(()=>search(clientInput.value.trim()),250);};
  form.onsubmit=event=>{event.preventDefault();if(current&&current.outcome!=='open'){mutate('edit',current);return;}if(!client){clientInput.setCustomValidity('Selecione um cliente da lista.');clientInput.reportValidity();return;}if(form.reportValidity()){choices.hidden=false;choices.querySelector('button').focus();}};
  form.querySelector('[data-save-draft]').onclick=()=>current&&mutate('edit',current);for(const el of choices.querySelectorAll('button'))el.onclick=()=>save(el.dataset.outcome);
 }
 document.addEventListener('click',event=>{const trigger=event.target.closest('[data-open-attendance],[data-attendance-history]');if(!trigger)return;event.preventDefault();if(!modal)build();if(busy)return;
  form.reset();current=null;client=null;requestKey=newKey();results.replaceChildren();notice.replaceChildren();choices.hidden=true;clientInput.readOnly=false;clientInput.setCustomValidity('');form.querySelector('[type=submit]').textContent='Concluir atendimento';form.querySelector('[data-save-draft]').hidden=true;form.querySelector('.attendance-hint').textContent='O registro será salvo ao escolher o resultado abaixo.';setBusy(false);
  const isHistory=trigger.hasAttribute('data-attendance-history');form.hidden=isHistory;title(isHistory?'Histórico de atendimentos':'Registrar atendimento');modal.querySelector('.attendance-history').open=isHistory;modal.showModal();top();
  if(isHistory){loadHistory();return;}clientInput.focus();const login=trigger.dataset.openAttendance;if(login){clientInput.value=login;search(login,login);}else loadHistory();
 });
})();
