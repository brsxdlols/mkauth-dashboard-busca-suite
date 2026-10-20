(function(){
'use strict';
if(window.mkaClientInvoicesLoaded)return;window.mkaClientInvoicesLoaded=true;
var scrollState=null, selectedCard=null, activeTrigger=null, highlightTimer=null;
var child,childBody,childTitle,childTools,childStatus,childFrame,childController,childRefresh=false;
var modal,body,caption,detailsLink,prev,next,pager,controller,generation=0,uuid,currentPage=1;
function node(tag,text){var el=document.createElement(tag);if(text!==undefined)el.textContent=text;return el;}
function money(value){var raw=String(value||'').replace(/[^0-9,.-]/g,'');if(raw.includes(','))raw=raw.replace(/\./g,'').replace(',','.');var amount=Number(raw);return raw!==''&&Number.isFinite(amount)?amount.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}):String(value||'—');}
function date(value){var match=String(value||'').match(/^(\d{4})-(\d{2})-(\d{2})/);return match?match[3]+'/'+match[2]+'/'+match[1]:'Não informado';}
function clearCardHighlightLater(){
 if(highlightTimer!==null)window.clearTimeout(highlightTimer);
 var card=selectedCard;
 highlightTimer=window.setTimeout(function(){highlightTimer=null;if(card&&(!modal||!modal.open))card.classList.remove('invoice-client-selected');},2000);
}
function centerSelectedCard(){
 if(!selectedCard||scrollState)return;
 var root=document.documentElement,rect=selectedCard.getBoundingClientRect();
 var height=window.innerHeight||root.clientHeight;
 var target=Math.max(0,window.scrollY+rect.top+rect.height/2-height/2);
 var behavior=[root.style.getPropertyValue('scroll-behavior'),root.style.getPropertyPriority('scroll-behavior')];
 root.style.setProperty('scroll-behavior','auto');window.scrollTo(window.scrollX,target);
 if(behavior[0])root.style.setProperty('scroll-behavior',behavior[0],behavior[1]);else root.style.removeProperty('scroll-behavior');
}
function lockPage(){
 if(scrollState)return;
 var root=document.documentElement,page=document.body;
 scrollState={x:window.scrollX,y:window.scrollY,body:{},root:{}};
 ['position','top','left','width','overflow','overscroll-behavior','padding-right'].forEach(function(key){scrollState.body[key]=[page.style.getPropertyValue(key),page.style.getPropertyPriority(key)];});
 ['overflow','scroll-behavior'].forEach(function(key){scrollState.root[key]=[root.style.getPropertyValue(key),root.style.getPropertyPriority(key)];});
 var gap=Math.max(0,window.innerWidth-root.clientWidth),padding=parseFloat(window.getComputedStyle(page).paddingRight)||0;
 root.style.setProperty('overflow','hidden');page.style.setProperty('position','fixed');page.style.setProperty('top',(-scrollState.y)+'px');page.style.setProperty('left',(-scrollState.x)+'px');page.style.setProperty('width','100%');page.style.setProperty('overflow','hidden');page.style.setProperty('overscroll-behavior','none');
 if(gap)page.style.setProperty('padding-right',(padding+gap)+'px');
}
function unlockPage(){
 if(!scrollState)return;var saved=scrollState;scrollState=null;
 Object.keys(saved.body).forEach(function(key){var entry=saved.body[key];if(entry[0])document.body.style.setProperty(key,entry[0],entry[1]);else document.body.style.removeProperty(key);});
 var root=document.documentElement;root.style.setProperty('scroll-behavior','auto');var overflow=saved.root.overflow;if(overflow[0])root.style.setProperty('overflow',overflow[0],overflow[1]);else root.style.removeProperty('overflow');window.scrollTo(saved.x,saved.y);
 var behavior=saved.root['scroll-behavior'];if(behavior[0])root.style.setProperty('scroll-behavior',behavior[0],behavior[1]);else root.style.removeProperty('scroll-behavior');
 if(activeTrigger){activeTrigger.setAttribute('aria-expanded','false');activeTrigger.focus({preventScroll:true});}
 clearCardHighlightLater();
}
function rememberDetailsReturn(event){
 if(event&&(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||event.button>0))return;
 var saved={uuid:uuid,x:scrollState?scrollState.x:window.scrollX,y:scrollState?scrollState.y:window.scrollY,path:location.pathname+location.search};
 try{var state=Object.assign({},history.state||{});state.mkaInvoiceReturn=saved;history.replaceState(state,'');}catch(error){}
 if(modal&&modal.open)modal.close();
}
function restoreDetailsReturn(){
 var saved=history.state&&history.state.mkaInvoiceReturn;
 if(!saved||saved.path!==location.pathname+location.search)return;
 window.requestAnimationFrame(function(){window.requestAnimationFrame(function(){
  var trigger=Array.from(document.querySelectorAll('[data-invoices-uuid]')).find(function(link){return link.dataset.invoicesUuid===saved.uuid;});
  if(selectedCard)selectedCard.classList.remove('invoice-client-selected');selectedCard=trigger?trigger.closest('.client-row'):null;if(selectedCard)selectedCard.classList.add('invoice-client-selected');clearCardHighlightLater();
  var root=document.documentElement,behavior=[root.style.getPropertyValue('scroll-behavior'),root.style.getPropertyPriority('scroll-behavior')];root.style.setProperty('scroll-behavior','auto');window.scrollTo(Number(saved.x)||0,Number(saved.y)||0);if(behavior[0])root.style.setProperty('scroll-behavior',behavior[0],behavior[1]);else root.style.removeProperty('scroll-behavior');
  try{var state=Object.assign({},history.state||{});delete state.mkaInvoiceReturn;history.replaceState(state,'');}catch(error){}
 });});
}
window.addEventListener('pageshow',restoreDetailsReturn);
function create(){
 modal=node('dialog');modal.className='client-invoices-modal';modal.setAttribute('aria-labelledby','client-invoices-title');
 var header=node('header'),heading=node('div'),title=node('h2','Faturas em aberto');title.id='client-invoices-title';caption=node('p');heading.append(title,caption);var close=node('button','Fechar ×');close.type='button';close.addEventListener('click',function(){modal.close();});header.append(heading,close);
 body=node('div');body.className='client-invoices-body';body.setAttribute('aria-live','polite');
 var footer=node('footer');prev=node('button','Anterior');next=node('button','Próxima');prev.type=next.type='button';pager=node('span');footer.append(prev,pager,next);prev.addEventListener('click',function(){load(currentPage-1);});next.addEventListener('click',function(){load(currentPage+1);});
 var actions=node('div');actions.className='client-invoices-actions';detailsLink=node('a','Ver mais detalhes');detailsLink.hidden=true;actions.append(detailsLink);
 modal.append(header,body,actions,footer);document.body.append(modal);modal.addEventListener('close',function(){generation++;if(controller)controller.abort();unlockPage();});modal.addEventListener('click',function(event){if(event.target===modal){var r=modal.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)modal.close();}});
}
async function load(page){
 if(controller)controller.abort();detailsLink.hidden=true;controller=new AbortController();var ticket=++generation;prev.disabled=next.disabled=true;body.replaceChildren(node('p','Consultando faturas…'));pager.textContent='';
 try{var response=await fetch('/admin/addons/busca_inteligente/client_invoices_api.php?'+new URLSearchParams({uuid:uuid,page:page}),{credentials:'same-origin',cache:'no-store',signal:controller.signal});var data;try{data=await response.json();}catch(e){throw Error('Não foi possível consultar as faturas. Recarregue a página e tente novamente.');}if(!response.ok)throw Error(data.error||'Não foi possível consultar as faturas.');if(ticket!==generation||!modal.open)return;
 detailsLink.href=data.details_url;detailsLink.onclick=rememberDetailsReturn;detailsLink.target='_self';detailsLink.hidden=!data.details_url;
 caption.textContent=data.client.nome+' · '+data.client.login;currentPage=data.page;pager.textContent=data.total+' fatura(s) · Página '+data.page+' de '+data.pages;prev.disabled=data.page<=1;next.disabled=data.page>=data.pages;body.replaceChildren();
 if(!data.invoices.length){body.append(node('p','Nenhuma fatura em aberto para este cliente.'));return;}
 var table=node('table'),head=node('thead'),row=node('tr');['Fatura','Vencimento','Valor original','Situação','Ações'].forEach(function(text){var th=node('th',text);th.scope='col';row.append(th);});head.append(row);table.append(head);var tbody=node('tbody');data.invoices.forEach(function(invoice){var tr=node('tr');[invoice.recibo||String(invoice.id),date(invoice.datavenc),money(invoice.valor),Number(invoice.overdue)?'Vencida':'Em aberto'].forEach(function(value){tr.append(node('td',value));});if(invoice.boleto_url){var titleLink=node('a',invoice.recibo||String(invoice.id));titleLink.href='#boleto';titleLink.addEventListener('click',function(event){event.preventDefault();openNative('Boleto / PDF',invoice.boleto_url,'boleto',invoice);});tr.firstElementChild.replaceChildren(titleLink);}
if(Number(invoice.overdue))tr.lastElementChild.className='invoice-overdue';var action=node('td');action.className='invoice-row-actions';if(Number(invoice.overdue)){var calculate=node('button','Calcular juros');calculate.type='button';calculate.addEventListener('click',function(){calculateInterest(invoice,calculate,tr);});action.append(calculate);}
[['Receber',invoice.receive_url,'receive'],['Alterar',invoice.edit_url,'edit'],['Informações',invoice.info_url,'info'],['Boleto/PDF',invoice.boleto_url,'boleto']].forEach(function(item){if(!item[1])return;var link=node('a',item[0]);link.href='#'+item[2];link.addEventListener('click',function(event){event.preventDefault();openNative(item[0],item[1],item[2],invoice);});action.append(link);});
if(Number(invoice.has_pix)){var pix=node('button','PIX / QR Code');pix.type='button';pix.addEventListener('click',function(){openPix(invoice);});action.append(pix);}if(!action.children.length)action.textContent='—';tr.append(action);tbody.append(tr);});table.append(tbody);body.append(table,node('p','Use Calcular juros nas faturas vencidas para consultar os encargos de hoje.'));body.lastElementChild.className='invoice-note';
 }catch(error){if(error.name==='AbortError'||ticket!==generation)return;body.replaceChildren(node('p',error.message));body.firstElementChild.setAttribute('role','alert');}
}
function openChild(title){
 if(!child){
  child=node('dialog');child.className='client-invoice-action-modal';var header=node('header');childTitle=node('h2');childTitle.id='client-invoice-action-title';child.setAttribute('aria-labelledby',childTitle.id);var close=node('button','Voltar às faturas ×');close.type='button';close.addEventListener('click',function(){child.close();});header.append(childTitle,close);childTools=node('div');childTools.className='invoice-action-tools';childStatus=node('p');childStatus.setAttribute('role','status');childBody=node('div');childBody.className='invoice-action-body';child.append(header,childTools,childStatus,childBody);document.body.append(child);
  child.addEventListener('close',function(){if(childController)childController.abort();if(childFrame)childFrame.src='about:blank';childBody.replaceChildren();if(childRefresh&&modal.open){childRefresh=false;load(currentPage);}});
 }
 if(childController)childController.abort();childController=new AbortController();child.className='client-invoice-action-modal';childTitle.textContent=title;childBody.replaceChildren();childTools.replaceChildren();childStatus.textContent='';childFrame=null;childRefresh=false;if(!child.open)child.showModal();
}
function tool(label,callback){var button=node('button',label);button.type='button';button.addEventListener('click',callback);childTools.append(button);return button;}
function renderInvoiceInfo(doc,frame,invoice){
 var tables=Array.from(doc.querySelectorAll('table.table')).filter(function(table){return table.querySelector('tr.is-selected');});
 if(!tables.some(function(table){return /dados do t[ií]tulo/i.test(table.querySelector('tr.is-selected').textContent);}))return false;
 var grid=node('div');grid.className='invoice-info-grid';
 tables.forEach(function(table){
  var heading=table.querySelector('tr.is-selected'),title=heading.textContent.trim();if(/c[oó]digo pix/i.test(title))return;
  var card=node('section');card.className='invoice-info-card';card.append(node('h3',title));var fields=node('div');fields.className='invoice-info-fields';
  Array.from(table.rows).forEach(function(row){
   if(row===heading||row.classList.contains('is-selected'))return;
   var cells=Array.from(row.cells);if(!cells.length||row.querySelector('button,input,select,textarea'))return;
   if(cells.length===1){var content=cells[0].textContent.trim();if(content&&!/^informa[çc][õo]es/i.test(content))fields.append(node('p',content));return;}
   for(var i=0;i<cells.length;i+=2){if(!cells[i+1])break;var label=cells[i].textContent.trim(),value=cells[i+1].textContent.trim();if(!label&&!value)continue;var field=node('div');field.className='invoice-info-field';field.append(node('span',label),node('strong',value||'—'));fields.append(field);}
  });
  if(fields.children.length){card.append(fields);grid.append(card);}
 });
 var pix=doc.querySelector('#foo');
 if(pix&&pix.value.trim()){
  var card=node('section');card.className='invoice-info-card invoice-info-pix';card.append(node('h3','PIX · Copia e cola'));var code=node('textarea');code.readOnly=true;code.rows=3;code.value=pix.value.trim();code.setAttribute('aria-label','Código PIX copia e cola');card.append(code);var copy=node('button','Copiar PIX');copy.type='button';copy.addEventListener('click',async function(){try{await navigator.clipboard.writeText(code.value);copy.textContent='PIX copiado';}catch(error){code.focus();code.select();copy.textContent='Selecione e copie o código';}});card.append(copy);grid.append(card);
 }
 frame.className='invoice-native-info-source';childBody.append(grid);childStatus.textContent='Informações do MK-AUTH, organizadas para consulta.';
 if(invoice){[['Receber',invoice.receive_url,'receive'],['Alterar título',invoice.edit_url,'edit'],['Boleto / PDF',invoice.boleto_url,'boleto']].forEach(function(action){if(action[1])tool(action[0],function(){openNative(action[0],action[1],action[2],invoice);});});}
 return true;
}
function openNative(title,url,kind,invoice){
 openChild(title+(invoice?' · Fatura '+(invoice.recibo||invoice.id):''));child.classList.add('is-native');childRefresh=kind==='receive'||kind==='edit';childStatus.textContent='Carregando tela do MK-AUTH…';
 var frame=node('iframe');childFrame=frame;frame.title=title;frame.setAttribute('sandbox','allow-forms allow-scripts allow-same-origin allow-downloads allow-modals');frame.addEventListener('load',function(){
  if(!child.open||childFrame!==frame)return;childStatus.textContent=childRefresh?'Conclua a operação no formulário e volte às faturas para atualizar a lista.':'';
  try{var doc=frame.contentDocument;if(!doc||!doc.body)return;var style=doc.createElement('style');style.textContent='.navbar,#systopop,#systopo,#rodape,footer{display:none!important}html{padding-top:0!important}body{padding-top:12px!important}';doc.head.append(style);if(kind==='info'&&renderInvoiceInfo(doc,frame,invoice))return;doc.querySelectorAll('form').forEach(function(form){form.target='_self';});frame.contentWindow.open=function(next){if(next)frame.src=new URL(next,frame.contentWindow.location.href).href;return frame.contentWindow;};doc.addEventListener('keydown',function(event){if(event.key==='Escape'){event.preventDefault();child.close();}});
  }catch(error){childStatus.textContent='Conteúdo do emissor exibido dentro do modal.';}
 });frame.src=url;childBody.append(frame);
 if(kind==='boleto'){
  if(invoice&&invoice.download_url)tool('Baixar PDF',function(){downloadPdf(invoice);});
  tool('Imprimir / Salvar PDF',function(){try{frame.contentWindow.focus();frame.contentWindow.print();}catch(error){childStatus.textContent='A impressão deve ser feita pelos controles do documento exibido.';}});
 }
 if((kind==='info'||kind==='boleto')&&invoice&&Number(invoice.has_pix))tool('PIX / QR Code e copia e cola',function(){openPix(invoice);});
}
async function downloadPdf(invoice){
 childStatus.textContent='Preparando download do PDF…';
 try{var response=await fetch(invoice.download_url,{credentials:'same-origin',cache:'no-store',signal:childController.signal});if(!response.ok){var error=await response.json();throw Error(error.error||'PDF indisponível.');}if(!String(response.headers.get('Content-Type')).includes('application/pdf'))throw Error('O emissor não retornou um PDF.');var blob=await response.blob();if(!child.open)return;var url=URL.createObjectURL(blob),link=node('a');link.href=url;link.download='boleto-'+invoice.id+'.pdf';document.body.append(link);link.click();link.remove();setTimeout(function(){URL.revokeObjectURL(url);},60000);childStatus.textContent='Download do PDF iniciado.';
 }catch(error){if(error.name!=='AbortError')childStatus.textContent=error.message||'Não foi possível baixar o PDF.';}
}
async function openPix(invoice){
 openChild('PIX / QR Code · Fatura '+(invoice.recibo||invoice.id));childStatus.textContent='Consultando PIX existente…';var signal=childController.signal;
 try{var response=await fetch('/admin/addons/busca_inteligente/client_invoices_api.php?'+new URLSearchParams({uuid:uuid,pix:invoice.id}),{credentials:'same-origin',cache:'no-store',signal:signal});var data=await response.json();if(!response.ok)throw Error(data.error||'Não foi possível consultar o PIX.');if(signal.aborted||!child.open)return;childStatus.textContent='Código PIX da fatura selecionada.';
 if(data.pix.image){var image=node('img');image.src=data.pix.image;image.alt='QR Code PIX desta fatura';image.width=image.height=220;childBody.append(image);}
 var code=node('textarea');code.value=data.pix.code;code.readOnly=true;code.rows=4;code.setAttribute('aria-label','Código PIX copia e cola');childBody.append(code);tool('Copiar PIX',async function(){try{await navigator.clipboard.writeText(code.value);childStatus.textContent='PIX copiado.';}catch(error){code.focus();code.select();childStatus.textContent='Selecione e copie o código PIX.';}});
 }catch(error){if(error.name!=='AbortError')childStatus.textContent=error.message;}
}
async function calculateInterest(invoice,button,row){
 var requestedUuid=uuid,ticket=generation;button.disabled=true;button.textContent='Calculando…';
 try{
  var response=await fetch('/admin/addons/busca_inteligente/client_invoices_api.php?'+new URLSearchParams({uuid:requestedUuid,invoice:invoice.id}),{credentials:'same-origin',cache:'no-store',signal:controller.signal});
  var data=await response.json();if(!response.ok)throw Error(data.error||'Não foi possível calcular os encargos.');if(ticket!==generation||!modal.open)return;
  var c=data.calculation,detail=node('tr'),cell=node('div');cell.colSpan=5;cell.className='invoice-calculation';detail.className='invoice-extra-row';
  cell.append(node('strong','Valor atualizado: '+money(c.total)),node('p',c.days_late+' dia(s) em atraso · Cálculo em '+date(c.as_of)));
  var lines=[['Base do título, com acréscimos/descontos',money(c.base)],['Multa única ('+c.fine_rate.toLocaleString('pt-BR')+'%)',money(c.fine)],['Juros/mora ('+c.daily_rate.toLocaleString('pt-BR')+'% ao dia × '+c.days_late+' dias)',money(c.interest)]];
  if(Number(c.rounding_adjustment))lines.push(['Ajuste de arredondamento',money(c.rounding_adjustment)]);
  lines.forEach(function(line){cell.append(node('p',line[0]+': '+line[1]));});cell.append(node('p','Taxas da conta do cliente, conforme cálculo nativo do MK-AUTH. Consulta sem alterar a fatura.'));
  openChild('Cálculo de juros · Fatura '+(invoice.recibo||invoice.id));childBody.append(cell);button.disabled=false;button.textContent='Ver cálculo';
 }catch(error){if(error.name==='AbortError'||ticket!==generation)return;button.disabled=false;button.textContent='Tentar calcular novamente';var errorLine=node('p',error.message||'Não foi possível calcular.');errorLine.setAttribute('role','alert');button.parentNode.append(errorLine);}
}
document.addEventListener('click',function(event){var target=event.target.closest('[data-invoices-uuid]');if(!target)return;event.preventDefault();if(!modal)create();uuid=target.dataset.invoicesUuid;caption.textContent='';
 if(activeTrigger)activeTrigger.setAttribute('aria-expanded','false');activeTrigger=target;activeTrigger.setAttribute('aria-expanded','true');
 if(highlightTimer!==null){window.clearTimeout(highlightTimer);highlightTimer=null;}if(selectedCard)selectedCard.classList.remove('invoice-client-selected');selectedCard=target.closest('.client-row');if(selectedCard)selectedCard.classList.add('invoice-client-selected');
 if(!modal.open){centerSelectedCard();lockPage();try{modal.showModal();}catch(error){unlockPage();throw error;}}load(1);});
}());
