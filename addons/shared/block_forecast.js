(function(){
 function br(date){return date ? date.slice(0,10).split('-').reverse().join('/') : '-';}
 window.mkaLoadBlockForecast=function(uuid,container){
  container.hidden=false;container.textContent='Consultando boletos em aberto e prazo de corte...';
  container.dataset.uuid=uuid;
  fetch('client_block_forecast.php?uuid='+encodeURIComponent(uuid),{credentials:'same-origin',cache:'no-store'})
   .then(function(response){if(!response.ok)throw new Error();return response.json();})
   .then(function(data){
    if(container.dataset.uuid!==uuid)return;
    if(!data.ok)throw new Error();
    var f=data.forecast,lines=[],status;
    if(f.open_count===0)status='Nenhum boleto em aberto.';
    else if(f.automatic_block==='nao')status='Bloqueio autom\u00e1tico desativado.';
    else if(f.exempt)status='Cliente isento de cobran\u00e7a.';
    else if(f.observation)status='Em observa\u00e7\u00e3o: respeitar o desbloqueio de confian\u00e7a.';
    else if(!f.active)status='Cliente desativado.';
    else if(!f.eligible_date)status='Previs\u00e3o de corte indispon\u00edvel.';
    else status=f.eligible_now ? 'Pode ser bloqueado na pr\u00f3xima rotina financeira.' : 'Previs\u00e3o de bloqueio a partir de '+br(f.eligible_date)+', se continuar em aberto.';
    if(f.open_count===0)lines.push('Nenhum boleto em aberto encontrado.');
    else {
     lines.push('Boletos em aberto: '+f.open_count+'. Vencimento mais antigo: '+br(f.oldest_due)+'.');
     lines.push('Dias de corte: '+(f.cut_days===null?'n\u00e3o informado':f.cut_days)+'.');
     if(f.eligible_date){
      lines.push('Vencimento + dias de corte: '+br(f.cut_date)+'.');
      lines.push(f.eligible_now ? 'O prazo j\u00e1 foi ultrapassado: o cliente poder\u00e1 entrar na pr\u00f3xima rotina de bloqueio financeiro.' : 'Mantendo o boleto em aberto, o prazo ser\u00e1 ultrapassado a partir de '+br(f.eligible_date)+', quando poder\u00e1 entrar na rotina de bloqueio.');
     }else lines.push('N\u00e3o foi poss\u00edvel calcular a data de corte.');
    }
    if(f.automatic_block==='nao')lines.push('Bloqueio autom\u00e1tico desativado no sistema.');
    if(f.exempt)lines.push('Cliente isento: a previs\u00e3o financeira n\u00e3o confirma bloqueio.');
    if(f.observation)lines.push('Cliente em observa\u00e7\u00e3o: o desbloqueio de confian\u00e7a deve ser respeitado pela rotina nativa.');
    if(!f.active)lines.push('Cliente desativado.');
    if(f.automatic_unlock==='sim')lines.push('Desbloqueio autom\u00e1tico habilitado no sistema.');
    lines.push('Previs\u00e3o pela regra de atraso maior que os dias de corte; a execu\u00e7\u00e3o depende das regras e do agendamento do MK-AUTH. Pagamentos ou altera\u00e7\u00f5es posteriores podem mudar esta previs\u00e3o.');
    container.textContent=f.open_count>0 ? f.open_count+' boleto'+(f.open_count===1?'':'s')+' em aberto.\n'+status : status;
    container.style.whiteSpace='pre-line';
    var details=document.createElement('details'),label=document.createElement('summary'),body=document.createElement('div');
    label.textContent='Ver detalhes';body.textContent=lines.join('\n');
    details.style.marginTop='8px';body.style.marginTop='6px';
    details.appendChild(label);details.appendChild(body);container.appendChild(details);
   }).catch(function(){if(container.dataset.uuid===uuid)container.textContent='N\u00e3o foi poss\u00edvel consultar a previs\u00e3o financeira. Confira os t\u00edtulos e o prazo de corte antes de continuar.';});
 };
}());
