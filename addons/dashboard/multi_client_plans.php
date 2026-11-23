<?php
// Included by the authenticated graphs endpoint after its permission/group checks.
if(!isset($conn,$multi,$grupos)||!$multi||!permissao('perm_numClientes'))return;
$planCounts=array();
$sql="SELECT plan, SUM(quantity) quantity FROM (
 SELECT COALESCE(NULLIF(c.plano,''),'Sem plano') plan, COUNT(*) quantity FROM sis_cliente c WHERE $grupos c.cli_ativado='s' GROUP BY c.plano
 UNION ALL
 SELECT COALESCE(NULLIF(a.plano,''),'Sem plano') plan, COUNT(*) quantity FROM sis_adicional a INNER JOIN sis_cliente c ON c.login=a.login WHERE $grupos c.cli_ativado='s' GROUP BY a.plano
) scoped_plans GROUP BY plan ORDER BY quantity DESC, plan";
$result=mysqli_query($conn,$sql);
if($result)while($row=mysqli_fetch_assoc($result))$planCounts[]=array('name'=>$row['plan'],'y'=>(int)$row['quantity']);

$monthly=array();$monthStart=new DateTimeImmutable(date('Y-m-01'));
for($i=max(1,min(24,(int)$per_meses))-1;$i>=0;$i--){$key=$monthStart->modify('-'.$i.' months')->format('Y-m');$monthly[$key]=array('month'=>$key,'activations'=>0,'reactivations'=>0,'cancellations'=>0);}
$from=array_keys($monthly)[0].'-01';$until=$monthStart->modify('+1 month')->format('Y-m-d');
foreach(array('activations'=>'data_ins','cancellations'=>'data_desativacao') as $kind=>$column){
 $query=mysqli_query($conn,"SELECT DATE_FORMAT(c.$column,'%Y-%m') month,COUNT(*) total FROM sis_cliente c WHERE $grupos c.$column>='$from' AND c.$column<'$until' GROUP BY DATE_FORMAT(c.$column,'%Y-%m')");
 if($query)while($row=mysqli_fetch_assoc($query))if(isset($monthly[$row['month']]))$monthly[$row['month']][$kind]=(int)$row['total'];
}
$patterns=array();foreach(array_keys($monthly) as $month)$patterns[]="l.data LIKE '%/".substr($month,5,2)."/".substr($month,0,4)."%'";
$query=mysqli_query($conn,"SELECT CONCAT(SUBSTRING(l.data,7,4),'-',SUBSTRING(l.data,4,2)) month,COUNT(DISTINCT c.login) total FROM sis_logs l INNER JOIN sis_cliente c ON c.login=l.login WHERE $grupos l.registro LIKE '%ativou o cliente%' AND l.registro NOT LIKE '%desativou%' AND (".implode(' OR ',$patterns).") GROUP BY CONCAT(SUBSTRING(l.data,7,4),'-',SUBSTRING(l.data,4,2))");
if($query)while($row=mysqli_fetch_assoc($query))if(isset($monthly[$row['month']]))$monthly[$row['month']]['reactivations']=(int)$row['total'];
?>
<section class="card border-light mb-2 mka-client-charts" aria-label="Clientes por plano">
<div class="card-header">Clientes</div><div class="card-body"><div class="row"><div class="col-12 col-lg-4">
<?php if(!$result){ ?><p role="status">Não foi possível consultar os clientes por plano.</p>
<?php }elseif(!$planCounts){ ?><p>Nenhum cliente ativo encontrado nos grupos permitidos.</p>
<?php }else{ ?><div id="container-rel-cli-planos-1" style="min-height:380px"></div>
<p style="font-size:12px">Clientes ativos e adicionais vinculados a clientes ativos, nos grupos permitidos ao seu usuário.</p>
<script>
Highcharts.chart('mka-multi-client-plans',{chart:{type:'pie',height:380},title:{text:'Planos por Clientes'},credits:{enabled:false},tooltip:{valuePrefix:'',valueDecimals:0,pointFormat:'<b>{point.y} cliente(s)</b> · {point.percentage:.1f}%'},plotOptions:{pie:{showInLegend:true,allowPointSelect:true,dataLabels:{enabled:false}}},series:[{name:'Clientes',colorByPoint:true,data:<?=json_encode($planCounts,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|(defined('JSON_INVALID_UTF8_SUBSTITUTE')?JSON_INVALID_UTF8_SUBSTITUTE:0))?>}]});
</script><?php } ?></div><div class="col-12 col-lg-4"><div id="container-rel-cli-1" style="height:380px"></div></div><div class="col-12 col-lg-4"><div id="container-rel-cli-2" style="height:380px"></div></div></div>
<p style="font-size:12px">Movimentações: clientes principais cadastrados, reativados e desativados no período, nos grupos permitidos.</p>
<script>
(function(){
var months=<?=json_encode(array_values($monthly))?>,labels=months.map(function(m){return m.month;}),incoming=months.map(function(m){return m.activations+m.reactivations;}),cancelled=months.map(function(m){return m.cancellations;}),balance=incoming.map(function(n,i){return n-cancelled[i];});
function total(values){return values.reduce(function(a,b){return a+b;},0);}
function draw(id,categories,values){Highcharts.chart(id,{chart:{type:'column',height:380},title:{text:id==='container-rel-cli-1'?'Movimentações de clientes':'Balanço acumulado de clientes'},credits:{enabled:false},xAxis:{categories:categories},yAxis:{allowDecimals:false,title:{text:'Clientes'}},tooltip:{valuePrefix:'',valueDecimals:0,valueSuffix:' cliente(s)'},plotOptions:{column:{dataLabels:{enabled:true}}},series:[{name:'Ativações + Reativações',data:values[0]},{name:'Cancelamentos',data:values[1]},{name:'Saldo',data:values[2]}]});}
draw('container-rel-cli-1',labels,[incoming,cancelled,balance]);draw('container-rel-cli-2',['Total do período'],[[total(incoming)],[total(cancelled)],[total(balance)]]);
})();
</script></div></section>
