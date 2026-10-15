(function(){
 'use strict';
 const root=document.querySelector('.attendance-page');if(!root)return;
 const form=document.getElementById('attendance-report-filters'),list=document.getElementById('attendance-report-list'),status=document.getElementById('attendance-report-status'),description=document.getElementById('attendance-report-period');
 const prev=document.querySelector('[data-attendance-prev]'),next=document.querySelector('[data-attendance-next]'),clearClient=document.querySelector('[data-attendance-clear-client]');
 const labels={open:'Em atendimento',resolved:'Resolvido',finished:'Finalizado sem OS',service:'Encaminhado para OS'};
 let page=1,pages=1,user='',uuid='',clientName='',sequence=0,controller=null;
 const initial=new URLSearchParams(location.search);for(const name of ['from','to','q','operator','outcome'])if(initial.has(name))form.elements[name].value=initial.get(name);
 if(initial.get('operator'))form.elements.operator.append(new Option(initial.get('operator'),initial.get('operator'),true,true));
 form.elements.all.checked=initial.get('all')==='1';uuid=initial.get('uuid')||'';clientName=initial.get('client_name')||'Cliente selecionado';page=Math.max(1,Number(initial.get('page'))||1);
 function node(tag,cls,text){const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;}
 function date(value){return String(value||'').replace(/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}:\d{2}).*)?$/,(all,y,m,d,time)=>d+'/'+m+'/'+y+(time?' '+time:''));}
 function sync(){form.elements.from.disabled=form.elements.to.disabled=form.elements.all.checked;clearClient.hidden=!uuid;}
 function params(){const values=new URLSearchParams();if(form.elements.all.checked)values.set('all','1');else{values.set('from',form.elements.from.value);values.set('to',form.elements.to.value);}for(const name of ['operator','q','outcome'])if(form.elements[name].value.trim())values.set(name,form.elements[name].value.trim());if(uuid){values.set('uuid',uuid);values.set('client_name',clientName);}if(page>1)values.set('page',String(page));return values;}
 function renderRecord(record){
  const item=node('details','attendance-history-item'),summary=node('summary'),who=node('div','attendance-history-client');who.append(node('strong','',record.client_name),node('span','',record.login),node('span','attendance-summary-subject',record.subject));
  const registered=node('div','');registered.append(node('small','','Abertura'),node('span','',date(record.created_at)));
  const operator=node('div','');operator.append(node('small','','Usuário / atendente'),node('span','',record.operator));
  const arrow=node('span','attendance-history-toggle','⌄');arrow.setAttribute('aria-hidden','true');summary.append(who,registered,operator,node('span','attendance-outcome outcome-'+record.outcome,labels[record.outcome]||record.outcome),arrow);item.append(summary);
  const body=node('div','attendance-history-content');body.append(node('strong','',record.subject),node('p','attendance-notes',record.notes));if(record.updated_at)body.append(node('p','attendance-history-update','Atualizado em '+date(record.updated_at)+' por '+record.updated_by));
  const actions=node('div','attendance-history-actions'),view=node('button','',record.can_edit?'Ver / editar registro':'Ver registro');view.type='button';view.dataset.attendanceRecord=record.id;
  const client=node('button','','Todos os atendimentos deste cliente');client.type='button';client.onclick=()=>{uuid=record.client_uuid;clientName=record.client_name;form.elements.q.value='';form.elements.all.checked=true;page=1;sync();load();};actions.append(view,client);body.append(actions);item.append(body);return item;
 }
 async function load(){
  const current=++sequence;if(controller)controller.abort();controller=new AbortController();status.textContent='Carregando atendimentos…';status.classList.remove('is-error');prev.disabled=next.disabled=true;
  const query=params();query.set('action','report');
  try{
   const response=await fetch('/admin/addons/busca_inteligente/attendance_api.php?'+query.toString(),{credentials:'same-origin',cache:'no-store',signal:controller.signal});let data;try{data=await response.json();}catch(error){throw Error('Não foi possível carregar os atendimentos. Confira sua sessão.');}if(!response.ok)throw Error(data.error||'Não foi possível carregar.');if(current!==sequence)return;
   user=data.logged_user;page=data.page;pages=data.pages;const selected=form.elements.operator.value;const operators=Array.from(new Set([...data.operators,user,selected].filter(Boolean)));form.elements.operator.replaceChildren(new Option('Todos os usuários',''),...operators.map(value=>new Option(value+(value===user?' (meu usuário)':''),value)));form.elements.operator.value=selected;
   const filters=data.filters;description.textContent=(filters.all?'Todo o período':filters.from===root.dataset.today&&filters.to===root.dataset.today?'Hoje · '+date(root.dataset.today):date(filters.from)+' a '+date(filters.to))+' · '+(filters.operator?'Usuário: '+filters.operator:'Todos os usuários')+(uuid?' · Cliente: '+clientName:filters.q?' · Cliente ou login: '+filters.q:'')+(filters.outcome?' · '+labels[filters.outcome]:'');
   status.textContent=data.total+' atendimento(s) encontrado(s), pela data de abertura.';list.replaceChildren(...data.records.map(renderRecord));if(!data.records.length)list.append(node('p','attendance-history-empty','Nenhum atendimento encontrado com esses filtros.'));
   document.getElementById('attendance-report-page').textContent='Página '+page+' de '+pages;prev.disabled=page<=1;next.disabled=page>=pages;
   const saved=params();history.replaceState(null,'',location.pathname+(saved.toString()?'?'+saved.toString():''));sync();
  }catch(error){if(error.name==='AbortError'||current!==sequence)return;status.textContent=error.message;status.classList.add('is-error');list.replaceChildren();}
 }
 form.onsubmit=event=>{event.preventDefault();page=1;load();};form.elements.all.onchange=sync;
 form.elements.q.oninput=()=>{if(uuid){uuid='';clientName='';sync();}};
 document.querySelector('[data-attendance-today]').onclick=()=>{form.elements.all.checked=false;form.elements.from.value=form.elements.to.value=root.dataset.today;page=1;sync();load();};
 document.querySelector('[data-attendance-mine]').onclick=()=>{if(!user){status.textContent='Aguarde o carregamento dos usuários.';return;}form.elements.operator.value=user;page=1;load();};
 clearClient.onclick=()=>{uuid='';clientName='';page=1;sync();load();};prev.onclick=()=>{if(page>1){page--;load();}};next.onclick=()=>{if(page<pages){page++;load();}};
 document.addEventListener('mka-attendance-changed',()=>load());sync();load();
})();
