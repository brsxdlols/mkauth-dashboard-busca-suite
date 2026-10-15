(function(){
 'use strict';
 let modal,frame,submitted=false,trigger=null;
 function build(){
  modal=document.createElement('dialog');modal.className='ticket-native-modal';modal.setAttribute('aria-labelledby','ticket-native-title');
  modal.innerHTML='<header><h2 id="ticket-native-title">Abrir chamado</h2><button type="button" data-native-close>Fechar</button></header><p class="ticket-native-status" role="status">Carregando formulário…</p><iframe title="Formulário nativo de abertura de chamado"></iframe>';
  frame=modal.querySelector('iframe');document.body.append(modal);modal.querySelector('[data-native-close]').onclick=()=>modal.close();
  frame.addEventListener('load',()=>{
   if(!modal.open)return;
   try{
    const doc=frame.contentDocument;if(!doc)return;
    const style=doc.createElement('style');style.textContent='html.has-navbar-fixed-top{padding-top:0!important}nav.navbar,footer.footer,#sistema-rodape{display:none!important}';doc.head.append(style);
    for(const form of doc.querySelectorAll('form'))form.addEventListener('submit',()=>{submitted=true;});
    modal.querySelector('[role=status]').textContent=submitted?'Após concluir a gravação, feche este modal para atualizar o calendário.':'Preencha e grave o chamado no formulário do MK-AUTH.';
   }catch(error){modal.querySelector('[role=status]').textContent='Confira o formulário e feche o modal ao concluir.';}
  });
  modal.addEventListener('close',()=>{frame.src='about:blank';if(trigger&&trigger.isConnected)trigger.focus();if(submitted){document.dispatchEvent(new CustomEvent('mka-ticket-native-refresh'));location.reload();}});
 }
 document.addEventListener('click',event=>{
  const link=event.target.closest('a[data-open-native-ticket]');if(!link)return;
  const url=new URL(link.href,location.href);if(url.origin!==location.origin||!/^\/admin\/suporte_ins\.(hhvm|php)$/.test(url.pathname))return;
  event.preventDefault();if(!modal)build();trigger=link;submitted=false;modal.querySelector('[role=status]').textContent='Carregando formulário…';frame.src=url.href;modal.showModal();
 });
})();
