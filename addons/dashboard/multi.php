<?php
// Rendered only after index.php has authenticated the dashboard session.
if (!defined('MKA_MULTI_DASHBOARD')) { http_response_code(403); exit('Acesso negado.'); }
$cfgQuery = mysqli_query($conn, 'SELECT * FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1');
$multiCfg = $cfgQuery ? mysqli_fetch_assoc($cfgQuery) : array();
$multiCfg = $multiCfg ?: array();
$per_meses = max(1, min(24, isset($multiCfg['qtd_meses_graficos']) ? (int)$multiCfg['qtd_meses_graficos'] : 3));
$string_per = $per_meses.' meses';
$limite_ticket = max(1, isset($multiCfg['limite_ticket']) ? (int)$multiCfg['limite_ticket'] : 1000);
$exb_ticket_medio = isset($multiCfg['exb_ticket_medio']) ? $multiCfg['exb_ticket_medio'] : 's';
$exb_saldo_conta = isset($multiCfg['exb_saldo_conta']) ? $multiCfg['exb_saldo_conta'] : 's';
$exb_balanco_faturamento = 's';
$exb_balanco_clientes = $exb_balanco_chamados = 'n';
$canTotals = permissao('perm_totais');
$canFinance = permissao('perm_relFin') || permissao('perm_relFat');
$canConfig = permissao('perm_config');
$safeUser = mysqli_real_escape_string($conn, $usuario_logado);
$accessQuery = mysqli_query($conn, "SELECT cli_grupos FROM sis_acesso WHERE login='$safeUser' LIMIT 1");
$access = $accessQuery ? mysqli_fetch_assoc($accessQuery) : null;
if (!$access) { http_response_code(403); exit('Usuário sem acesso.'); }
$groupNames = array_values(array_filter(array_map('trim', explode(',', (string)$access['cli_grupos'])), function($g){return $g!=='' && $g!=='ped_fil';}));
$grupos = '';
if (trim((string)$access['cli_grupos'])!=='' && !in_array('full_clientes',$groupNames,true)) {
    $quoted = array();
    foreach ($groupNames as $group) $quoted[]="'".mysqli_real_escape_string($conn,$group)."'";
    $grupos = $quoted ? 'c.grupo IN ('.implode(',',$quoted).') AND ' : '1=0 AND ';
}
$cards = array(); $multiError = false;
if ($canTotals) {
    // Aggregate business status only; deliberately no connection/session queries.
    $q = mysqli_query($conn,"SELECT COUNT(*) clients, COALESCE(SUM(c.bloqueado='sim'),0) blocked, COALESCE(SUM(c.observacao='sim'),0) observation FROM sis_cliente c WHERE $grupos c.cli_ativado='s'");
    $counts = $q ? mysqli_fetch_assoc($q) : null;
    $q = mysqli_query($conn,"SELECT COUNT(*) additional FROM sis_adicional a INNER JOIN sis_cliente c ON c.login=a.login WHERE $grupos c.cli_ativado='s'");
    $extra = $q ? mysqli_fetch_assoc($q) : null;
    $safeDay = mysqli_real_escape_string($conn,$now);
    $q = mysqli_query($conn,"SELECT COUNT(DISTINCT l.login) late FROM sis_lanc l INNER JOIN sis_cliente c ON c.login=l.login WHERE $grupos c.cli_ativado='s' AND l.deltitulo=0 AND l.status NOT LIKE 'pago' AND l.datavenc<='$safeDay'");
    $late = $q ? mysqli_fetch_assoc($q) : null;
    $multiError = !$counts || !$extra || !$late;
    if (!$multiError) {
        $base=(int)$counts['clients']; $additional=(int)$extra['additional']; $total=$base+$additional;
        $cards=array(
            array('Total',$total,$total,'','blue'),
            array('Livres',$base-(int)$counts['blocked'],$base,'','cyan'),
            array('Adicionais',$additional,$total,'adicionais','light'),
            array('Observação',(int)$counts['observation'],$base,'obs','mint'),
            array('Bloqueados',(int)$counts['blocked'],$base,'bloq','red'),
            array('Atrasados',(int)$late['late'],$base,'atrasado','yellow')
        );
    }
}
?>
<!doctype html><html lang="pt-BR" class="has-navbar-fixed-top"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard Multiempresas</title>
<link href="css/bootstrap.css" rel="stylesheet"><link href="../../estilos/mk-auth.css" rel="stylesheet"><link href="../../estilos/font-awesome.css" rel="stylesheet">
<script src="../../scripts/jquery.js"></script><script src="../../scripts/mk-auth.js"></script>
<script src="js/highcharts.js"></script><script src="js/exporting.js"></script>
<style>
.multi-page{background:#f5f7fb;color:#20364f}.multi-main{width:100%;max-width:1900px;margin:auto;padding:24px;box-sizing:border-box}.multi-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:22px}.multi-heading h1{font-size:27px;margin:0 0 6px}.multi-heading p{margin:0;color:#60738c}.multi-nav{display:flex;gap:8px;flex-wrap:wrap}.multi-nav a,.multi-search button{background:#1467df;color:white;border-radius:10px;padding:12px 18px;text-decoration:none;border:0;font-weight:600}.multi-search{display:flex;gap:10px;margin-bottom:20px}.multi-search input{min-width:0;flex:1;border:1px solid #ced9e7;border-radius:10px;padding:14px;background:white}.multi-summary{background:white;border:1px solid #d4dfed;border-radius:18px;padding:18px;margin-bottom:24px}.multi-summary h2{font-size:15px;margin:0 0 16px;text-transform:uppercase;letter-spacing:.06em}.multi-cards{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}.multi-stat{display:flex;flex-direction:column;gap:14px;min-width:0;padding:18px;border-radius:15px;text-decoration:none!important;color:#173451!important;background:#f5f8fc;border:1px solid #d5e0ed}.multi-stat strong{font-size:clamp(28px,3vw,44px);line-height:1.1;font-weight:400;overflow-wrap:anywhere}.multi-stat span{font-weight:700}.multi-stat small{border-top:1px solid #ffffff55;padding-top:10px;font-size:15px}.multi-stat.blue{background:#2678ef;color:white!important}.multi-stat.cyan{background:#28bbd2}.multi-stat.mint{background:#86e4b1}.multi-stat.red{background:#e73551;color:white!important}.multi-stat.yellow{background:#ffca19}.multi-note{color:#60738c;font-size:13px;margin:14px 0 0}.multi-main .card{border-radius:16px;overflow:hidden}.multi-main .highcharts-figure{margin:0;min-width:0}.multi-main .row>*{min-width:0}@media(max-width:1100px){.multi-cards{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:600px){.multi-main{padding:14px}.multi-cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.multi-stat{padding:14px}.multi-search{flex-direction:column}.multi-heading h1{font-size:23px}}
</style></head><body class="multi-page mka-suite-dashboard-page">
<?php if (!defined('ADMIN2URL')) define('ADMIN2URL','/admin/'); include('../../topo.php'); mka_suite_render_top_spacing_style($conn); ?>
<main class="multi-main mka-suite-dashboard-start">
<header class="multi-heading"><div><h1>Multiempresas</h1><p>Visão de clientes e resultados financeiros</p></div><nav class="multi-nav" aria-label="Atalhos">
<a href="../busca_inteligente/">Clientes</a><a href="../busca_inteligente/relcontratos.php">Contratos</a><?php if($canConfig){?><a href="cfg.php">Configurações</a><?php } ?></nav></header>
<form class="multi-search" action="../busca_inteligente/index.php" method="get"><input type="search" name="busca" aria-label="Pesquisar clientes" placeholder="Pesquisar cliente por nome, documento ou cadastro"><button type="submit">Buscar</button></form>
<section class="multi-summary"><h2>Clientes</h2>
<?php if (!$canTotals) { ?><p>Seu usuário não possui permissão para visualizar os totais.</p><?php } elseif($multiError) { ?><p role="alert">Não foi possível carregar os indicadores. Tente novamente.</p><?php } else { ?>
<div class="multi-cards"><?php foreach($cards as $card) { ?>
<a class="multi-stat <?= $card[4] ?>" href="../busca_inteligente/index.php?busca=<?=rawurlencode($card[3])?>"><span><?=mka_contract_escape($card[0])?></span><strong><?=number_format($card[1],0,',','.')?></strong><small><?=number_format($card[2]>0 ? $card[1]/$card[2]*100 : 0,2,',','.')?>%</small></a>
<?php } ?></div><p class="multi-note">Total inclui adicionais. Os demais indicadores de situação consideram os cadastros principais.</p><?php } ?></section>
<?php if($canFinance) {
    $tot_fat_previsto=$tot_entrada=$tot_a_receber=$tot_contas_pagar=$tot_saida=$saldo=$tot_geral_entrada_sem_emprestimo=$tot_geral_emprestimos=0;
    include __DIR__.'/graf_periodo.php';
    include __DIR__.'/graficos.php';
} else { ?><section class="multi-summary"><h2>Faturamento</h2><p>Seu usuário não possui permissão para visualizar os gráficos financeiros.</p></section><?php } ?>
</main><?php include('../../baixo.php'); ?></body></html>
