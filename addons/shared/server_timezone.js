(function(){
'use strict';
var clock=document.getElementById('server-time-clock'),details=document.getElementById('server-time-details'),state=document.getElementById('server-time-state'),mode=document.getElementById('server_timezone_mode'),zone=document.getElementById('server_timezone_zone');
var enable=document.getElementById('server_timezone_enable'),apply=document.getElementById('server_timezone_apply');
if(!clock||!mode||!zone||!enable||!apply)return;
enable.checked=false;
var epoch=0,at=0,tz='UTC',snapshot=null;
var actualLabel=document.createElement('div');actualLabel.textContent='Horário atual do servidor';clock.before(actualLabel);
var preview=document.createElement('p');preview.id='server-time-preview';preview.setAttribute('role','status');preview.style.cssText='margin:14px 0;padding:12px;border:1px solid #d6e1ef;border-radius:8px;background:white';details.before(preview);
function showPreview(){
 var selected=mode.value==='manual'?zone.value:(snapshot&&snapshot.status?snapshot.status.detected_zone:null);
 if(!epoch||!selected){preview.textContent='Prévia: aguardando identificação do fuso.';return;}
 var instant=new Date(epoch*1000+performance.now()-at);
 var formatted=new Intl.DateTimeFormat('pt-BR',{timeZone:selected,dateStyle:'short',timeStyle:'medium'}).format(instant);
 var offset=new Intl.DateTimeFormat('pt-BR',{timeZone:selected,timeZoneName:'shortOffset'}).formatToParts(instant).filter(function(p){return p.type==='timeZoneName';})[0].value.replace('GMT','UTC');
 var pending=!snapshot.config.enabled||mode.value!==snapshot.config.mode||(mode.value==='manual'&&zone.value!==snapshot.config.zone);
 preview.textContent='Prévia do fuso selecionado: '+selected+' ('+offset+') — '+formatted+'. '+(pending?'Seleção ainda não salva. Clique em Aplicar somente este ajuste de fuso para solicitar a alteração.':'Configuração salva; consulte o estado de aplicação abaixo.');
}

function tick(){if(!epoch)return;clock.textContent=new Intl.DateTimeFormat('pt-BR',{timeZone:tz,dateStyle:'short',timeStyle:'medium'}).format(new Date(epoch*1000+performance.now()-at));showPreview();}
function toggle(){mode.disabled=!enable.checked;zone.disabled=!enable.checked||mode.value!=='manual';apply.disabled=!enable.checked;}
// Disabled fields are omitted by forms: retain the selection in a separate input.
var retained=document.createElement('input');retained.type='hidden';retained.name='server_timezone_zone';zone.form.append(retained);
function sync(){toggle();retained.disabled=!enable.checked||!zone.disabled;retained.value=zone.value;showPreview();}
enable.addEventListener('change',sync);
apply.addEventListener('click',function(event){if(!enable.checked||!window.confirm('Solicitar este ajuste de fuso? A troca de UTC será bloqueada se o RADIUS estiver ativo. Nenhuma conexão será derrubada automaticamente.'))event.preventDefault();});
mode.addEventListener('change',sync);zone.addEventListener('change',sync);sync();
async function refresh(){if(document.hidden)return;try{var r=await fetch('server_time.php',{credentials:'same-origin',cache:'no-store'});if(!r.ok)throw Error();var d=await r.json();snapshot=d;epoch=d.epoch;at=performance.now();tz=d.server_zone;tick();var p=d.provider||{},s=d.status||{};details.textContent='Servidor: '+d.server_zone+' (UTC'+d.offset+') · PHP: '+d.php_zone+' · Banco: '+(d.mysql?d.mysql.local_time:'indisponível')+'. Cadastro: '+(p.cidade||'?')+'/'+(p.estado||'?')+' — CEP '+(p.cep||'?')+'.';state.textContent=s.status==='error'?(s.message||'Não foi possível aplicar o fuso.'):'Detectado pelo cadastro: '+(s.detected_zone||'aguardando verificação')+'. '+(d.config.enabled&&d.config.manual_request&&d.config.revision?(s.status==='applied'&&s.config&&s.config.revision===d.config.revision&&s.config.mode===d.config.mode&&s.config.zone===d.config.zone&&s.provider&&s.provider.cidade===p.cidade&&s.provider.estado===p.estado?'Configuração aplicada.':'Aguardando aplicação pelo servidor.'):'Recurso desativado por padrão. Habilite e use o botão específico somente se precisar ajustar.');}catch(e){state.textContent='Não foi possível consultar o horário. Atualize a página.';}}
refresh();setInterval(tick,1000);setInterval(refresh,15000);
})();
