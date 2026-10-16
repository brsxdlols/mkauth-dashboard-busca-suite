(function(){
 'use strict';if(window.mkaTicketBooking)return;
 const endpoint='/admin/addons/busca_inteligente/visit_agenda_api.php';
 window.mkaTicketBooking=async function(doc){
  const installation=!!doc.querySelector('form[action*="executar_instalacao"]'),kind=installation?'installation':'ticket';const form=doc.querySelector('.suite-ticket-form'),date=doc.getElementById('visita_data'),time=doc.getElementById('visita_hora'),ramal=doc.getElementById('ramal'),message=doc.getElementById(installation?'obs':'msg');
  if(!form||!date||!time||!message||form.dataset.bookingReady)return;form.dataset.bookingReady='1';
  if(installation){const label=date.closest('.field.is-horizontal')?.querySelector('.field-label .label');if(label)label.textContent='Data da instalação';}
  const initialParams=new URLSearchParams(doc.defaultView.location.search),explicitDay=initialParams.get('suite_day');
  if(!installation){
   const parts=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Sao_Paulo',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());const part=type=>parts.find(p=>p.type===type).value;const today=part('year')+'-'+part('month')+'-'+part('day');
   if(!/^20\d\d-\d\d-\d\d$/.test(explicitDay||''))date.value=today;
   const priority=doc.getElementById('prioridade');if(priority){let normal=Array.from(priority.options).find(o=>o.value.toLowerCase()==='normal');if(!normal){normal=new Option('Normal','normal');priority.add(normal);}priority.value=normal.value;}
   const technician=doc.getElementById('tecnico');if(technician){const nativeNone=Array.from(technician.options).find(o=>!o.value||o.value==='0'||/^nenhum$/i.test(o.textContent.trim()));const noneValue=nativeNone?nativeNone.value:'0';if(nativeNone)nativeNone.remove();const placeholder=new Option('Selecione o técnico','');placeholder.disabled=true;technician.prepend(placeholder);technician.add(new Option('Não informar',noneValue||'0'));technician.value='';technician.required=true;}
  }
  let viability=null;
  if(!installation){
   const client=doc.getElementById('cliente'),box=doc.querySelector('.suite-ticket-client .suite-ticket-fields');
   if(client&&box){
    const toggleLabel=doc.createElement('label');toggleLabel.style.cssText='grid-column:1/-1;display:flex;align-items:center;gap:9px;padding:12px;background:#edf5ff;border-radius:9px;font-weight:600';const toggle=doc.createElement('input');toggle.type='checkbox';toggle.style.width='auto';toggleLabel.append(toggle,doc.createTextNode('Suporte de viabilidade · sem cliente cadastrado'));box.prepend(toggleLabel);
    const fields=doc.createElement('div');fields.hidden=true;fields.style.cssText='grid-column:1/-1';const grid=doc.createElement('div');grid.style.cssText='display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px';fields.append(grid);box.append(fields);
    const values={};for(const [key,label] of Object.entries({name:'Nome',contact:'Contato',address:'Endereço',district:'Bairro',city:'Cidade',complement:'Complemento',observation:'Observação'})){const wrap=doc.createElement('label');wrap.style.cssText='display:grid;gap:5px;font-size:12px';wrap.textContent=label+' (opcional)';const input=doc.createElement(key==='observation'?'textarea':'input');input.className='input';input.maxLength=key==='observation'?8000:255;input.style.cssText='width:100%;min-width:0;box-sizing:border-box';if(key==='observation')wrap.style.gridColumn='1/-1';wrap.append(input);grid.append(wrap);values[key]=input;}
    const originalRequired={client:client.required,message:message.required};const clientRow=client.closest('.field.is-horizontal');
    toggle.onchange=()=>{fields.hidden=!toggle.checked;client.disabled=toggle.checked;client.required=toggle.checked?false:originalRequired.client;if(clientRow){clientRow.hidden=toggle.checked;if(toggle.checked)clientRow.style.setProperty('display','none','important');else clientRow.style.removeProperty('display');}message.required=toggle.checked?false:originalRequired.message;};
    viability={active:()=>toggle.checked,values,key:'viability_'+Date.now()+'_'+Math.random().toString(36).slice(2)};
   }
  }
  const originalTime=time.value;
  if(ramal&&!installation){if(!Array.from(ramal.options).some(o=>o.value===''))ramal.prepend(new Option('Não informar',''));ramal.value='';ramal.required=false;}
  const row=time.closest('.field.is-horizontal'),controls=doc.createElement('div');controls.className='suite-booking-controls';
  const period=doc.createElement('select');period.setAttribute('aria-label','Período da visita');period.innerHTML='<option value="">Selecione o período</option><option value="manha">Manhã</option><option value="tarde">Tarde</option><option value="outro">Outro período / horário</option>';period.required=true;
  const hours=doc.createElement('select');hours.setAttribute('aria-label','Horário da visita');hours.disabled=true;
  const custom=doc.createElement('input');custom.type='time';custom.step='60';custom.setAttribute('aria-label','Horário combinado');custom.hidden=true;
  const agenda=doc.createElement('button');agenda.type='button';agenda.textContent='Ver agenda';agenda.className='suite-agenda-open';
  const hint=doc.createElement('p');hint.className='suite-booking-hint';hint.setAttribute('role','status');
  time.style.display='none';time.required=false;time.value='';row.querySelector('.field-label .label').textContent='Período e horário';row.querySelector('.control').append(controls);controls.append(period,hours,custom,agenda,hint);
  let lunch={enabled:false},events=[],loadedDay='',sequence=0,approved=false;
  const dayEvents=()=>events.filter(e=>e.visit.slice(0,10)===date.value);
  const conflicts=t=>dayEvents().filter(e=>!e.period&&e.visit.slice(11,13)===t.slice(0,2));
  function hintText(){if(date.value&&Array.isArray(lunch.days)&&!lunch.days.includes(new Date(date.value+'T12:00:00').getDay())){hours.disabled=true;hint.textContent='Dia sem expediente para '+(installation?'instalações.':'ordens de serviço.')+' Escolha outro dia.';return;}hours.disabled=!period.value;const matches=time.value?conflicts(time.value):[];hint.textContent=matches.length?'Já existem '+matches.length+' visitas nesta faixa. Será possível continuar organizando '+(matches.length+1)+' equipes.':time.value?'Horário livre na agenda que seu usuário pode consultar.':period.value?(installation?'Selecione o horário da instalação.':'Visita por período, sem horário exato.'):'';}
  function fill(){const previous=hours.value;hours.replaceChildren(new Option(installation?'Selecione o horário':'Sem horário definido',''));let first=period.value==='tarde'?12:8,last=period.value==='manha'?11:18;for(let h=first;h<=last;h++){const t=String(h).padStart(2,'0')+':00',count=conflicts(t).length;const reserved=lunch.enabled&&t>=lunch.start&&t<lunch.end;const option=new Option(t+(reserved?' · Almoço reservado':count?' · '+count+' visita(s)':' · Livre'),t);option.disabled=reserved;hours.add(option);}hours.add(new Option('Informar outro horário','custom'));if(Array.from(hours.options).some(o=>o.value===previous))hours.value=previous;hintText();}
  async function load(){if(!date.value)return;const current=++sequence;hint.textContent='Consultando agenda…';const r=await fetch(endpoint+'?kind='+kind+'&day='+encodeURIComponent(date.value),{credentials:'same-origin',cache:'no-store'});const data=await r.json();if(!r.ok)throw Error(data.error);if(current!==sequence)return;events=data.events.filter(e=>e.kind===kind);lunch=data.lunch||{enabled:false};loadedDay=date.value;fill();return data;}
  function changePeriod(){hours.disabled=!period.value;custom.hidden=true;custom.required=false;custom.value='';time.value='';fill();if(period.value==='outro'){hours.value='custom';custom.hidden=false;custom.required=true;}approved=false;}
  period.onchange=changePeriod;hours.onchange=()=>{custom.hidden=hours.value!=='custom';custom.required=hours.value==='custom';time.value=hours.value==='custom'?custom.value:hours.value;approved=false;hintText();};custom.oninput=()=>{time.value=custom.value;approved=false;hintText();};date.addEventListener('change',()=>{approved=false;load().catch(e=>hint.textContent=e.message);});
  agenda.onclick=async()=>{
   try{if(!window.mkaVisitAgenda)await new Promise((resolve,reject)=>{const script=document.createElement('script');script.src='/admin/addons/shared/visit_agenda.js?v=20261016-2';script.onload=resolve;script.onerror=reject;document.head.append(script);});
    await window.mkaVisitAgenda.open({doc,kind,day:date.value,onSelect:async(value,close)=>{if(value.events.length&&!window.confirm('Já há '+value.events.length+' visitas nesta faixa. Organize '+(value.events.length+1)+' equipes para atender simultaneamente. Deseja selecionar mesmo assim?'))return;date.value=value.day;period.value=Number(value.time.slice(0,2))<12?'manha':'tarde';changePeriod();await load();hours.value=value.time;time.value=value.time;hintText();close();}});
   }catch(error){hint.textContent=error.message;}
  };
  form.addEventListener('submit',async event=>{
   if(approved){approved=false;return;}event.preventDefault();event.stopImmediatePropagation();if(!form.reportValidity())return;
   try{await load();if(Array.isArray(lunch.days)&&!lunch.days.includes(new Date(date.value+'T12:00:00').getDay()))throw Error('Este dia não está habilitado para '+(installation?'instalações.':'ordens de serviço.'));if(loadedDay!==date.value)throw Error('Confirme a agenda antes de gravar.');if(time.value&&lunch.enabled&&time.value>=lunch.start&&time.value<lunch.end)throw Error('Este horário está reservado para almoço ('+lunch.start+'–'+lunch.end+'). Escolha outro horário.');if(time.value&&(time.value<'08:00'||time.value>'18:00')&&!window.confirm('Este horário está fora do expediente das 08h às 18h. Foi combinado com o cliente e deseja continuar?'))return;
    const count=time.value?conflicts(time.value).length:0;if(count&&!window.confirm('Já existem '+count+' visitas nesta faixa. Organize '+(count+1)+' equipes para atender simultaneamente. Deseja gravar mesmo assim?'))return;
    message.value=message.value.replace(/\n?\[Periodo da visita: (manha|tarde); sem horario definido\]/g,'');if(!time.value){if(installation)throw Error('Selecione o horário da instalação.');if(!['manha','tarde'].includes(period.value))throw Error('Escolha manhã, tarde ou informe o horário.');message.value+='\n[Periodo da visita: '+period.value+'; sem horario definido]';time.value='00:00';}
    if(viability&&viability.active()){
     const endpoint='/admin/addons/busca_inteligente/ticket_viability_api.php';let response=await fetch(endpoint,{credentials:'same-origin',cache:'no-store'}),session=await response.json();if(!response.ok)throw Error(session.error);
     const data={csrf:session.csrf,key:viability.key,day:date.value,time:time.value,period:period.value,technician:doc.getElementById('tecnico').value,priority:doc.getElementById('prioridade').value};for(const [key,input] of Object.entries(viability.values))data[key]=input.value;
     if(message.value.trim())data.observation=(data.observation?data.observation+'\n':'')+message.value.replace(/\n?\[Periodo da visita: (manha|tarde); sem horario definido\]/g,'').trim();
     response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});const result=await response.json();if(!response.ok)throw Error(result.error);hint.textContent='Chamado de viabilidade registrado.';for(const control of form.querySelectorAll('input,select,textarea,button'))control.disabled=true;const link=doc.createElement('a');link.href='/admin/addons/busca_inteligente/chamados_abertos.php?view=list';link.target='_top';link.textContent='Ver na lista de chamados';hint.append(doc.createElement('br'),link);return;
    }
    approved=true;form.requestSubmit(event.submitter||undefined);
   }catch(error){hint.textContent=error.message;}
  },true);
  if(window.mkaPriorityColors){const priority=doc.getElementById('prioridade');const paint=()=>{if(priority)priority.style.borderLeft='5px solid '+window.mkaPriorityColors.color(priority.value);};if(priority){priority.addEventListener('change',paint);new MutationObserver(paint).observe(priority,{childList:true,subtree:true});}window.mkaPriorityColors.load().then(paint).catch(()=>{});paint();}
  const prefill=new URLSearchParams(doc.defaultView.location.search),prefillDay=prefill.get('suite_day'),prefillTime=prefill.get('suite_time');if(/^20\d\d-\d\d-\d\d$/.test(prefillDay||'')&&/^\d\d:\d\d$/.test(prefillTime||'')){date.value=prefillDay;period.value=Number(prefillTime.slice(0,2))<12?'manha':'tarde';changePeriod();load().then(()=>{hours.value=prefillTime;time.value=prefillTime;hintText();}).catch(e=>hint.textContent=e.message);}else if(installation&&originalTime){period.value=Number(originalTime.slice(0,2))<12?'manha':'tarde';changePeriod();load().then(()=>{if(Array.from(hours.options).some(o=>o.value===originalTime))hours.value=originalTime;else{hours.value='custom';custom.hidden=false;custom.value=originalTime;}time.value=originalTime;hintText();}).catch(e=>hint.textContent=e.message);}else load().catch(e=>hint.textContent=e.message);
 };
})();
