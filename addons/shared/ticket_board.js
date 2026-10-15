(function(){
 'use strict';
 const buttons=Array.from(document.querySelectorAll('[data-ticket-filter]')),tickets=Array.from(document.querySelectorAll('[data-ticket-state]'));
 const status=document.getElementById('ticket-filter-status'),empty=document.getElementById('ticket-empty');
 const calendar=document.getElementById('ticket-calendar'),grid=document.getElementById('ticket-calendar-grid'),list=document.querySelector('.ticket-list');
 const viewButtons=Array.from(document.querySelectorAll('[data-ticket-view]'));
 let calendarView=false,start=new Date(),dragged=null,busy=false,editor=null;
 const endpoint='/admin/addons/busca_inteligente/ticket_schedule_api.php';
 function announce(message,error){const notice=document.getElementById('ticket-calendar-notice');if(notice){notice.textContent=message;notice.classList.toggle('is-error',!!error);}}
 async function api(id,payload){const response=await fetch(endpoint+(payload?'':'?id='+encodeURIComponent(id)),{credentials:'same-origin',cache:'no-store',...(payload?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}:{})});let data;try{data=await response.json();}catch(e){throw Error('Não foi possível acessar o agendamento. Confira sua sessão.');}if(!response.ok)throw Error(data.error||'Não foi possível salvar.');return data;}
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
  if(busy||!ticket||ticket.dataset.visit.slice(0,10)===day)return;busy=true;announce('Salvando nova data…');
  try{
   const data=await api(ticket.dataset.ticketId),row=data.record;
   if((row.visita||'')!==ticket.dataset.visit||row.assunto!==ticket.dataset.subject||(row.prioridade||'Não informada')!==ticket.dataset.priority)throw Error('O chamado mudou desde a abertura da página. Clique na visita para conferir os dados atuais.');
   const saved=await api(row.id,{csrf:data.csrf,id:row.id,version:row.version,action:'move',day});applyRecord(ticket,saved.record);announce('Visita salva para '+visitLabel(saved.record.visita)+'. Horário preservado.');
  }catch(error){announce(error.message,true);}finally{busy=false;}
 }
 async function edit(ticket){
  if(!ticket||busy)return;busy=true;announce('Carregando agendamento…');
  try{
   const data=await api(ticket.dataset.ticketId),row=data.record;
   if(!editor){editor=document.createElement('dialog');editor.className='ticket-schedule-modal';editor.setAttribute('aria-labelledby','ticket-schedule-title');editor.innerHTML='<header><h2 id="ticket-schedule-title">Editar agendamento</h2><button type="button" data-schedule-close>Fechar</button></header><form><p data-schedule-client></p><label>Assunto<input name="subject" maxlength="255" required></label><label>Prioridade<select name="priority"></select></label><label>Data e hora da visita<input name="visit" type="datetime-local" step="1"></label><p>Deixe a data vazia para retirar o agendamento. A abertura e os demais dados do chamado são preservados.</p><p data-schedule-status role="status"></p><footer><button type="button" data-schedule-details>Ver detalhes</button><button type="submit">Salvar alterações</button></footer></form>';document.body.append(editor);editor.querySelector('[data-schedule-close]').onclick=()=>editor.close();}
   const form=editor.querySelector('form'),notice=editor.querySelector('[data-schedule-status]');notice.textContent='';editor.querySelector('[data-schedule-client]').textContent=row.nome+' · Chamado #'+row.chamado;form.elements.subject.value=row.assunto;
   const options=Array.from(new Set(['baixa','normal','alta',row.prioridade||'']));form.elements.priority.replaceChildren(...options.map(value=>{const option=document.createElement('option');option.value=value;option.textContent=value||'Não informada';return option;}));form.elements.priority.value=row.prioridade||'';form.elements.visit.value=row.timing.state==='unscheduled'?'':row.visita.replace(' ','T');
   editor.querySelector('[data-schedule-details]').onclick=()=>{editor.close();setView('list');ticket.open=true;ticket.scrollIntoView({block:'center',behavior:'smooth'});};
   form.onsubmit=async event=>{event.preventDefault();if(busy||!form.reportValidity())return;busy=true;const submit=form.querySelector('[type=submit]');submit.disabled=true;notice.textContent='Salvando…';
    try{let visit=form.elements.visit.value.replace('T',' ');if(visit.length===16)visit+=':00';const saved=await api(row.id,{csrf:data.csrf,id:row.id,version:row.version,action:'edit',subject:form.elements.subject.value.trim(),priority:form.elements.priority.value,visit});applyRecord(ticket,saved.record);editor.close();announce('Agendamento salvo para '+visitLabel(saved.record.visita)+'.');}
    catch(error){notice.textContent=error.message;}finally{busy=false;submit.disabled=false;}
   };
   announce('');editor.showModal();form.elements.subject.focus();
  }catch(error){announce(error.message,true);}finally{busy=false;}
 }
 function resetDate(){start=new Date();start.setHours(0,0,0,0);start.setDate(start.getDate()-start.getDay());}
 resetDate();
 function setView(value){calendarView=value==='calendar';if(calendar)calendar.hidden=!calendarView;if(list)list.hidden=calendarView;for(const b of viewButtons)b.setAttribute('aria-pressed',String((b.dataset.ticketView==='calendar')===calendarView));renderCalendar();}
 function renderCalendar(){
  if(!grid)return;grid.replaceChildren();const end=new Date(start);end.setDate(end.getDate()+13);
  document.getElementById('ticket-calendar-range').textContent=start.toLocaleDateString('pt-BR')+' a '+end.toLocaleDateString('pt-BR');
  for(let i=0;i<14;i++){
   const day=new Date(start);day.setDate(day.getDate()+i);const cell=document.createElement('section');cell.className='ticket-calendar-day';
   const heading=document.createElement('h3');heading.textContent=day.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'2-digit'});cell.append(heading);
   const key=day.getFullYear()+'-'+String(day.getMonth()+1).padStart(2,'0')+'-'+String(day.getDate()).padStart(2,'0');
   cell.dataset.calendarDay=key;cell.addEventListener('dragover',e=>{if(dragged&&!busy){e.preventDefault();e.dataTransfer.dropEffect='move';cell.classList.add('is-drop-target');}});cell.addEventListener('dragleave',e=>{if(!cell.contains(e.relatedTarget))cell.classList.remove('is-drop-target');});cell.addEventListener('drop',e=>{e.preventDefault();cell.classList.remove('is-drop-target');const ticket=dragged;dragged=null;move(ticket,key);});
   const events=tickets.filter(t=>!t.hidden&&t.dataset.visit&&t.dataset.visit.slice(0,10)===key).sort((a,b)=>a.dataset.visit.localeCompare(b.dataset.visit));
   for(const ticket of events){const event=document.createElement('button');event.type='button';event.className='ticket-calendar-event '+ticket.dataset.ticketState;
    for(const text of [ticket.dataset.visit.slice(11,16)+' · '+ticket.dataset.subject,ticket.dataset.client,'Prioridade: '+ticket.dataset.priority,'Abertura: '+ticket.dataset.opening]){const line=document.createElement('span');line.textContent=text;event.append(line);}
    event.draggable=true;event.title='Arraste para mudar o dia ou clique para editar';event.addEventListener('dragstart',e=>{if(busy){e.preventDefault();return;}dragged=ticket;e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',ticket.dataset.ticketId);event.classList.add('is-dragging');});event.addEventListener('dragend',()=>{dragged=null;event.classList.remove('is-dragging');for(const cell of grid.querySelectorAll('.is-drop-target'))cell.classList.remove('is-drop-target');});
    event.addEventListener('click',()=>edit(ticket));cell.append(event);
   }
   if(!events.length){const note=document.createElement('p');note.textContent='Sem visitas';cell.append(note);}grid.append(cell);
  }
 }
 for(const b of viewButtons)b.addEventListener('click',()=>setView(b.dataset.ticketView));
 for(const b of document.querySelectorAll('[data-edit-ticket]'))b.addEventListener('click',()=>edit(b.closest('[data-ticket-id]')));
 for(const b of document.querySelectorAll('[data-calendar-step]'))b.addEventListener('click',()=>{const step=Number(b.dataset.calendarStep);if(step===0)resetDate();else start.setDate(start.getDate()+step*14);renderCalendar();});
 for(const button of buttons)button.addEventListener('click',()=>{
  const filter=button.dataset.ticketFilter;
  for(const item of buttons)item.setAttribute('aria-pressed',String(item===button));
  let shown=0;
  for(const ticket of tickets){ticket.hidden=!(filter==='all'||(filter==='unassigned'?ticket.dataset.unassigned==='1':ticket.dataset.ticketState===filter));if(!ticket.hidden)shown++;}
  if(status)status.textContent=shown+' de '+tickets.length+' chamados · '+button.dataset.label;
  if(empty)empty.hidden=shown!==0;
  if(calendarView)renderCalendar();
 });
})();
