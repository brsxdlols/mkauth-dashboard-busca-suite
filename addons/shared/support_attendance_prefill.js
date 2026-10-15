(function(){
 'use strict';
 const id=new URLSearchParams(location.search).get('mka_atendimento');if(!/^[1-9][0-9]*$/.test(id||'')||!/\/suporte_ins\.(hhvm|php)$/.test(location.pathname))return;
 fetch('/admin/addons/busca_inteligente/attendance_api.php?action=detail&id='+encodeURIComponent(id),{credentials:'same-origin',cache:'no-store'}).then(async response=>{if(!response.ok)throw Error();return response.json();}).then(data=>{
  const record=data.records[0],uuid=document.querySelector('#uuid_cliente'),client=document.querySelector('#cliente'),notes=document.querySelector('#msg'),subject=document.querySelector('#assunto');
  if(!uuid||!client||!notes)return;
  if(uuid.value&&uuid.value!==record.client_uuid)return;
  uuid.value=record.client_uuid;client.value=record.client_name;
  if(!notes.value.trim())notes.value='[Atendimento #'+record.id+']\nAssunto: '+record.subject+'\n\n'+record.notes;
  if(subject){const option=Array.from(subject.options).find(item=>item.textContent.trim().toLowerCase()===record.subject.trim().toLowerCase());if(option)subject.value=option.value;}
  const info=document.createElement('p');info.textContent='Atendimento #'+record.id+' carregado. Confira cliente, assunto, técnico e visita antes de gravar a ordem de serviço.';info.style.cssText='margin:12px;padding:12px;background:#eaf3ff;border:1px solid #a3c4ee;border-radius:7px;color:#174a84;font:14px/1.5 system-ui';
  const form=uuid.closest('form');if(form)form.prepend(info);
 }).catch(()=>{const info=document.createElement('p');info.textContent='Não foi possível carregar o atendimento. Volte à Busca e tente novamente.';info.style.cssText='padding:12px;color:#9c2436';document.body.prepend(info);});
})();
