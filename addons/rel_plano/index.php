<?php
include('addons.class.php');
session_name('mka');if(!isset($_SESSION))session_start();
if(!isset($_SESSION['MKA_Logado'])){http_response_code(403);exit('Acesso negado.');}
require __DIR__.'/config.php';
function rp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$search=isset($_GET['search'])&&is_string($_GET['search'])?trim($_GET['search']):'';
$plans=array();$problem=false;$usage=array();
require_once __DIR__.'/../shared/support_attendance.php';
if($acesso_permitido){$scope=mka_attendance_scope($link,(string)$usuario_logado);$counts=$link->query("SELECT plano,SUM(active_count) active_count,SUM(additional_count) additional_count FROM (SELECT c.plano,COUNT(*) active_count,0 additional_count FROM sis_cliente c WHERE ($scope) AND c.cli_ativado='s' GROUP BY c.plano UNION ALL SELECT a.plano,0,COUNT(*) FROM sis_adicional a JOIN sis_cliente c ON c.login=a.login WHERE ($scope) AND c.cli_ativado='s' GROUP BY a.plano) x GROUP BY plano");if($counts)while($r=$counts->fetch_assoc())$usage[$r['plano']]=$r;}
if($acesso_permitido){
 mysqli_set_charset($link,'utf8mb4');
 $sql="SELECT uuid_plano,nome,valor,velup,veldown FROM sis_plano WHERE oculto='nao'";
 $sql.=' ORDER BY valor DESC,nome ASC';
 $stmt=$link->prepare($sql);
 if($stmt){$stmt->execute();$result=$stmt->get_result();while($row=$result->fetch_assoc())$plans[]=$row;$stmt->close();}else $problem=true;
}
?>
<!doctype html><html lang="pt-BR"<?=isset($_SESSION['MM_Usuario'])?'':' class="has-navbar-fixed-top"'?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Relatório de planos · MK-AUTH</title>
<link rel="stylesheet" href="/admin/estilos/mk-auth.css"><link rel="stylesheet" href="/admin/estilos/font-awesome.css"><script src="/admin/scripts/jquery.js"></script><script src="/admin/scripts/mk-auth.js"></script>
<script src="plans.js?v=20261118-4" defer></script><link rel="stylesheet" href="plans.css?v=20261118-7"><link rel="stylesheet" href="../shared/addon_header_mobile.css?v=20261118-3"></head><body class="rp-body">
<?php include __DIR__.'/../../topo.php'; ?>
<main class="rp-main"><header class="rp-heading"><span>GESTÃO DE PLANOS</span><h1>Relatório de planos</h1><p>Consulte valores e velocidades dos planos disponíveis.</p></header>
<?php if(!$acesso_permitido):?><p role="alert">Acesso não permitido.</p><?php else: ?>
<section class="rp-panel"><form method="get" class="rp-search"><label for="rp-search">Pesquisar plano<input type="search" id="rp-search" name="search" value="<?=rp_h($search)?>" placeholder="Nome do plano ou valor"></label><button type="submit">Buscar</button><a href="index.php">Limpar</a></form></section>
<div class="rp-columns"><section class="rp-panel"><div class="rp-section-heading"><h2>Planos disponíveis</h2><span><?=count($plans)?> plano(s)<?=$search!==''?' encontrados':''?></span></div>
<?php if($problem):?><p role="alert">Não foi possível consultar os planos.</p><?php else:?>
<table class="rp-table"><thead><tr><th data-sort="text"><button>Plano ↕</button></th><th data-sort="number"><button>Valor mensal ↕</button></th><th data-sort="number"><button>Upload ↕</button></th><th data-sort="number"><button>Download ↕</button></th><th>Ação</th></tr></thead><tbody>
<?php foreach($plans as $i=>$plan):?><tr data-plan="<?=rp_h($plan['uuid_plano'])?>"><td data-label="Plano" data-value="<?=rp_h($plan['nome'])?>"><span class="rp-number">#<?=$i+1?></span><a class="rp-name" href="?<?=rp_h(http_build_query(array('search'=>$plan['nome'])))?>"><?=rp_h($plan['nome'])?></a></td><td data-label="Valor mensal" data-value="<?=(float)$plan['valor']?>"><strong>R$ <?=number_format((float)$plan['valor'],2,',','.')?></strong></td><td data-label="Upload"><?=rp_h($plan['velup'])?> <small>Kbps</small></td><td data-label="Download"><?=rp_h($plan['veldown'])?> <small>Kbps</small></td><td data-label="Ação"><a class="rp-edit" href="/admin/planos_alt<?=rp_h($ext_mk)?>?uuid=<?=rawurlencode($plan['uuid_plano'])?>">Alterar plano</a></td></tr><?php endforeach;?>
<?php if(!$plans):?><tr><td colspan="5">Nenhum plano encontrado para esta pesquisa.</td></tr><?php endif;?>
</tbody><tfoot><tr><th scope="row" colspan="4">Total de planos</th><td><?=count($plans)?></td></tr></tfoot></table><?php endif;?></section><section class="rp-panel"><div class="rp-section-heading"><h2>Clientes e receita por plano</h2></div><table class="rp-table rp-revenue"><thead><tr><th data-sort="text"><button>Plano ↕</button></th><th data-sort="number"><button>Clientes ↕</button></th><th data-sort="number"><button>Receita mensal ↕</button></th></tr></thead><tbody>
<?php $totalClients=0;$totalRevenue=0;foreach($plans as $plan):$u=$usage[$plan['nome']]??array();$count=(int)($u['active_count']??0)+(int)($u['additional_count']??0);$revenue=$count*(float)$plan['valor'];$totalClients+=$count;$totalRevenue+=$revenue;?><tr data-plan="<?=rp_h($plan['uuid_plano'])?>"><td data-label="Plano" data-value="<?=rp_h($plan['nome'])?>"><a class="rp-name" href="/admin/addons/busca_inteligente/index.php?busca=<?=rawurlencode($plan['nome'])?>"><?=rp_h($plan['nome'])?></a></td><td data-label="Clientes" data-value="<?=$count?>"><strong><?=$count?></strong><small class="rp-break"><?=(int)($u['active_count']??0)?> titulares + <?=(int)($u['additional_count']??0)?> adicionais</small></td><td data-label="Receita mensal" data-value="<?=$revenue?>"><strong>R$ <?=number_format($revenue,2,',','.')?></strong></td></tr><?php endforeach;?></tbody><tfoot><tr><th scope="row">Total de clientes e receita</th><td><?=$totalClients?></td><td>R$ <?=number_format($totalRevenue,2,',','.')?></td></tr></tfoot></table><p class="rp-note">Titulares ativos e adicionais de clientes ativos, conforme seus grupos permitidos.</p><p class="rp-note">Receita prevista = valor do plano × clientes vinculados. Não inclui descontos ou acréscimos individuais e não representa pagamentos recebidos.</p></section></div><p class="rp-note">Velocidades conforme o cadastro do plano, em Kbps. Apenas planos visíveis são exibidos.</p><?php endif;?></main>
<?php include __DIR__.'/../../baixo.php'; ?><script src="/admin/menu.js.php"></script></body></html>
