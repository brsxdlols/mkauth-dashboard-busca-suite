(function(){
 'use strict';
 const buttons=Array.from(document.querySelectorAll('[data-ticket-filter]')),tickets=Array.from(document.querySelectorAll('[data-ticket-state]'));
 const status=document.getElementById('ticket-filter-status'),empty=document.getElementById('ticket-empty');
 const calendar=document.getElementById('ticket-calendar'),grid=document.getElementById('ticket-calendar-grid'),list=document.querySelector('.ticket-list');
 const viewButtons=Array.from(document.querySelectorAll('[data-ticket-view]'));
 let calendarView=false,start=new Date();
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
   const events=tickets.filter(t=>!t.hidden&&t.dataset.visit&&t.dataset.visit.slice(0,10)===key).sort((a,b)=>a.dataset.visit.localeCompare(b.dataset.visit));
   for(const ticket of events){const event=document.createElement('button');event.type='button';event.className='ticket-calendar-event '+ticket.dataset.ticketState;
    for(const text of [ticket.dataset.visit.slice(11,16)+' · '+ticket.dataset.subject,ticket.dataset.client,'Prioridade: '+ticket.dataset.priority,'Abertura: '+ticket.dataset.opening]){const line=document.createElement('span');line.textContent=text;event.append(line);}
    event.addEventListener('click',()=>{setView('list');ticket.open=true;ticket.scrollIntoView({block:'center',behavior:'smooth'});ticket.querySelector('summary').focus();});cell.append(event);
   }
   if(!events.length){const note=document.createElement('p');note.textContent='Sem visitas';cell.append(note);}grid.append(cell);
  }
 }
 for(const b of viewButtons)b.addEventListener('click',()=>setView(b.dataset.ticketView));
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
