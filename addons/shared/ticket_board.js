(function(){
 'use strict';
 const buttons=Array.from(document.querySelectorAll('[data-ticket-filter]')),tickets=Array.from(document.querySelectorAll('[data-ticket-state]'));
 const status=document.getElementById('ticket-filter-status'),empty=document.getElementById('ticket-empty');
 const calendar=document.getElementById('ticket-calendar'),grid=document.getElementById('ticket-calendar-grid'),list=document.querySelector('.ticket-list');
 const viewButtons=Array.from(document.querySelectorAll('[data-ticket-view]'));
 let calendarView=false,start=new Date(),dragged=null,busy=false,editor=null,moveEditor=null,moveSequence=0,showFilteredWeeks=false;
 const installation=document.querySelector('[data-installation-board]'),editable=!installation||installation.dataset.canEdit==='1';
 const endpoint='/admin/addons/busca_inteligente/'+(installation?'installation_schedule_api.php':'ticket_schedule_api.php');
 function announce(message,error){const notice=document.getElementById('ticket-calendar-notice');if(notice){notice.textContent=message;notice.classList.toggle('is-error',!!error);}}
 async function api(id,payload,query=''){const response=await fetch(endpoint+(payload?'':'?id='+encodeURIComponent(id)+query),{credentials:'same-origin',cache:'no-store',...(payload?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}:{})});let data;try{data=await response.json();}catch(e){throw Error('Não foi possível acessar o agendamento. Confira sua sessão.');}if(!response.ok){const error=Error(data.error||'Não foi possível salvar.');error.bookingConflicts=Number(data.booking_conflicts)||0;throw error;}return data;}
 function outsideHours(visit){const time=visit?visit.replace('T',' ').slice(11,16):'';return !!time&&(time<'08:00'||time>'18:00');}
 function bookingWarning(form,count){let box=form.querySelector('[data-booking-warning]');if(!box){box=document.createElement('aside');box.className='ticket-outside-warning';box.dataset.bookingWarning='';box.innerHTML='<strong>Sobreposição de visitas</strong><p data-booking-message></p><label><input type="checkbox" name="conflictsConfirmed"> Estou ciente e vou organizar equipes diferentes.</label>';form.querySelector('footer').before(box);}if(Number(box.dataset.count)!==count)form.elements.conflictsConfirmed.checked=false;box.dataset.count=count;box.hidden=count===0;form.elements.conflictsConfirmed.required=count>0;box.querySelector('[data-booking-message]').textContent=count===1?'Já há uma visita nesta faixa de horário. Organize duas equipes para atender as duas visitas ao mesmo tempo.':'Já há '+count+' visitas nesta faixa de horário. Organize '+(count+1)+' equipes para atender as visitas ao mesmo tempo.';}
 function confirmedConflicts(form){const box=form.querySelector('[data-booking-warning]');return box&&form.elements.conflictsConfirmed.checked?Number(box.dataset.count)||0:0;}
 function visitLabel(visit){return !visit||visit.startsWith('0000')?'Não agendada':visit.replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}).*/,'$3/$2/$1 $4');}
 function applyRecord(ticket,row){
  ticket.dataset.visit=row.timing.state==='unscheduled'?'':row.visita;ticket.dataset.subject=row.assunto;ticket.dataset.priority=row.prioridade||'Não informada';ticket.dataset.ticketState=row.timing.state;
  const subject=ticket.querySelector('.ticket-summary-address strong');if(subject)subject.textContent='#'+row.chamado+' · '+row.assunto;
  const priority=ticket.querySelector('.ticket-priority');if(priority){priority.textContent='Prioridade: '+ticket.dataset.priority;priority.className='ticket-priority priority-'+(['baixa','normal','alta','urgente'].includes(row.prioridade)?row.prioridade:'other');}
  for(const label of ticket.querySelectorAll('[data-ticket-visit-label]'))label.textContent=visitLabel(row.visita);
  const timing=ticket.querySelector('.ticket-sla');if(timing){timing.textContent=row.timing.text;timing.className='ticket-sla '+row.timing.class;}
  for(const b of buttons){const f=b.dataset.ticketFilter,count=tickets.filter(t=>f==='all'||(f==='unassigned'?t.dataset.unassigned==='1':t.dataset.ticketState===f)).length;const value=b.querySelector('strong');if(value)value.textContent=count;}
  const active=buttons.find(b=>b.getAttribute('aria-pressed')==='true');if(active)active.click();else renderCalendar();
 }
 async function move(ticket,day){
  if(busy||!ticket||ticket.dataset.visit.slice(0,10)===day)return;const sequence=++moveSequence;busy=true;announce('Conferindo agendamento…');
  try{
   const data=await api(ticket.dataset.ticketId),row=data.record;
   if((row.visita||'')!==ticket.dataset.visit||row.assunto!==ticket.dataset.subject||(row.prioridade||'Não informada')!==ticket.dataset.priority)throw Error('O chamado mudou desde a abertura da página. Clique na visita para conferir os dados atuais.');
   if(!moveEditor){moveEditor=document.createElement('dialog');moveEditor.className='ticket-schedule-modal ticket-move-modal';moveEditor.setAttribute('aria-labelledby','ticket-move-title');moveEditor.innerHTML='<header><h2 id="ticket-move-title">Mover visita</h2><button type="button" data-move-close>Fechar</button></header><form><p data-move-client></p><p data-move-dates></p><fieldset><legend>Como deseja agendar no novo dia?</legend><label><input type="radio" name="mode" value="keep" checked> Manter o mesmo horário <strong data-move-time></strong></label><label><input type="radio" name="mode" value="change"> Alterar horário</label></fieldset><div data-move-slots hidden><label>Novo horário<select name="time" disabled><option value="">Carregando horários…</option></select></label><label data-move-custom hidden>Informe o horário combinado<input name="customTime" type="time" step="60" disabled></label><p data-move-availability></p><p class="ticket-schedule-hint">As marcações mostram as visitas que você pode consultar. Horários com visitas também podem ser selecionados.</p></div><aside class="ticket-outside-warning" data-outside-warning hidden><strong>Fora do horário de expediente</strong><p>O expediente é das 08h às 18h. Tem certeza de que deseja agendar neste horário?</p><label><input type="checkbox" name="outsideConfirmed"> Sim, o horário foi combinado e quero continuar.</label></aside><p data-move-status role="status"></p><footer><button type="button" data-move-cancel>Cancelar</button><button type="submit">Confirmar mudança</button></footer></form>';document.body.append(moveEditor);for(const b of moveEditor.querySelectorAll('[data-move-close],[data-move-cancel]'))b.onclick=()=>moveEditor.close();}
   const form=moveEditor.querySelector('form'),notice=moveEditor.querySelector('[data-move-status]');form.reset();notice.textContent='';form.elements.time.disabled=true;form.elements.time.replaceChildren(new Option('Carregando horários…',''));moveEditor.querySelector('[data-move-slots]').hidden=true;
   moveEditor.querySelector('[data-move-client]').textContent=row.nome+(installation?' · Instalação #':' · Chamado #')+row.chamado;moveEditor.querySelector('[data-move-dates]').textContent='De '+visitLabel(row.visita)+' para '+visitLabel(day+row.visita.slice(10));moveEditor.querySelector('[data-move-time]').textContent=row.visita.slice(11,16);
   let available=null,lastCandidate='';bookingWarning(form,0);const candidateTime=()=>form.elements.mode.value==='keep'?row.visita.slice(11):(form.elements.time.value==='custom'?form.elements.customTime.value:form.elements.time.value);
   function updateChoice(){const change=form.elements.mode.value==='change',custom=change&&form.elements.time.value==='custom';moveEditor.querySelector('[data-move-slots]').hidden=!change;moveEditor.querySelector('[data-move-custom]').hidden=!custom;form.elements.time.required=change;form.elements.customTime.disabled=!custom;form.elements.customTime.required=custom;const time=candidateTime(),candidate=time?day+' '+time:'';if(candidate!==lastCandidate){form.elements.outsideConfirmed.checked=false;form.elements.conflictsConfirmed.checked=false;lastCandidate=candidate;}const outside=outsideHours(candidate);moveEditor.querySelector('[data-outside-warning]').hidden=!outside;form.elements.outsideConfirmed.required=outside;const count=available&&time?available.hours[Number(time.slice(0,2))]:null;bookingWarning(form,count||0);moveEditor.querySelector('[data-move-availability]').textContent=count===null?'':count?count+' visita(s) agendada(s) entre '+time.slice(0,2)+'h e '+String(Number(time.slice(0,2))+1).padStart(2,'0')+'h.':'Sem visitas agendadas nesta faixa de uma hora.';}
   for(const input of form.querySelectorAll('[name=mode],[name=time],[name=customTime]')){input.onchange=updateChoice;input.oninput=updateChoice;}
   updateChoice();
   form.onsubmit=async event=>{event.preventDefault();if(busy)return;if(!available){notice.textContent='Aguarde a consulta dos horários. Se ela falhou, feche e tente novamente.';return;}if(!form.reportValidity())return;busy=true;const submit=form.querySelector('[type=submit]');submit.disabled=true;notice.textContent='Salvando…';try{const payload={csrf:data.csrf,id:row.id,version:row.version,action:'move',day,outside_confirmed:form.elements.outsideConfirmed.checked,conflicts_confirmed:confirmedConflicts(form)};if(form.elements.mode.value==='change')payload.time=candidateTime();const saved=await api(row.id,payload);applyRecord(ticket,saved.record);moveEditor.close();announce('Visita salva para '+visitLabel(saved.record.visita)+'.');}catch(error){notice.textContent=error.message;if(error.bookingConflicts){available.hours[Number(candidateTime().slice(0,2))]=error.bookingConflicts;updateChoice();}}finally{busy=false;submit.disabled=false;}};
   announce('');moveEditor.showModal();form.querySelector('[name=mode]').focus();
   api(row.id,null,'&action=slots&day='+encodeURIComponent(day)).then(slots=>{if(!moveEditor.open||sequence!==moveSequence)return;available=slots;form.elements.time.replaceChildren(new Option('Selecione um horário',''),...slots.slots.map(slot=>new Option(slot.time+' · '+(slot.visits?slot.visits+' visita(s) agendada(s)':'Sem visitas'),slot.time)),new Option('Outro horário…','custom'));form.elements.time.disabled=false;updateChoice();}).catch(error=>{if(moveEditor.open&&sequence===moveSequence)moveEditor.querySelector('[data-move-availability]').textContent=error.message;});
  }catch(error){announce(error.message,true);}finally{busy=false;}
 }
 async function edit(ticket){
  if(installation){const link=ticket&&ticket.querySelector('[data-open-native-installation]');if(link)link.click();else if(ticket){setView('list');ticket.open=true;ticket.scrollIntoView({block:'center',behavior:'smooth'});}return;}
  if(!ticket||busy)return;busy=true;announce('Carregando agendamento…');
  try{
   const data=await api(ticket.dataset.ticketId),row=data.record;
   if(!editor){editor=document.createElement('dialog');editor.className='ticket-schedule-modal';editor.setAttribute('aria-labelledby','ticket-schedule-title');editor.innerHTML='<header><h2 id="ticket-schedule-title">Editar agendamento</h2><button type="button" data-schedule-close>Fechar</button></header><form><p data-schedule-client></p><label>Assunto<input name="subject" maxlength="255" required></label><label>Prioridade<select name="priority"></select></label><label>Data e hora da visita<input name="visit" type="datetime-local" step="1"></label><p>Deixe a data vazia para retirar o agendamento. A abertura e os demais dados do chamado são preservados.</p><aside class="ticket-outside-warning" data-outside-warning hidden><strong>Fora do horário de expediente</strong><p>O expediente é das 08h às 18h. Tem certeza de que deseja agendar neste horário?</p><label><input type="checkbox" name="outsideConfirmed"> Sim, o horário foi combinado e quero continuar.</label></aside><p data-schedule-status role="status"></p><footer><button type="button" data-schedule-details>Ver detalhes</button><button type="submit">Salvar alterações</button></footer></form>';document.body.append(editor);editor.querySelector('[data-schedule-close]').onclick=()=>editor.close();}
   const form=editor.querySelector('form'),notice=editor.querySelector('[data-schedule-status]');notice.textContent='';editor.querySelector('[data-schedule-client]').textContent=row.nome+(installation?' · Instalação #':' · Chamado #')+row.chamado;form.elements.subject.value=row.assunto;
   const options=Array.from(new Set(['baixa','normal','alta',row.prioridade||'']));form.elements.priority.replaceChildren(...options.map(value=>{const option=document.createElement('option');option.value=value;option.textContent=value||'Não informada';return option;}));form.elements.priority.value=row.prioridade||'';form.elements.visit.value=row.timing.state==='unscheduled'?'':row.visita.replace(' ','T');
   function updateOutside(){let visit=form.elements.visit.value.replace('T',' ');if(visit.length===16)visit+=':00';const outside=visit!==row.visita&&outsideHours(visit);editor.querySelector('[data-outside-warning]').hidden=!outside;form.elements.outsideConfirmed.required=outside;form.elements.outsideConfirmed.checked=false;bookingWarning(form,0);}form.elements.visit.oninput=updateOutside;form.elements.visit.onchange=updateOutside;updateOutside();
   editor.querySelector('[data-schedule-details]').onclick=()=>{editor.close();setView('list');ticket.open=true;ticket.scrollIntoView({block:'center',behavior:'smooth'});};
   form.onsubmit=async event=>{event.preventDefault();if(busy||!form.reportValidity())return;busy=true;const submit=form.querySelector('[type=submit]');submit.disabled=true;notice.textContent='Salvando…';
    try{let visit=form.elements.visit.value.replace('T',' ');if(visit.length===16)visit+=':00';const saved=await api(row.id,{csrf:data.csrf,id:row.id,version:row.version,action:'edit',subject:form.elements.subject.value.trim(),priority:form.elements.priority.value,visit,outside_confirmed:form.elements.outsideConfirmed.checked,conflicts_confirmed:confirmedConflicts(form)});applyRecord(ticket,saved.record);editor.close();announce('Agendamento salvo para '+visitLabel(saved.record.visita)+'.');}
    catch(error){notice.textContent=error.message;if(error.bookingConflicts)bookingWarning(form,error.bookingConflicts);}finally{busy=false;submit.disabled=false;}
   };
   announce('');editor.showModal();form.elements.subject.focus();
  }catch(error){announce(error.message,true);}finally{busy=false;}
 }
 function resetDate(){start=new Date();start.setHours(0,0,0,0);start.setDate(start.getDate()-start.getDay());}
 resetDate();
 function setView(value){calendarView=value==='calendar';if(calendar)calendar.hidden=!calendarView;if(list)list.hidden=calendarView;for(const b of viewButtons)b.setAttribute('aria-pressed',String((b.dataset.ticketView==='calendar')===calendarView));renderCalendar();}
 function renderCalendar(){
  if(!grid)return;grid.replaceChildren();const end=new Date(start);end.setDate(end.getDate()+13);
  let days=Array.from({length:14},(_,i)=>{const day=new Date(start);day.setDate(day.getDate()+i);return day;});
  if(showFilteredWeeks){
   const weeks=new Map();
   for(const ticket of tickets.filter(t=>!t.hidden&&t.dataset.visit)){
    const day=new Date(ticket.dataset.visit.slice(0,10)+'T00:00:00');if(isNaN(day))continue;
    day.setDate(day.getDate()-day.getDay());weeks.set(day.getTime(),day);
   }
   days=Array.from(weeks.values()).sort((a,b)=>a-b).flatMap(week=>Array.from({length:7},(_,i)=>{const day=new Date(week);day.setDate(day.getDate()+i);return day;}));
  }
  document.getElementById('ticket-calendar-range').textContent=showFilteredWeeks?'Todas as semanas com visitas atrasadas':start.toLocaleDateString('pt-BR')+' a '+end.toLocaleDateString('pt-BR');
  for(const day of days){
   const cell=document.createElement('section');cell.className='ticket-calendar-day';
   const heading=document.createElement('h3');heading.textContent=day.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'2-digit'});cell.append(heading);
   const key=day.getFullYear()+'-'+String(day.getMonth()+1).padStart(2,'0')+'-'+String(day.getDate()).padStart(2,'0');
   cell.dataset.calendarDay=key;cell.addEventListener('dragover',e=>{if(dragged&&!busy){e.preventDefault();e.dataTransfer.dropEffect='move';cell.classList.add('is-drop-target');}});cell.addEventListener('dragleave',e=>{if(!cell.contains(e.relatedTarget))cell.classList.remove('is-drop-target');});cell.addEventListener('drop',e=>{e.preventDefault();cell.classList.remove('is-drop-target');const ticket=dragged;dragged=null;move(ticket,key);});
   const events=tickets.filter(t=>!t.hidden&&t.dataset.visit&&t.dataset.visit.slice(0,10)===key).sort((a,b)=>a.dataset.visit.localeCompare(b.dataset.visit));
   for(const ticket of events){const event=document.createElement('button');event.type='button';event.className='ticket-calendar-event '+ticket.dataset.ticketState;
    for(const text of [ticket.dataset.visit.slice(11,16)+' · '+ticket.dataset.subject,ticket.dataset.client,(installation?'Situação: ':'Prioridade: ')+ticket.dataset.priority,(installation?'Cadastro: ':'Abertura: ')+ticket.dataset.opening]){const line=document.createElement('span');line.textContent=text;event.append(line);}
    event.draggable=editable;event.title=editable?'Arraste para mudar o dia ou clique para editar':'Clique para consultar';event.addEventListener('dragstart',e=>{if(busy||!editable){e.preventDefault();return;}dragged=ticket;e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',ticket.dataset.ticketId);event.classList.add('is-dragging');});event.addEventListener('dragend',()=>{dragged=null;event.classList.remove('is-dragging');for(const cell of grid.querySelectorAll('.is-drop-target'))cell.classList.remove('is-drop-target');});
    event.addEventListener('click',()=>edit(ticket));cell.append(event);
   }
   if(!events.length){const note=document.createElement('p');note.textContent='Sem visitas';cell.append(note);}grid.append(cell);
  }
 }
 for(const b of viewButtons)b.addEventListener('click',()=>setView(b.dataset.ticketView));
 for(const b of document.querySelectorAll('[data-edit-ticket]'))b.addEventListener('click',()=>edit(b.closest('[data-ticket-id]')));
 for(const b of document.querySelectorAll('[data-calendar-step]'))b.addEventListener('click',()=>{showFilteredWeeks=false;const step=Number(b.dataset.calendarStep);if(step===0)resetDate();else start.setDate(start.getDate()+step*14);renderCalendar();});
 for(const button of buttons)button.addEventListener('click',()=>{
  const filter=button.dataset.ticketFilter;
  showFilteredWeeks=filter==='late';
  for(const item of buttons)item.setAttribute('aria-pressed',String(item===button));
  let shown=0;
  for(const ticket of tickets){ticket.hidden=!(filter==='all'||(filter==='unassigned'?ticket.dataset.unassigned==='1':ticket.dataset.ticketState===filter));if(!ticket.hidden)shown++;}
  if(status)status.textContent=shown+' de '+tickets.length+(installation?' instalações · ':' chamados · ')+button.dataset.label;
  if(empty)empty.hidden=shown!==0;
  if(calendarView)renderCalendar();
 });
 const returnKey='mka-ticket-board-return';
 try{const saved=JSON.parse(sessionStorage.getItem(returnKey)||'null');sessionStorage.removeItem(returnKey);if(saved&&saved.path===location.pathname+location.search&&Date.now()-saved.savedAt<3600000&&Number.isFinite(saved.start)){const date=new Date(saved.start);if(!isNaN(date))start=date;}}catch(error){}
 document.addEventListener('mka-ticket-native-refresh',()=>{try{sessionStorage.setItem(returnKey,JSON.stringify({path:location.pathname+location.search,start:start.getTime(),savedAt:Date.now()}));}catch(error){}});
 setView('calendar');
})();
