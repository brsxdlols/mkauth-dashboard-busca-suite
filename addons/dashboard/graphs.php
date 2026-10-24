<?php
http_response_code(403);
require __DIR__.'/config.php';
header('Cache-Control: no-store');
$result=mysqli_query($conn,'SELECT * FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1');
$settings=$result?mysqli_fetch_assoc($result):null;
if(!$settings){http_response_code(500);exit('Unable to load dashboard settings.');}
$per_meses=max(1,(int)$settings['qtd_meses_graficos']);
$limite_ticket=(int)$settings['limite_ticket'];
$string_per=$per_meses.' meses';
foreach(array('exb_ticket_medio','exb_saldo_conta','exb_balanco_faturamento','exb_balanco_clientes','exb_balanco_chamados') as $setting) {
    $$setting=isset($settings[$setting]) && trim((string)$settings[$setting])!==''?$settings[$setting]:'s';
}
$multi=mka_suite_get_layout_mode($conn)==='multi';
if($multi){$exb_balanco_faturamento='s';$exb_balanco_clientes=$exb_balanco_chamados='n';}
$user=(string)$usuario_logado;
$access=$conn->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');
$access->bind_param('s',$user);$access->execute();$row=$access->get_result()->fetch_assoc();
if(!$row){http_response_code(403);exit('Access denied.');}
http_response_code(200);
$groupNames=array_map('trim',explode(',',(string)$row['cli_grupos']));
$grupos=$multi?'':"c.grupo LIKE '%' AND ";
if(trim((string)$row['cli_grupos'])!=='' && !in_array('full_clientes',$groupNames,true)) {
    $quoted=array();
    foreach($groupNames as $group)if($group!=='' && $group!=='ped_fil')$quoted[]="'".mysqli_real_escape_string($conn,$group)."'";
    $grupos=$quoted?'c.grupo IN ('.implode(',',$quoted).') AND ':'1=0 AND ';
}
$hasFinance=permissao('perm_relFin')||permissao('perm_relFat');
$_GET+=array('assunto'=>'TODOS','tecnico'=>'TODOS');
foreach(array('assunto','tecnico') as $filter)$_GET[$filter]=mysqli_real_escape_string($conn,(string)$_GET[$filter]);
session_write_close();
if($hasFinance){
    include __DIR__.'/graf_periodo.php';
    if(!$multi){
        include __DIR__.'/cli_periodo.php';
        include __DIR__.'/cli_chamado_per.php';
        include __DIR__.'/cli_planos.php';
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gráficos do Dashboard</title><link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/estilo.css">
<script src="js/highcharts.js"></script><script src="js/exporting.js"></script>
<style>html,body{margin:0;padding:0;background:transparent;overflow:hidden}#dashboard-graphs-content{display:flow-root;width:100%}.row{margin-left:0;margin-right:0}.highcharts-figure{margin:0;min-width:0}.card{border-radius:16px;overflow:hidden}.highcharts-description{margin:0}</style>
</head><body data-mka-graphs-ready>
<main id="dashboard-graphs-content">
<?php include __DIR__.'/graficos.php'; ?>
</main>
<script>
(function(){
    const content=document.getElementById('dashboard-graphs-content');
    function report(){parent.postMessage({type:'mka-dashboard-graphs',height:content.querySelector('[id^="container-"]')?Math.ceil(Math.max(content.scrollHeight,content.getBoundingClientRect().height)):0},location.origin);}
    window.addEventListener('load',report);
    window.addEventListener('resize',report);
    if(window.ResizeObserver)new ResizeObserver(report).observe(content);
    report();
})();
</script></body></html>
