(function(){
'use strict';
var config=JSON.parse(document.currentScript.getAttribute('data-repair-config')),button=document.getElementById('runRepair'),box=document.getElementById('repairProgress'),title=document.getElementById('repairTitle'),message=document.getElementById('repairMessage'),sessionCard=document.getElementById('diagnostic-session');
var timer=null,controller=null,version=0,tracking=false,repairing=false,offlineSeen=false,started=0,pending=false,confirmed=false,lastSession=config.baseline_session;
function schedule(){clearTimeout(timer);if(tracking)timer=setTimeout(poll,5000);}
function updateSession(s){if(!sessionCard)return;var value=sessionCard.querySelector('.value'),detail=sessionCard.querySelector('small');sessionCard.classList.toggle('ok',s.online);sessionCard.classList.toggle('warn',!s.online);if(value)value.textContent=s.online?s.uptime:'Offline';if(detail)detail.textContent=s.online?'Online no IP '+s.ip+' pelo concentrador '+s.nas+'. Atualizado agora.':'Nenhuma sessão ativa no RADIUS. Acompanhamento em andamento.';}
async function poll(){
 if(!tracking||repairing||pending)return;pending=true;var current=version;controller=new AbortController();var timeout=setTimeout(function(){controller.abort();},10000);
 try{var response=await fetch('client_connection_status.php?login='+encodeURIComponent(config.login),{credentials:'same-origin',cache:'no-store',signal:controller.signal});if(!response.ok)throw Error();var s=await response.json();if(current!==version||!tracking)return;updateSession(s);
  if(!s.online)offlineSeen=true;
  var reconnected=s.online&&(confirmed||offlineSeen||!config.baseline_session||String(s.session_id)!==String(config.baseline_session));
  if(reconnected){confirmed=true;lastSession=s.session_id;box.classList.add('ok');title.textContent='Cliente conectado';message.textContent='IP '+s.ip+' · uptime '+s.uptime+' · concentrador '+s.nas+'. Atualização automática ativa.';button.disabled=false;button.textContent='Executar reparo novamente';}
  else{box.classList.remove('ok');title.textContent=s.online?'Aguardando renovação da sessão':'Aguardando reconexão — acompanhamento ativo';message.textContent='Verificação automática a cada 5 segundos enquanto este diagnóstico estiver aberto. Última consulta: '+new Date().toLocaleTimeString('pt-BR')+'.';if(Date.now()-started>90000)button.disabled=false;}
 }catch(e){if(current===version&&tracking){title.textContent='Acompanhamento temporariamente indisponível';message.textContent='Não foi possível consultar a conexão. Tentaremos novamente automaticamente.';button.disabled=false;}}
 finally{pending=false;clearTimeout(timeout);if(current===version)schedule();}
}
button.addEventListener('click',async function(){
 if(repairing||!confirm('Deseja executar agora o reparo '+(config.additional?'deste adicional':'deste cliente')+'?'))return;
 clearTimeout(timer);if(controller)controller.abort();version++;repairing=true;tracking=false;offlineSeen=false;confirmed=false;config.baseline_session=lastSession;button.disabled=true;box.className='repair-progress show';title.textContent='Executando reparo…';message.textContent='Aguarde enquanto o cadastro é atualizado.';
 var data=new FormData();if(config.additional){data.append('uuid',config.additional);data.append('csrf',config.csrf);data.append('action','repair');}else data.append('login[]',config.login);
 try{var response=await fetch(config.repair_url,{method:'POST',credentials:'same-origin',body:data});if(!response.ok)throw Error();if(config.additional){var result=await response.json();if(!result.ok)throw Error();}else{var text=await response.text();if(/acesso negado/i.test(text))throw Error();}tracking=true;started=Date.now();title.textContent='Reparo executado — acompanhando conexão';message.textContent='A verificação continuará enquanto o diagnóstico estiver aberto.';}
 catch(e){title.textContent='Não foi possível executar o reparo';message.textContent='Confira a permissão e tente novamente.';button.disabled=false;}
 finally{repairing=false;if(tracking)poll();}
});
function stop(){tracking=false;version++;clearTimeout(timer);if(controller)controller.abort();}
window.addEventListener('pagehide',stop);window.addEventListener('beforeunload',stop);
document.addEventListener('visibilitychange',function(){if(!document.hidden&&tracking&&!repairing){clearTimeout(timer);if(!controller||!controller.signal.aborted)poll();}});
}());
