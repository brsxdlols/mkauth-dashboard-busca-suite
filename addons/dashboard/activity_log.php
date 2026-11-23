<?php
if(!isset($conn)||!function_exists('permissao')||!permissao('perm_logs')||(isset($multiCfg)?($multiCfg['tbl_logs_sistema']??'n'):($tbl_logs_sistema??'n'))!=='s')return;
$activities=mysqli_query($conn,"SELECT registro,data,login FROM sis_logs WHERE tipo NOT LIKE 'central' ORDER BY id DESC LIMIT 15");
if(!$activities){?><p role="status">Não foi possível consultar as atividades.</p><?php return;}
$logsExt=isset($ext_mk)?$ext_mk:'.hhvm';
?>
<style>
[data-dashboard-activities] table{table-layout:fixed}
[data-dashboard-activities] th,[data-dashboard-activities] td{text-align:left;padding:10px 12px!important;vertical-align:top;overflow-wrap:anywhere}
[data-dashboard-activities] th{background:#f0f5fc;font-weight:700;border-bottom:2px solid #d4e0ef}
[data-dashboard-activities] th+th,[data-dashboard-activities] td+td{border-left:1px solid #dce5ef}
[data-dashboard-activities] th:nth-child(2){width:175px}
[data-dashboard-activities] th:nth-child(3){width:210px}
[data-dashboard-activities] tbody tr:nth-child(even){background:#f8fafd}
@media(max-width:600px){[data-dashboard-activities] table{table-layout:auto;min-width:650px}}
</style>
<section class="multi-summary" style="background:#fff;border:1px solid #d5e2f4;border-radius:16px;padding:16px;margin:12px 0" aria-label="Log de atividades" data-dashboard-activities><h2>Atividades</h2>
<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Atividade</th><th>Data</th><th>Usuário</th></tr></thead><tbody>
<?php while($activity=mysqli_fetch_assoc($activities)){?><tr><?php foreach(array('registro','data','login') as $key){?><td style="padding:8px;border-bottom:1px solid #dce5ef"><?=htmlspecialchars((string)$activity[$key],ENT_QUOTES,'UTF-8')?></td><?php }?></tr><?php }?>
</tbody></table></div><a href="<?=htmlspecialchars('/admin/logs_sistema'.$logsExt,ENT_QUOTES,'UTF-8')?>">Ver mais atividades</a></section>
