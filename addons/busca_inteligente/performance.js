(function(){
 const measure=document.getElementById('measure'),apply=document.getElementById('apply'),rollback=document.getElementById('rollback'),status=document.getElementById('status'),report=document.getElementById('report'),details=document.getElementById('details');
 let plan,csrf='',busy=false,samples={},requestSequence=0;
 async function api(action){const r=await fetch('performance_api.php',{method:action?'POST':'GET',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json'},body:action?JSON.stringify({action,csrf}):undefined});const data=await r.json();if(!r.ok||data.error)throw Error(data.error||'Falha no diagnóstico.');return data;}
 function median(v){const a=[...v].sort((x,y)=>x-y),i=Math.floor(a.length/2);return a.length%2?a[i]:(a[i-1]+a[i])/2;}
 async function stage(rule){
  samples[rule]={};
  for(const filter of ['on','off']){
   samples[rule][filter]=[];
   for(let n=0;n<3;n++){
    status.textContent='Medindo '+(filter==='on'?'Online':'Offline')+' — '+rule+' ('+(n+1)+'/3)...';
    const url=new URL('index.php',location.href);url.search=new URLSearchParams({busca:filter,pagina:'1',num_registros:'25',organizar:'c.nome',_mka_perf:plan.id,_mka_rule:rule,_mka_run:Date.now()+'-'+(++requestSequence)});
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),45000),start=performance.now();
    let text,r;try{r=await fetch(url,{credentials:'same-origin',cache:'no-store',signal:controller.signal});text=await r.text();}finally{clearTimeout(timer);}
    const elapsed=(performance.now()-start)/1000;
    if(!r.ok)throw Error('A página respondeu com erro HTTP.');
    const doc=new DOMParser().parseFromString(text,'text/html'),node=doc.getElementById('mka-perf-metrics');
    if(!node)throw Error('A página não confirmou o perfil de diagnóstico; verifique sessão, versão instalada e sintaxe PHP.');
    const metric=JSON.parse(node.textContent);if(metric.failed){details.textContent=JSON.stringify(metric,null,2);const failure=metric.failure||{};throw Error(failure.timeout?'A consulta '+(failure.phase||'da busca')+' excedeu o limite de 15 segundos. Execute o instalador atualizado para revalidar a otimização.':'Uma consulta da busca falhou ('+(failure.phase||'diagnóstico')+', código '+(failure.error_code||'indisponível')+'). Consulte os detalhes das medições.');}
    samples[rule][filter].push({web_seconds:elapsed,metrics:metric});
    await new Promise(resolve=>setTimeout(resolve,500));
   }
  }
 }
 function render(server){
  report.replaceChildren();const table=document.createElement('table'),head=table.createTHead().insertRow();
  for(const label of ['Filtro','Antes: HTTP completo','Candidato: HTTP completo','Depois: HTTP completo']){const th=document.createElement('th');th.textContent=label;head.append(th);}
  for(const filter of ['on','off']){const row=table.insertRow();row.insertCell().textContent=filter==='on'?'Online':'Offline';for(const rule of ['baseline','candidate','applied'])row.insertCell().textContent=samples[rule]?.[filter]?.length?median(samples[rule][filter].map(x=>x.web_seconds)).toFixed(3)+' s':'—';}
  report.append(table);details.textContent=JSON.stringify({sql_and_application:server,web:samples},null,2);
 }
 measure.onclick=async()=>{
  if(busy)return;busy=true;measure.disabled=true;apply.disabled=true;rollback.hidden=true;
  try{
   const data=await api();plan=data.plan;csrf=data.csrf;samples={};
   if(plan.pending){rollback.hidden=false;throw Error('Existe uma otimização provisória. Reverta ou aguarde o prazo antes de uma nova análise.');}
   await stage('baseline');
   if(plan.compatible && !plan.already_direct)await stage('candidate');
   const r=await api('report');render(r.report);
   status.textContent=plan.already_direct?(plan.compatible?'Otimização já instalada. Medições atuais concluídas.':'Regra direta preexistente, mas compatibilidade não comprovada: revisão necessária.'):!plan.compatible?'Compatibilidade não comprovada. A otimização foi bloqueada; consulte os detalhes.':r.recommended?'Ganho medido. A otimização está disponível e exige sua confirmação.':'Nenhuma melhora suficiente foi comprovada. Esta alteração não é recomendada.';
   details.textContent+='\nAmbiente: '+JSON.stringify({php:data.php,database:data.database,mk_auth:data.mk_auth,tables:plan.tables,indexes:plan.indexes})+'\nCompatibilidade: '+JSON.stringify(plan.checks);apply.disabled=!r.recommended;
  }catch(e){status.textContent=e.message;}finally{busy=false;measure.disabled=false;}
 };
 apply.onclick=async()=>{
  if(busy||!confirm('Aplicar a regra candidata nesta instalação? Ela altera somente a política da Busca. Serão repetidas as medições e a validação; falhas revertem a política anterior.'))return;
  busy=true;apply.disabled=true;measure.disabled=true;
  try{const a=await api('apply');plan=a.plan;rollback.hidden=false;await stage('applied');for(const f of ['on','off']){if(median(samples.applied[f].map(x=>x.web_seconds))>median(samples.baseline[f].map(x=>x.web_seconds))*1.10)throw Error('O tempo HTTP apresentou regressão. Revertendo.');}const r=await api('confirm');render(r.report);status.textContent='Otimização confirmada após validação. Backup: '+r.plan.backup;rollback.hidden=true;}
  catch(e){try{await api('rollback');}catch(_){}status.textContent=e.message+' Se a validação não concluir, a política provisória expira e volta à regra anterior.';}
  finally{busy=false;measure.disabled=false;}
 };
 rollback.onclick=async()=>{try{await api('rollback');status.textContent='Política anterior restaurada.';rollback.hidden=true;}catch(e){status.textContent=e.message;}};
}());
