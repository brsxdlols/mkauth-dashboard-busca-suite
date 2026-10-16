(function(){
 'use strict';
 if(window.mkaSupportAttendanceLoaded)return;window.mkaSupportAttendanceLoaded=true;
 const endpoint='/admin/addons/busca_inteligente/attendance_api.php';
 let modal,form,clientInput,results,client=null,csrf='',requestKey='',busy=false,lookup=null,lookupTimer=null,notice,choices,history,historySequence=0,current=null,pendingOutcome=null;
 const labels={open:'Em atendimento',resolved:'Resolvido',finished:'Finalizado sem OS',service:'Encaminhado para OS'};
 function node(tag,cls,text){const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;}
 function button(text,handler,cls){const el=node('button',cls||'attendance-secondary',text);el.type='button';el.onclick=handler;return el;}
 async function api(url,options){const response=await fetch(url,{credentials:'same-origin',cache:'no-store',...options});let data;try{data=await response.json();}catch(e){throw Error('Não foi possível acessar o registro. Confira sua sessão.');}if(!response.ok)throw Error(data.error||'Não foi possível concluir.');if(data.csrf)csrf=data.csrf;return data;}
 function date(value){if(!value)return '';return String(value).replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}).*/,'$3/$2/$1 $4');}
 function newKey(){return Date.now().toString(36)+'_'+Math.random().toString(36).slice(2)+'_'+Math.random().toString(36).slice(2);}
 function title(text){modal.querySelector('h2').textContent=text;}
 function top(){modal.querySelector('.attendance-body').scrollTop=0;}
 function renderRecord(record,message){
  modal.classList.remove('is-service');modal.querySelector('.attendance-history').hidden=false;current=record;client={uuid_cliente:record.client_uuid,nome:record.client_name,login:record.login};form.hidden=true;choices.hidden=true;results.replaceChildren();title('Atendimento #'+record.id);
  notice.replaceChildren(node('h3','attendance-result '+(record.outcome==='resolved'?'is-resolved':''),message||('Atendimento #'+record.id+' · '+labels[record.outcome])));
  notice.append(node('p','',record.client_name+' · '+record.login),node('strong','',record.subject),node('p','attendance-notes',record.notes),node('p','attendance-hint','Registrado em '+date(record.created_at)+' por '+record.operator+(record.updated_at?' · Atualizado em '+date(record.updated_at)+' por '+record.updated_by:'')));
  const actions=node('div','attendance-result-actions');actions.append(button(document.querySelector('.attendance-page')?'Voltar aos atendimentos':'Voltar aos chamados',()=>modal.close(),'attendance-primary'));
  if(record.can_edit){actions.append(button('Editar registro',()=>edit(record)));actions.append(button(record.outcome==='open'?'Continuar atendimento':'Reabrir atendimento',()=>record.outcome==='open'?edit(record):mutate('reopen',record)));}
  if(record.outcome==='service'){const link=node('a','attendance-primary',record.service_ticket?'Ver OS #'+record.service_ticket.chamado:'Abrir ordem de serviço');link.href=record.service_url;if(!record.service_ticket)link.onclick=event=>{event.preventDefault();event.stopPropagation();openService(record,'');};link.target='_blank';link.rel='noopener';actions.append(link);notice.append(node('p','',record.service_ticket?'Ordem de serviço gravada no MK-AUTH e vinculada a este atendimento.':'Revise e grave a ordem de serviço no formulário nativo.'));}
  notice.append(actions);top();actions.querySelector('button').focus();loadHistory();
 }
 function resetConclusion(){pendingOutcome=null;choices.hidden=false;choices.querySelector('[data-service-extra]').hidden=true;choices.querySelector('.attendance-simple-actions').hidden=false;form.querySelector('[type=submit]').hidden=true;modal.classList.remove('is-service');modal.querySelector('.attendance-history').hidden=false;}
 function edit(record){
  resetConclusion();
  current=record;client={uuid_cliente:record.client_uuid,nome:record.client_name,login:record.login};form.hidden=false;notice.replaceChildren();results.replaceChildren();
  clientInput.value=record.client_name+' · '+record.login;clientInput.readOnly=true;clientInput.setCustomValidity('');form.elements.subject.value=record.subject;form.elements.notes.value=record.notes;
  form.querySelector('[type=submit]').textContent='Salvar alterações';form.querySelector('[type=submit]').hidden=record.outcome==='open';choices.hidden=record.outcome!=='open';
  form.querySelector('.attendance-hint').textContent=record.outcome==='open'?'Escolha como continuar este atendimento.':'A edição preserva o resultado atual: '+labels[record.outcome]+'.';title('Editar atendimento #'+record.id);top();form.elements.notes.focus();
 }
 async function loadHistory(){
  const sequence=++historySequence;history.replaceChildren(node('p','','Carregando histórico…'));
  try{const data=await api(endpoint+'?action=history'+(client?'&uuid='+encodeURIComponent(client.uuid_cliente):''));if(sequence!==historySequence)return;history.replaceChildren();
   history.append(node('p','attendance-hint','Até 20 registros mais recentes · todos os dias e atendentes'+(client?' · deste cliente':'.')));const full=node('a','attendance-history-link','Ver histórico completo e filtrar');full.href='/admin/addons/busca_inteligente/atendimentos.php?all=1'+(client?'&uuid='+encodeURIComponent(client.uuid_cliente)+'&client_name='+encodeURIComponent(client.nome):'');history.append(full);
   if(client){const integrated=node('a','attendance-history-link','Ver atendimentos e OS deste cliente');integrated.href='/admin/addons/busca_inteligente/atendimentos.php?all=1&kind=all&uuid='+encodeURIComponent(client.uuid_cliente)+'&client_name='+encodeURIComponent(client.nome);history.append(integrated);}
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
  try{const data=await api(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});setBusy(false);document.dispatchEvent(new CustomEvent('mka-attendance-changed'));if(action==='reopen'){edit(data.record);notice.textContent='Atendimento #'+data.id+' reaberto. O histórico anterior foi preservado.';loadHistory();}else renderRecord(data.record,action==='edit'?'Alterações salvas · '+labels[data.outcome]:'Atendimento #'+data.id+' atualizado · '+labels[data.outcome]);return data.record;}
  catch(error){setBusy(false);notice.textContent=error.message;}
 }
 async function save(outcome){
  if(current)return await mutate(outcome==='open'?'edit':'finish',current,outcome);if(busy)return;if(!client){clientInput.setCustomValidity('Selecione um cliente da lista.');clientInput.reportValidity();return;}if(!form.reportValidity())return;setBusy(true);notice.textContent='Salvando atendimento…';
  try{const data=await api(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,uuid:client.uuid_cliente,subject:form.elements.subject.value.trim(),notes:form.elements.notes.value.trim(),outcome,key:requestKey})});setBusy(false);document.dispatchEvent(new CustomEvent('mka-attendance-changed'));renderRecord(data.record,'Atendimento #'+data.id+' registrado · '+labels[data.outcome]);return data.record;}
  catch(error){setBusy(false);notice.textContent=error.message;}
 }
 async function openService(record,extra){
  if(record.service_ticket){renderRecord(record);return;}
  form.hidden=true;choices.hidden=true;modal.querySelector('.attendance-history').hidden=true;modal.classList.add('is-service');title('Ordem de serviço · Atendimento #'+record.id);notice.replaceChildren();
  const toolbar=node('div','attendance-service-toolbar');toolbar.append(button('Voltar ao atendimento',()=>renderRecord(record)));const status=node('p','attendance-hint','O relato do atendimento será preenchido na OS. Confira a agenda e grave quando estiver pronto.');toolbar.append(status);const frame=node('iframe','attendance-service-frame');frame.title='Ordem de serviço do atendimento';notice.append(toolbar,frame);let submitted=false;
  frame.addEventListener('load',async()=>{if(!modal.open)return;try{const doc=frame.contentDocument;if(!doc)return;if(submitted){const data=await api(endpoint+'?action=detail&id='+record.id);const updated=data.records[0];if(updated.service_ticket){status.textContent='Ordem de serviço #'+updated.service_ticket.chamado+' registrada e vinculada ao atendimento.';record=updated;document.dispatchEvent(new CustomEvent('mka-attendance-changed'));}return;}
   const uuid=doc.getElementById('uuid_cliente'),name=doc.getElementById('cliente'),notes=doc.getElementById('msg'),subject=doc.getElementById('assunto');if(!uuid||!name||!notes){status.textContent='Confira a página do MK-AUTH abaixo.';return;}
   uuid.value=record.client_uuid;name.value=record.client_name;notes.value='[Atendimento #'+record.id+']\nAssunto: '+record.subject+'\n\n'+record.notes+(extra?'\n\nInformações adicionais para a OS:\n'+extra:'');
   if(subject){const option=Array.from(subject.options).find(o=>o.textContent.trim().toLowerCase()===record.subject.trim().toLowerCase());if(option)subject.value=option.value;}
   if(window.mkaPrepareTicketDoc)await window.mkaPrepareTicketDoc(doc);
   const nativeForm=uuid.closest('form');if(nativeForm)nativeForm.addEventListener('submit',()=>{submitted=true;});
  }catch(error){status.textContent='Não foi possível preparar a OS. Volte ao atendimento e tente novamente.';}});
  const url=new URL(record.service_url,location.href);if(url.origin!==location.origin||!/^\/admin\/suporte_ins\.(hhvm|php)$/.test(url.pathname)){status.textContent='Formulário de OS indisponível.';return;}frame.src=url.href;top();
 }
 function build(){
  modal=node('dialog','attendance-modal');modal.setAttribute('aria-label','Registrar e consultar atendimento');
  modal.innerHTML='<header><h2>Registrar atendimento</h2><button type="button" data-close aria-label="Fechar atendimento">Fechar</button></header><div class="attendance-body"><p>Registre o contato com o cliente e escolha como continuar.</p><form><label>Cliente<input name="client" type="search" placeholder="Busque por nome ou login e selecione" autocomplete="off" required></label><div class="attendance-clients" role="group" aria-label="Clientes encontrados"></div><label>Assunto<input name="subject" maxlength="255" required placeholder="Ex.: cliente sem conexão"></label><label>Relato do atendimento<textarea name="notes" maxlength="8000" rows="5" required placeholder="O que foi informado, verificado e orientado?"></textarea></label><p class="attendance-hint">Escolha como continuar este atendimento.</p><button type="submit" class="attendance-primary" hidden>Salvar alterações</button><section class="attendance-choices"><div class="attendance-simple-actions"><button type="button" data-outcome="resolved">Resolvido</button><button type="button" data-outcome="open">Manter em atendimento</button><button type="button" data-outcome="service">Continuar para ordem de serviço</button></div><div data-service-extra hidden><label>O que mais deseja informar para a ordem de serviço?<textarea data-extra-notes rows="3" maxlength="3000" placeholder="Ex.: referência do endereço, testes realizados, contato no local. Opcional."></textarea></label><p class="attendance-hint">O relato acima será levado junto para a OS. Você poderá revisar tudo na próxima etapa.</p><button type="button" data-service-back class="attendance-secondary">Voltar</button><button type="button" data-service-continue class="attendance-primary">Continuar para OS</button></div></section></form><div class="attendance-notice" role="status"></div><details class="attendance-history"><summary>Atendimentos recentes</summary><div></div></details></div>';

  document.body.append(modal);form=modal.querySelector('form');clientInput=form.elements.client;results=modal.querySelector('.attendance-clients');notice=modal.querySelector('.attendance-notice');choices=modal.querySelector('.attendance-choices');history=modal.querySelector('.attendance-history>div');
  modal.querySelector('[data-close]').onclick=()=>modal.close();modal.addEventListener('close',()=>{historySequence++;clearTimeout(lookupTimer);if(lookup)lookup.abort();});
  clientInput.oninput=()=>{client=null;clientInput.setCustomValidity('');results.replaceChildren();clearTimeout(lookupTimer);if(clientInput.value.trim().length>=2)lookupTimer=setTimeout(()=>search(clientInput.value.trim()),250);};
  function validClient(){if(!client){clientInput.setCustomValidity('Selecione um cliente da lista.');clientInput.reportValidity();return false;}return form.reportValidity();}
  form.onsubmit=event=>{event.preventDefault();if(current&&current.outcome!=='open')mutate('edit',current);};
  for(const el of choices.querySelectorAll('[data-outcome]'))el.onclick=()=>{if(!validClient())return;if(el.dataset.outcome==='service'){choices.querySelector('[data-service-extra]').hidden=false;choices.querySelector('.attendance-simple-actions').hidden=true;choices.querySelector('[data-extra-notes]').focus();}else save(el.dataset.outcome);};
  choices.querySelector('[data-service-back]').onclick=()=>{choices.querySelector('[data-service-extra]').hidden=true;choices.querySelector('.attendance-simple-actions').hidden=false;};
  choices.querySelector('[data-service-continue]').onclick=async()=>{const extra=choices.querySelector('[data-extra-notes]').value.trim();const record=await save('service');if(record)openService(record,extra);};

 }
 document.addEventListener('click',async event=>{const trigger=event.target.closest('[data-open-attendance],[data-attendance-history],[data-attendance-record]');if(!trigger)return;event.preventDefault();if(!modal)build();if(busy)return;
  if(trigger.hasAttribute('data-attendance-record')){const id=trigger.dataset.attendanceRecord;if(!/^[1-9][0-9]*$/.test(id))return;current=null;client=null;form.hidden=true;choices.hidden=true;notice.textContent='Carregando atendimento…';title('Atendimento #'+id);modal.showModal();top();setBusy(true);try{const data=await api(endpoint+'?action=detail&id='+encodeURIComponent(id));setBusy(false);renderRecord(data.records[0]);}catch(error){setBusy(false);notice.textContent=error.message;}return;}
  form.reset();resetConclusion();current=null;client=null;requestKey=newKey();results.replaceChildren();notice.replaceChildren();choices.hidden=false;clientInput.readOnly=false;clientInput.setCustomValidity('');form.querySelector('[type=submit]').hidden=true;form.querySelector('.attendance-hint').textContent='Escolha como continuar este atendimento.';setBusy(false);
  const isHistory=trigger.hasAttribute('data-attendance-history');form.hidden=isHistory;title(isHistory?'Histórico de atendimentos':'Registrar atendimento');modal.querySelector('.attendance-history').open=isHistory;modal.showModal();top();
  if(isHistory){loadHistory();return;}clientInput.focus();const login=trigger.dataset.openAttendance,uuid=trigger.dataset.attendanceUuid,name=trigger.dataset.attendanceName;if(login&&name&&/^[a-zA-Z0-9-]{16,64}$/.test(uuid||'')){chooseClient({uuid_cliente:uuid,nome:name,login});form.elements.subject.focus();}else if(login){clientInput.value=login;search(login,login);}else loadHistory();
 });
})();
