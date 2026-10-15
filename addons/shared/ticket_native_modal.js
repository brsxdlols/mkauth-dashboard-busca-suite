(function(){
 'use strict';
 let modal,frame,submitted=false,trigger=null;
 function styleForm(doc){
  const form=doc.querySelector('form#form[action*="executar_suporte"],form[action*="ins.chamado"]');
  if(!form||!form.querySelector('#cliente')||!form.querySelector('#uuid_cliente')||!form.querySelector('#msg'))return false;
  const rows=Array.from(form.querySelectorAll(':scope > .field.is-horizontal'));
  if(!rows.length)return false;
  const section=(title,cls)=>{const box=doc.createElement('section');box.className='suite-ticket-section '+(cls||'');const heading=doc.createElement('h3');heading.textContent=title;const grid=doc.createElement('div');grid.className='suite-ticket-fields';box.append(heading,grid);form.append(box);return grid;};
  form.classList.add('suite-ticket-form');
  const client=section('Cliente e atendimento','suite-ticket-client'),schedule=section('Agendamento e equipe'),description=section('Detalhes do chamado');
  let extra=null;const metadata=doc.createElement('div');metadata.className='suite-ticket-metadata';
  for(const row of rows){
   const control=row.querySelector('input:not([type=hidden]),select,textarea'),label=row.querySelector('.field-label label');
   if(label){label.textContent=label.textContent.trim().replace(/:\s*$/,'');if(control&&control.id)label.htmlFor=control.id;}
   if(row.querySelector('[type=submit]')){row.classList.add('suite-ticket-submit');form.append(row);}
   else if(control&&control.id==='cliente'){row.classList.add('suite-ticket-wide');client.append(row);}
   else if(control&&['tecnico','ramal','visita_data','visita_hora','prioridade'].includes(control.id)){schedule.append(row);if(label&&control.id==='tecnico')label.textContent='Técnico';if(label&&control.id==='visita_data')label.textContent='Data da visita';if(label&&control.id==='visita_hora')label.textContent='Horário da visita';}
   else if(control&&['assunto','msg'].includes(control.id)){row.classList.add('suite-ticket-wide');description.append(row);if(label&&control.id==='msg')label.textContent='Relato e orientações';}
   else if(!control){row.classList.add('suite-ticket-readonly');metadata.append(row);}
   else{if(!extra)extra=section('Outras informações');extra.append(row);}
  }
  if(metadata.childElementCount)client.append(metadata);
  const submit=form.querySelector('.suite-ticket-submit');if(submit)form.append(submit);
  const root=doc.createElement('main');root.id='suite-ticket-root';root.append(form);
  // Move the existing nodes so native validation, client lookup and hidden fields keep working.
  for(const overlay of Array.from(doc.querySelectorAll('.modal,.quickview,.ui-autocomplete,.autocomplete-sugestoes,.autocomplete-suggestions,.ui-helper-hidden-accessible')))if(!form.contains(overlay))doc.body.append(overlay);
  doc.body.append(root);doc.body.classList.add('suite-ticket-body');
  return true;
 }
 function build(){
  modal=document.createElement('dialog');modal.className='ticket-native-modal';modal.setAttribute('aria-labelledby','ticket-native-title');
  modal.innerHTML='<header><h2 id="ticket-native-title">Abrir chamado</h2><button type="button" data-native-close>Fechar</button></header><p class="ticket-native-status" role="status">Carregando formulário…</p><iframe title="Formulário de abertura de chamado"></iframe>';
  frame=modal.querySelector('iframe');document.body.append(modal);modal.querySelector('[data-native-close]').onclick=()=>modal.close();
  frame.addEventListener('load',async()=>{
   if(!modal.open)return;
   try{
    const doc=frame.contentDocument;if(!doc)return;
    const style=doc.createElement('style');style.textContent='html.has-navbar-fixed-top{padding-top:0!important}nav.navbar,footer.footer,#sistema-rodape{display:none!important}';doc.head.append(style);
    const styled=styleForm(doc);
    if(styled){const css=doc.createElement('link');css.rel='stylesheet';css.href='/admin/addons/shared/ticket_native_form.css?v=20261015-7';await new Promise(resolve=>{css.onload=resolve;css.onerror=resolve;doc.head.append(css);setTimeout(resolve,4000);});}
    if(!modal.open||frame.contentDocument!==doc)return;frame.style.visibility='visible';
    for(const form of doc.querySelectorAll('form'))form.addEventListener('submit',()=>{submitted=true;});
    modal.querySelector('[role=status]').textContent=submitted?'Após concluir a gravação, feche este modal para atualizar o calendário.':'Selecione o cliente, organize a visita e descreva o atendimento.';
   }catch(error){frame.style.visibility='visible';modal.querySelector('[role=status]').textContent='Confira o formulário e feche o modal ao concluir.';}
  });
  modal.addEventListener('close',()=>{frame.src='about:blank';if(trigger&&trigger.isConnected)trigger.focus();if(submitted){document.dispatchEvent(new CustomEvent('mka-ticket-native-refresh'));location.reload();}});
 }
 document.addEventListener('click',event=>{
  const link=event.target.closest('a[data-open-native-ticket]');if(!link)return;
  const url=new URL(link.href,location.href);if(url.origin!==location.origin||!/^\/admin\/suporte_ins\.(hhvm|php)$/.test(url.pathname))return;
  event.preventDefault();if(!modal)build();trigger=link;submitted=false;modal.querySelector('[role=status]').textContent='Carregando formulário…';frame.style.visibility='hidden';frame.src=url.href;modal.showModal();
 });
})();
