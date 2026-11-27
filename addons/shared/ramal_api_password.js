(function(){'use strict';
if(window.mkaRamalPasswordMounted)return;window.mkaRamalPasswordMounted=true;
var field=document.querySelector('input[name="senha"][type="password"]'),id=new URLSearchParams(location.search).get('id');
if(!field||!id||!field.value.startsWith('__MKA_KEEP_'))return;
async function readJson(response){if(response.headers&&response.headers.get&&!String(response.headers.get('Content-Type')||'').includes('application/json'))throw new Error('Sessão expirada ou acesso indisponível. Atualize a página.');return response.json();}
var placeholder=field.value,stub=field.value.endsWith('_EMPTY')?'':'********',dirty=false,revealed=false,context=null;field.value=stub;
var wrap=document.createElement('span');wrap.className='mka-ramal-password-field';field.parentNode.insertBefore(wrap,field);wrap.append(field);
var eye=document.createElement('button');eye.type='button';eye.className='mka-ramal-password-eye';eye.disabled=true;eye.title='Conferindo permissão Usuários';eye.setAttribute('aria-label','Visualizar senha da API');eye.setAttribute('aria-pressed','false');eye.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';wrap.append(eye);
var css=document.createElement('link');css.rel='stylesheet';css.href='/admin/addons/shared/ramal_api_password.css?v=20261127-1';document.head.append(css);
field.addEventListener('focus',function(){if(!dirty&&!revealed)field.value='';});
field.addEventListener('blur',function(){if(!dirty&&!revealed)field.value=stub;});
field.addEventListener('input',function(){dirty=true;});
var saving=false,saveChecked=false;
field.form.addEventListener('submit',async function(event){
 if(saveChecked){saveChecked=false;return;}event.preventDefault();if(saving)return;saving=true;
 try{var response=await fetch('/admin/mka_suite_ramal_password_guard.hhvm',{credentials:'same-origin',cache:'no-store'}),data=await readJson(response);if(!response.ok||!data.protected)throw new Error('A integração do ramal mudou. Atualize a página antes de gravar.');if(!dirty){field.type='password';field.value=placeholder;}saveChecked=true;if(field.form.requestSubmit)field.form.requestSubmit(event.submitter||undefined);else field.form.submit();}
 catch(problem){if(!dirty)field.value=stub;window.alert(problem.message||'Não foi possível validar a gravação. Atualize a página.');}
 finally{saving=false;}
},true);
field.form.addEventListener('reset',function(){setTimeout(function(){dirty=false;revealed=false;field.type='password';field.value=stub;},0);});
fetch('/admin/addons/dashboard/ramal_api_access.php?ramal='+encodeURIComponent(id),{credentials:'same-origin',cache:'no-store'}).then(function(r){if(!r.ok)throw new Error();return r.json();}).then(function(data){context=data;eye.disabled=!data.can_reveal;eye.title=data.can_reveal?'Visualizar senha: confirme sua senha de acesso ao MK-AUTH':'Exige a permissão Usuários e a integração atualizada';}).catch(function(){eye.title='Não foi possível conferir a permissão. Atualize a página.';});
function hide(){field.type='password';revealed=false;if(!dirty)field.value=stub;eye.setAttribute('aria-pressed','false');if(context&&context.can_reveal){eye.title='Visualizar senha: confirme sua senha de acesso ao MK-AUTH';eye.setAttribute('aria-label','Visualizar senha da API');}}
eye.addEventListener('click',function(){
 if(revealed){hide();return;}if(!context||!context.can_reveal)return;
 var dialog=document.createElement('dialog');dialog.className='mka-api-password-dialog';var title=document.createElement('h2');title.textContent='Confirmar acesso';var text=document.createElement('p');text.textContent='Informe a senha de '+context.operator+', usuário logado no MK-AUTH, para visualizar a senha da API.';
 var form=document.createElement('form'),input=document.createElement('input'),label=document.createElement('label'),error=document.createElement('p'),actions=document.createElement('div'),cancel=document.createElement('button'),ok=document.createElement('button');label.textContent='Sua senha de acesso ao MK-AUTH';input.type='password';input.autocomplete='current-password';input.required=true;label.append(input);error.setAttribute('role','alert');cancel.type='button';cancel.textContent='Cancelar';ok.type='submit';ok.textContent='Confirmar e visualizar';actions.append(cancel,ok);form.append(label,error,actions);dialog.append(title,text,form);document.body.append(dialog);
 function close(){input.value='';dialog.close();}cancel.onclick=close;dialog.addEventListener('close',function(){input.value='';dialog.remove();eye.focus();});
 form.addEventListener('submit',async function(event){event.preventDefault();ok.disabled=true;error.textContent='';var body=new URLSearchParams({ramal:id,csrf:context.csrf,operator_password:input.value,stored:dirty?'0':'1'});input.value='';try{var response=await fetch('/admin/addons/dashboard/ramal_api_access.php',{method:'POST',credentials:'same-origin',cache:'no-store',body:body}),data=await readJson(response);body.delete('operator_password');if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível validar o acesso.');if(!dirty)field.value=data.password;field.type='text';revealed=true;eye.setAttribute('aria-pressed','true');eye.title='Ocultar senha';eye.setAttribute('aria-label','Ocultar senha da API');close();}catch(problem){body.delete('operator_password');error.textContent=problem.message;ok.disabled=false;input.focus();}});
 dialog.showModal();input.focus();
});
document.addEventListener('visibilitychange',function(){if(document.hidden)hide();});
})();
