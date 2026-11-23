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
?>
<section class="card border-light mb-2 mka-client-charts" aria-label="Clientes por plano">
<div class="card-header">Clientes</div><div class="card-body">
<?php if(!$result){ ?><p role="status">Não foi possível consultar os clientes por plano.</p>
<?php }elseif(!$planCounts){ ?><p>Nenhum cliente ativo encontrado nos grupos permitidos.</p>
<?php }else{ ?><div id="mka-multi-client-plans" style="min-height:380px"></div>
<p style="font-size:12px">Clientes ativos e adicionais vinculados a clientes ativos, nos grupos permitidos ao seu usuário.</p>
<script>
Highcharts.chart('mka-multi-client-plans',{chart:{type:'pie',height:380},title:{text:'Planos por Clientes'},credits:{enabled:false},tooltip:{pointFormat:'<b>{point.y}</b> · {point.percentage:.1f}%'},plotOptions:{pie:{showInLegend:true,allowPointSelect:true,dataLabels:{enabled:false}}},series:[{name:'Clientes',colorByPoint:true,data:<?=json_encode($planCounts,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|(defined('JSON_INVALID_UTF8_SUBSTITUTE')?JSON_INVALID_UTF8_SUBSTITUTE:0))?>}]});
</script><?php } ?></div></section>
