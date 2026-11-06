(function(){'use strict';
 const root=document.querySelector('[data-finance-today]');if(!root)return;
 let timer=null,busy=false,controller=null,loaded=false,pending=false;
 const money=new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'});
 const find=key=>root.querySelector('[data-finance-'+key+']');
 const refresh=find('refresh'),status=find('updated');
 const buttons=Array.from(root.querySelectorAll('[data-finance-period]'));
 const preferenceKey=root.getAttribute('data-finance-preference-key')||'mka.finance.period';
 let period='day';
 try{const saved=localStorage.getItem(preferenceKey);if(saved==='day'||saved==='week')period=saved;}catch(e){}
 function markPeriod(){buttons.forEach(button=>button.setAttribute('aria-pressed',String(button.getAttribute('data-finance-period')===period)));}
 markPeriod();
 const date=value=>value.split('-').reverse().join('/');
 function cash(key,value){find(key).textContent=money.format(Number(value));}
 async function update(){
  clearTimeout(timer);if(document.hidden)return;if(busy){pending=true;controller.abort();return;}busy=true;const selectedPeriod=period;refresh.disabled=true;controller=new AbortController();
  const timeout=setTimeout(()=>controller.abort(),10000);
  try{
   const response=await fetch('/admin/addons/dashboard/finance_today_api.php?period='+encodeURIComponent(selectedPeriod),{credentials:'same-origin',cache:'no-store',signal:controller.signal});
   if(response.status===403){root.hidden=true;return;}
   const d=await response.json();if(!response.ok||!d.ok)throw Error('unavailable');
   if(selectedPeriod!==(period))return;
   const week=d.period==='week';
   find('eyebrow').textContent=week?'FINANCEIRO · SEMANA':'FINANCEIRO · HOJE';find('title').textContent=week?'Acompanhe a semana':'Acompanhe o dia';
   find('due-title').textContent=week?'A receber nesta semana':'A receber hoje';find('cash-title').textContent=week?'Caixa da semana':'Caixa de hoje';
   find('due-note').textContent=(week?'Vencimento de domingo a sábado':'Vencimento hoje')+' · títulos em aberto e vencidos, sem encargos calculados.';
   find('balance-note').textContent=(week?'Saldo da semana':'Saldo do dia')+' · entradas menos saídas';
   find('due').hidden=!d.due;find('cash').hidden=!d.cash;
   if(d.due){cash('amount',d.due.amount);find('quantity').textContent=d.due.quantity+' título(s) a receber'+(Number(d.due.overdue_quantity)>0?' · '+d.due.overdue_quantity+' em atraso':'');}
   if(d.cash){cash('incoming',d.cash.incoming);cash('outgoing',d.cash.outgoing);find('balance-wrap').hidden=d.cash.balance===undefined;if(d.cash.balance!==undefined){cash('balance',d.cash.balance);find('balance').classList.toggle('is-negative',Number(d.cash.balance)<0);}}
   if(!d.due&&!d.cash){root.hidden=true;return;}
   root.hidden=false;loaded=true;
   status.textContent='Atualizado às '+d.updated_at.slice(11,19)+' · '+(d.range?date(d.range.start)+' a '+date(d.range.last):date(d.day));
  }catch(e){if(pending||document.hidden)return;status.textContent=loaded?'Não atualizado agora · valores da última consulta.':'Não foi possível consultar os valores. Tente atualizar.';}
  finally{clearTimeout(timeout);busy=false;refresh.disabled=false;if(pending){pending=false;update();}else if(!document.hidden&&!root.hidden)timer=setTimeout(update,30000);}
 }
 refresh.addEventListener('click',update);
 buttons.forEach(button=>button.addEventListener('click',()=>{
  const value=button.getAttribute('data-finance-period');if((value!=='day'&&value!=='week')||value===period)return;
  period=value;markPeriod();try{localStorage.setItem(preferenceKey,period);}catch(e){}
  status.textContent='Atualizando período…';update();
 }));
 document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(document.hidden){if(controller)controller.abort();}else update();});
 window.addEventListener('pagehide',()=>{clearTimeout(timer);if(controller)controller.abort();});
 update();
}());
