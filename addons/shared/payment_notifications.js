(function(){'use strict';if(window.top!==window||window.mkaPaymentsLoaded||!location.pathname.startsWith('/admin/'))return;window.mkaPaymentsLoaded=true;
const endpoint='/admin/addons/dashboard/payment_events.php',tab=String(Date.now())+Math.random(),leaseKey='mka-payment-lease';let key='',csrf='',busy=false,duration=3,mode="simple",box;
function storage(k,v){try{if(v===undefined)return localStorage.getItem(k);localStorage.setItem(k,v);}catch(e){}return null;}
function host(){if(box)return box;box=document.createElement('aside');box.setAttribute('aria-live','polite');box.style.cssText='position:fixed;left:16px;bottom:16px;z-index:1090;width:min(360px,calc(100vw - 32px));max-height:70vh;overflow:hidden';document.body.append(box);return box;}
function show(event){
 const root=host(),card=document.createElement('section');card.style.cssText='position:relative;display:flex;gap:14px;background:#fff;color:#24344f;border:1px solid #e8edf3;border-left:4px solid #e4b333;border-radius:18px;padding:18px;box-shadow:0 12px 32px #14213d1a;margin-top:12px;font:13px/1.5 system-ui';
 const coin=document.createElement('div');coin.textContent='＄';coin.style.cssText='flex:0 0 46px;height:46px;display:grid;place-items:center;border-radius:15px;background:linear-gradient(135deg,#f9dc76,#d99d19);color:white;font-size:28px;font-weight:700;text-shadow:0 1px 2px #9c6900';
 const content=document.createElement('div');content.style.cssText='min-width:0;flex:1;padding-right:12px';
 const label=document.createElement('div');label.textContent=event.demo?'TESTE • PAGAMENTO RECEBIDO':'PAGAMENTO RECEBIDO';label.style.cssText='font-size:10px;letter-spacing:.7px;font-weight:800;color:#7b8ba7';
 const title=document.createElement('strong');title.textContent=event.name;title.style.cssText='display:block;font-size:14px;margin:4px 0 8px;overflow-wrap:anywhere';
 const body=document.createElement('div');body.style.cssText='color:#75839b;font-size:12px';if(mode==='detailed'){for(const value of ['Vencimento: '+(event.due||'—'),'Pago em: '+(event.paid||'—'),'Plano atual: '+(event.plan||'—'),'Referência: '+(event.description||'—')]){const line=document.createElement('div');line.textContent=value;body.append(line);}}
 const amount=document.createElement('span');amount.textContent=Number(event.amount||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});amount.style.cssText='display:inline-block;margin-top:10px;padding:5px 11px;border-radius:20px;background:#fff6da;color:#9b6a08;font-weight:500';body.append(amount);
 const controls=document.createElement('div');controls.style.cssText='display:flex;gap:12px;margin-top:10px';
 for(const [text,action] of [['Limpar',()=>root.replaceChildren()],['Não mostrar mais',async()=>{const r=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf,enabled:'0'})});if(r.ok){storage(key+'-disabled','1');root.replaceChildren();window.alert('Notificações desativadas. Para reativar, abra Configurações da Dashboard > Exibir notificações de pagamento > Sim.');}}]]){const b=document.createElement('button');b.type='button';b.textContent=text;b.style.cssText='border:0;background:none;padding:0;color:#78869b;font:11px system-ui;cursor:pointer';b.onclick=action;controls.append(b);}
 const close=document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','Fechar notificação de pagamento');close.style.cssText='position:absolute;right:10px;top:8px;border:0;background:none;color:#7b8ba7;font:20px system-ui;cursor:pointer';close.onclick=()=>card.remove();
 content.append(label,title,body,controls);card.append(coin,content,close);root.prepend(card);while(root.children.length>3)root.lastChild.remove();setTimeout(()=>card.remove(),duration*1000);while(root.children.length>1&&root.scrollHeight>window.innerHeight*.7)root.lastChild.remove();
}
async function poll(){
 if(busy||document.hidden)return;
 const last=Number(storage('mka-payment-last-poll')||0);
 if(key&&Date.now()-last<4500)return;
 busy=true;
 try{
  const after=key?storage(key+'-cursor'):null;
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),8000);
  let r;try{r=await fetch(endpoint+(after!==null?'?after='+encodeURIComponent(after):''),{signal:controller.signal,cache:'no-store'});}finally{clearTimeout(timeout);}
  if(!r.ok)return;
  const data=await r.json(),first=!key;
  duration=Math.max(1,Math.min(30,Number(data.duration_seconds)||3));mode=data.display_mode==='detailed'?'detailed':'simple';  csrf=data.csrf;key='mka-payments-'+data.user_key;
  storage(key+'-disabled',data.enabled?'0':'1');
  // Discover the account without advancing another tab's pending cursor.
  if(first&&storage(key+'-cursor')!==null)return;
  let seen;try{seen=JSON.parse(storage(key+'-seen')||'[]');}catch(e){seen=[];}
  if(data.enabled){for(const e of data.events||[]){if(seen.includes(String(e.id)))continue;show(e);seen.push(String(e.id));}}
  storage(key+'-seen',JSON.stringify(seen.slice(-200)));
  storage(key+'-cursor',String(data.cursor));
  storage('mka-payment-last-poll',String(Date.now()));
 }catch(e){}finally{busy=false;}
}
async function tick(){if(document.hidden)return;if(navigator.locks){await navigator.locks.request('mka-payment-poll',{ifAvailable:true},async lock=>{if(lock)await poll();});}else{const now=Date.now();let lease;try{lease=JSON.parse(storage(leaseKey)||'null');}catch(e){}if(lease&&lease.until>now&&lease.tab!==tab)return;storage(leaseKey,JSON.stringify({tab,until:now+12000}));await poll();}}
setInterval(tick,5000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)tick();});tick();})();
