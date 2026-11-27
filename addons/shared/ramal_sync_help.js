(function(){
 'use strict';
 if(window.mkaRamalSyncHelp)return;
 function escape(value){return String(value==null?'':value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
 function kind(reason){var text=String(reason||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();if(/senha|usuario|password|username|auth|autentic|login|invalid credentials/.test(text))return 'credentials';if(/8728|porta/.test(text))return 'port';if(/ping|rota|vpn|timeout|tempo.*esgot/.test(text))return 'route';return 'general';}
 var guides={
  credentials:{title:'Como corrigir usuário ou senha da API',tip:'Confira a conta de acesso à API no cadastro do ramal e em System → Users do MikroTik. Clique para ver o passo a passo.',steps:[
   'No MK-AUTH, abra Provedor → Ramais e altere o cadastro do ramal indicado. Confira o usuário e a senha usados para acessar a API desse MikroTik.',
   'No WinBox ou WebFig do mesmo MikroTik, abra System → Users. Confira se essa conta existe, está habilitada e usa a mesma senha informada no cadastro do ramal.',
   'Confira se o grupo dessa conta tem permissão de acesso à API e se o endereço permitido da conta aceita o IP do servidor MK-AUTH.',
   'Em IP → Services → api, confira se o serviço está habilitado na porta 8728 e se Available From, firewall e VPN permitem o acesso a partir do servidor MK-AUTH.',
   'Grave os dados corrigidos no cadastro do ramal e use Repetir teste na janela anterior. Se persistir, confira os registros de autenticação em Log no MikroTik.'
  ]},
  port:{title:'Como conferir o serviço API do ramal',tip:'Confira IP → Services → api e a liberação da porta 8728 para o servidor MK-AUTH. Clique para ver o passo a passo.',steps:[
   'No MK-AUTH, abra Provedor → Ramais e confira se o IP cadastrado corresponde ao MikroTik desse ramal.',
   'No WinBox ou WebFig, abra IP → Services → api e confira se o serviço está habilitado na porta 8728, utilizada por este teste.',
   'Confira se Available From e as regras de firewall permitem a origem do servidor MK-AUTH. Se houver VPN, confira o acesso por ela.',
   'Depois da correção, use Repetir teste para validar novamente o acesso à API.'
  ]},
  route:{title:'Como conferir o acesso do MK-AUTH ao ramal',tip:'Confira o IP do ramal, a rota/VPN e a porta API a partir do servidor MK-AUTH. Clique para ver o passo a passo.',steps:[
   'No MK-AUTH, abra Provedor → Ramais e confira o IP de gerenciamento cadastrado para esse MikroTik.',
   'Confira a rota e, quando utilizada, a VPN entre o servidor MK-AUTH e o ramal. Verifique se os dois lados estão acessíveis.',
   'Confira a liberação da porta API 8728 para a origem do servidor MK-AUTH. Se o ping for bloqueado, verifique o acesso à API separadamente.',
   'Após a correção, use Repetir teste e confira o novo resultado do ramal.'
  ]},
  general:{title:'Como revisar a sincronização do ramal',tip:'Confira o cadastro do ramal e o acesso do servidor MK-AUTH à API do MikroTik. Clique para ver as orientações.',steps:[
   'No MK-AUTH, abra Provedor → Ramais e localize o ramal indicado por nome e IP.',
   'Confira o IP de gerenciamento, o usuário e a senha da API e o acesso do servidor MK-AUTH ao MikroTik.',
   'No MikroTik, confira a conta em System → Users e o serviço api em IP → Services, incluindo as permissões e a origem autorizada.',
   'Use Repetir teste para atualizar o diagnóstico. Se a mensagem continuar, encaminhe o diagnóstico e o nome/IP do ramal ao responsável técnico.'
  ]}
 };
 function reasonHtml(router){
  router=router||{};var reason=router.reason||(router.ok?'Acesso à API validado':'Diagnóstico não informado');
  if(router.ok===true)return escape(reason);
  var type=kind(reason),guide=guides[type];
  return '<button type="button" class="mka-ramal-help-trigger" data-mka-ramal-help="'+type+'" data-ramal-name="'+escape(router.name||router.router||'Ramal')+'" data-ramal-ip="'+escape(router.router||'')+'" title="'+escape(guide.tip)+'" aria-label="'+escape(reason+'. Como resolver')+'">'+escape(reason)+' <span class="mka-ramal-help-label">— Como resolver</span></button>';
 }
 function node(tag,className,text){var element=document.createElement(tag);if(className)element.className=className;if(text!==undefined)element.textContent=text;return element;}
 function open(trigger){
  var old=document.getElementById('mka-ramal-help-dialog');if(old)old.close();
  var guide=guides[trigger.dataset.mkaRamalHelp]||guides.general,dialog=node('dialog','mka-ramal-help-dialog'),header=node('header'),title=node('h2','',guide.title),close=node('button','mka-ramal-help-close','×'),body=node('div','mka-ramal-help-body'),list=node('ol'),footer=node('footer'),done=node('button','mka-ramal-help-done','Entendi');
  dialog.id='mka-ramal-help-dialog';title.id='mka-ramal-help-title';dialog.setAttribute('aria-labelledby',title.id);close.type=done.type='button';close.setAttribute('aria-label','Fechar orientações');
  header.append(title,close);body.append(node('p','mka-ramal-help-context',(trigger.dataset.ramalName||'Ramal')+(trigger.dataset.ramalIp?' · '+trigger.dataset.ramalIp:'')),node('p','','Este aviso se refere à comunicação do MK-AUTH com a API do ramal.'));
  guide.steps.forEach(function(step){list.append(node('li','',step));});body.append(list);
  var note=node('p','mka-ramal-help-note','Use a conta de acesso à API chamada mkauth. No MK-AUTH, defina a senha em Provedor → Ramais → Senha mkauth. No MikroTik, crie ou edite o usuário mkauth em System → Users com a mesma senha e permissão de acesso à API. Conferindo o IP de origem deste servidor…');body.append(note);
  fetch('/admin/addons/dashboard/ramal_api_access.php?router='+encodeURIComponent(trigger.dataset.ramalIp||''),{credentials:'same-origin',cache:'no-store'}).then(function(response){if(!response.ok)throw new Error('context');return response.json();}).then(function(data){if(!note.isConnected)return;note.textContent='Use a conta de acesso à API chamada mkauth. No MK-AUTH, defina a senha em Provedor → Ramais → Senha mkauth. No MikroTik, crie ou edite o usuário mkauth em System → Users com a mesma senha e um grupo com acesso à API. '+(data.server_ip?'Permita o IP '+data.server_ip+(data.source_cached?', origem verificada na instalação deste servidor para o ramal. ':', origem deste servidor MK-AUTH para esse ramal. '):'Não foi possível identificar automaticamente o IP de origem; confira a rota do servidor até o ramal. ')+'Se houver NAT no caminho, autorize o IP traduzido que chega ao MikroTik.';}).catch(function(){if(note.isConnected)note.textContent='Use a conta de API mkauth: defina a senha no cadastro do ramal e crie ou edite o mesmo usuário no MikroTik com a mesma senha e acesso à API. Confira a rota para identificar o IP de origem do MK-AUTH que deve ser autorizado.';});
  footer.append(done);dialog.append(header,body,footer);document.body.append(dialog);
  function dismiss(){dialog.close();}close.addEventListener('click',dismiss);done.addEventListener('click',dismiss);
  dialog.addEventListener('close',function(){dialog.remove();if(trigger.isConnected)trigger.focus();});
  dialog.addEventListener('click',function(event){if(event.target!==dialog)return;var rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();});
  dialog.showModal();close.focus();
 }
 var style=document.createElement('link');style.rel='stylesheet';style.href='/admin/addons/shared/ramal_sync_help.css?v=20261127-1';document.head.append(style);
 document.addEventListener('click',function(event){if(!event.target||typeof event.target.closest!=='function')return;var trigger=event.target.closest('[data-mka-ramal-help]');if(trigger){event.preventDefault();open(trigger);}});
 window.mkaRamalSyncHelp={reasonHtml:reasonHtml,kind:kind};
}());

