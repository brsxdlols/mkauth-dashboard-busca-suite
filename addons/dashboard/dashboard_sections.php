<?php
if(!isset($conn)||!function_exists('permissao')||!in_array($sectionPlacement??'',array('before','after'),true))return;
$sectionCfg=isset($multiCfg)?$multiCfg:array('tbl_logs_sistema'=>$tbl_logs_sistema??'n','tbl_chamados_abertos'=>$tbl_chamados_abertos??'n','tbl_contas_pagar'=>$tbl_contas_pagar??'n','exb_graficos_em_baixo'=>$exb_graficos_em_baixo??'s');
$tot_chamados=$tot_chamados??0;
$tbl_chamados_abertos=$sectionCfg['tbl_chamados_abertos']??'n';$tbl_contas_pagar=$sectionCfg['tbl_contas_pagar']??'n';
$sectionPositions=array('activity'=>($sectionCfg['exb_graficos_em_baixo']??'s')==='h'?'hidden':(($sectionCfg['exb_graficos_em_baixo']??'s')==='n'?'before':'after'),'calls'=>($sectionCfg['tbl_chamados_abertos']??'n')==='s'?'after':'hidden','installations'=>($sectionCfg['tbl_chamados_abertos']??'n')==='s'?'after':'hidden','payables'=>($sectionCfg['tbl_contas_pagar']??'n')==='s'?'before':'hidden');
$sectionQuery=mysqli_query($conn,"SELECT nome,valor FROM sis_opcao WHERE nome IN ('suite_section_calls','suite_section_installations','suite_section_payables')");
if($sectionQuery)while($pref=mysqli_fetch_assoc($sectionQuery)){if(in_array($pref['valor'],array('before','after','hidden'),true))$sectionPositions[substr($pref['nome'],14)]=$pref['valor'];}
?>
<div class="mka-sections-stack" style="display:block;width:100%;min-width:0;box-sizing:border-box">
<style>.mka-operation-card{width:100%;background:white;border:1px solid #d5e2f4;border-radius:16px;padding:18px;margin:14px 0;color:#193b60;min-width:0;box-sizing:border-box}.mka-operation-card h2{font:700 17px system-ui;margin:0 0 16px}.mka-operation-table{overflow-x:auto}.mka-operation-card table{width:100%;border-collapse:collapse}.mka-operation-card th,.mka-operation-card td{padding:10px 12px!important;text-align:left;vertical-align:top;border-bottom:1px solid #dce5ef}.mka-operation-card th{background:#f0f5fc!important;color:#193b60!important;font-weight:700}.mka-operation-card th+th,.mka-operation-card td+td{border-left:1px solid #dce5ef}.mka-operation-card tbody tr:nth-child(even){background:#f8fafd}[data-dashboard-payable-detail],[data-dashboard-ticket-row],[data-dashboard-installation-row]{cursor:pointer}[data-dashboard-payable-detail]:hover,[data-dashboard-ticket-row]:hover,[data-dashboard-installation-row]:hover{background:#edf4ff!important}[data-dashboard-payable-detail]:focus-visible,[data-dashboard-ticket-row]:focus-visible,[data-dashboard-installation-row]:focus-visible{outline:2px solid #1768df;outline-offset:-2px}.mka-operation-empty{padding:22px!important;color:#667e99}</style>
<?php
foreach($sectionPositions as $sectionKey=>$placement){
 if($placement!==$sectionPlacement)continue;
 if($sectionKey==='activity')include __DIR__.'/activity_log.php';
 else {$tbl_chamados_abertos='s';$tbl_contas_pagar='s';include __DIR__.'/section_'.$sectionKey.'.php';}
}
?>
</div>

<script src="/admin/addons/dashboard/section_details.js?v=20261123-1" defer></script>
