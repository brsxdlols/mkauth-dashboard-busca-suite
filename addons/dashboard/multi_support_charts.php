<?php
if(!isset($conn,$multi,$grupos)||!$multi||!permissao('perm_chamados'))return;
$months=max(1,min(24,(int)$per_meses));$current=new DateTimeImmutable(date('Y-m-01'));$start=$current->modify('-'.($months-1).' months')->format('Y-m-d');$end=$current->modify('+1 month')->format('Y-m-d');
$q=mysqli_query($conn,"SELECT COALESCE(NULLIF(s.assunto,''),'Sem assunto informado') subject,COALESCE(NULLIF(f.nome,''),'Sem técnico informado') technician,COUNT(*) quantity,SUM(s.status='fechado') closed FROM sis_suporte s LEFT JOIN sis_cliente c ON c.login=s.login LEFT JOIN sis_func f ON f.id=s.tecnico WHERE $grupos s.abertura>='$start' AND s.abertura<'$end' GROUP BY subject,technician");
$subjects=$technicians=array();$opened=$closed=0;
if($q)while($row=mysqli_fetch_assoc($q)){$n=(int)$row['quantity'];$subjects[$row['subject']]=($subjects[$row['subject']]??0)+$n;$technicians[$row['technician']]=($technicians[$row['technician']]??0)+$n;$opened+=$n;$closed+=(int)$row['closed'];}
?>
<section class="card border-light mb-2"><div class="card-header">Chamados</div><div class="card-body">
<?php if(!$q){?><p>Não foi possível consultar os chamados.</p><?php }elseif(!$opened){?><p>Nenhum chamado registrado no período configurado.</p><?php }else{ ?>
<div class="row"><?php foreach(array(1,2) as $n){?><div class="col-12 col-lg-6"><figure class="highcharts-figure"><div id="container-rel-chamados-<?=$n?>"></div></figure></div><?php }?></div>
<div class="row"><?php foreach(array(3,4,5) as $n){?><div class="col-12 col-lg-4"><figure class="highcharts-figure"><div id="container-rel-chamados-<?=$n?>"></div></figure></div><?php }?></div>
<script>
(function(){var subjects=<?=json_encode($subjects,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,technicians=<?=json_encode($technicians,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
function bars(id,values){var keys=Object.keys(values);Highcharts.chart(id,{chart:{type:'column'},title:{text:'Chamados'},credits:{enabled:false},xAxis:{categories:keys},series:[{name:'Registrados',data:keys.map(function(k){return values[k];})}]});}
function pie(id,values){Highcharts.chart(id,{chart:{type:'pie'},title:{text:'Distribuição de chamados'},credits:{enabled:false},series:[{name:'Chamados',data:Object.keys(values).map(function(k){return {name:k,y:values[k]};})}]});}
bars('container-rel-chamados-1',subjects);bars('container-rel-chamados-2',technicians);pie('container-rel-chamados-3',subjects);pie('container-rel-chamados-4',technicians);Highcharts.chart('container-rel-chamados-5',{chart:{type:'column'},title:{text:'Chamados'},credits:{enabled:false},xAxis:{categories:['Período']},series:[{name:'Registrados',data:[<?=$opened?>]},{name:'Encerrados',data:[<?=$closed?>]},{name:'Em aberto',data:[<?=$opened-$closed?>]}]});
})();
</script><?php }?></div></section>
