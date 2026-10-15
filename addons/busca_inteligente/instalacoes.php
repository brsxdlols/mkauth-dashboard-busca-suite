<?php
include('nav/header.php');require_once __DIR__.'/../shared/installations.php';
if(!mka_installation_permission($link,(string)$usuario_logado,'perm_instalacao')){http_response_code(403);exit('Sem permissão para instalações.');}
$link->set_charset('utf8mb4');
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
$scope=mka_installation_scope($link,$operator,mka_installation_permission($link,$operator,'perm_instTodosUsuarios'));$canEdit=mka_installation_permission($link,$operator,'perm_instAlterar');
function installation_h($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function installation_date($value){return !$value||substr($value,0,4)==='0000'?'Não informada':date('d/m/Y H:i',strtotime($value));}
$search=is_string($_GET['busca']??null)?trim($_GET['busca']):'';$state=is_string($_GET['status']??null)?$_GET['status']:'';$tech=is_string($_GET['tecnico']??null)?$_GET['tecnico']:'';
$where=mka_installation_where($scope);$options=$link->query('SELECT DISTINCT s.tecnico,f.nome tecnico_nome FROM sis_solic s LEFT JOIN sis_func f ON CAST(f.id AS CHAR)=s.tecnico WHERE '.$where.' ORDER BY s.tecnico')->fetch_all(MYSQLI_ASSOC);
$params=array();$types='';
if($search!==''){$where.=' AND (s.nome LIKE ? OR s.login LIKE ? OR s.endereco_res LIKE ? OR s.bairro_res LIKE ? OR s.cidade_res LIKE ?)';for($i=0;$i<5;$i++)$params[]='%'.$search.'%';$types.='sssss';}
if(in_array($state,array('aberto','pendente','atrasado'),true)){$where.=' AND s.status=?';$params[]=$state;$types.='s';}
if($tech!==''){$where.=' AND s.tecnico=?';$params[]=$tech;$types.='s';}
$q=$link->prepare('SELECT s.id,s.uuid_solic,s.nome,s.login,s.status,s.tecnico,s.visita,s.processamento,s.endereco_res,s.numero_res,s.bairro_res,s.cidade_res,s.estado_res,s.celular,s.telefone,s.plano,s.obs,s.visitado,s.instalado,f.nome tecnico_nome FROM sis_solic s LEFT JOIN sis_func f ON CAST(f.id AS CHAR)=s.tecnico WHERE '.$where.' ORDER BY s.visita IS NULL,s.visita,s.id');
if($types!=='')$q->bind_param($types,...$params);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$counts=array('all'=>count($rows),'late'=>0,'upcoming'=>0,'unscheduled'=>0,'unassigned'=>0);
foreach($rows as &$row){$row['_timing']=mka_ticket_timing($row['visita']);$key=$row['_timing']['state'];if(isset($counts[$key]))$counts[$key]++;$row['_unassigned']=in_array(strtolower(trim((string)$row['tecnico'])),array('','0','nenhum','nenhuma'),true);if($row['_unassigned'])$counts['unassigned']++;}unset($row);
?>
<body><?php include('../../topo.php');require_once __DIR__.'/../shared/layout_mode.php';mka_suite_render_top_spacing_style($link); ?>
<link rel="stylesheet" href="../shared/installations.css?v=20261015-2">
<link rel="stylesheet" href="../shared/ticket_board.css?v=20261015-4">
<main class="ticket-board installation-board" data-installation-board data-can-edit="<?= $canEdit?'1':'0'; ?>">
<?php require_once __DIR__.'/../shared/support_navigation.php';mka_support_navigation($link,$Manifest,$operator,'installations'); ?>
<header class="ticket-page-heading"><div><h1>Central de instalações</h1><p>Organize as visitas, acompanhe os pendentes e consulte os dados da instalação.</p></div><a class="ticket-create" href="/admin/instalar_ins<?= installation_h($ext_mk); ?>" data-open-native-installation>+ Nova instalação</a></header>
<form class="ticket-search" method="get"><label>Pesquisar<input name="busca" value="<?= installation_h($search); ?>" placeholder="Nome, login ou endereço"></label><label>Situação<select name="status"><option value="">Todas em aberto</option><?php foreach(array('aberto'=>'Aberta','pendente'=>'Pendente','atrasado'=>'Atrasada') as $value=>$label){ ?><option value="<?= $value; ?>" <?= $state===$value?'selected':''; ?>><?= $label; ?></option><?php } ?></select></label><label>Técnico<select name="tecnico"><option value="">Todos</option><?php foreach($options as $option){ ?><option value="<?= installation_h($option['tecnico']); ?>" <?= $tech===$option['tecnico']?'selected':''; ?>><?= installation_h($option['tecnico_nome']?:($option['tecnico']?:'Sem técnico')); ?></option><?php } ?></select></label><button type="submit">Buscar</button><a href="instalacoes.php">Limpar filtros</a></form>
<nav class="ticket-overview" aria-label="Filtrar instalações"><?php foreach(array('all'=>'Todas abertas','late'=>'Visitas atrasadas','upcoming'=>'Próximas 24h','unscheduled'=>'Sem agendamento','unassigned'=>'Sem técnico') as $key=>$label){ ?><button type="button" data-ticket-filter="<?= $key; ?>" data-label="<?= $label; ?>" aria-pressed="<?= $key==='all'?'true':'false'; ?>"><span><?= $label; ?></span><strong><?= $counts[$key]; ?></strong></button><?php } ?></nav>
<p class="ticket-count" id="ticket-filter-status" role="status"><?= count($rows); ?> instalações em aberto · Filtros atuais</p>
<div class="ticket-view-controls"><button type="button" data-ticket-view="list">Lista de instalações</button><button type="button" data-ticket-view="calendar">Calendário de visitas</button></div>
<p id="ticket-calendar-notice" role="status"></p>
<section id="ticket-calendar" class="ticket-calendar" hidden><div class="ticket-calendar-toolbar"><button type="button" data-calendar-step="-1">Anterior</button><button type="button" data-calendar-step="0">Hoje</button><button type="button" data-calendar-step="1">Próximo</button><strong id="ticket-calendar-range" role="status"></strong></div><p><?= $canEdit?'Arraste a visita para mudar o dia ou clique para editar a instalação.':'Consulte as visitas no calendário ou na lista de instalações.'; ?></p><div id="ticket-calendar-grid" class="ticket-calendar-grid"></div></section>
<section class="ticket-list">
<?php foreach($rows as $row){$timing=$row['_timing'];$visit=$timing['state']==='unscheduled'?'':$row['visita'];$edit='/admin/instalar_alt'.$ext_mk.'?'.http_build_query(array('uuid'=>$row['uuid_solic'])); ?>
<details class="ticket-item" data-ticket-id="<?= (int)$row['id']; ?>" data-ticket-state="<?= $timing['state']; ?>" data-unassigned="<?= $row['_unassigned']?'1':'0'; ?>" data-visit="<?= installation_h($visit); ?>" data-subject="Instalação" data-priority="<?= installation_h($row['status']); ?>" data-client="<?= installation_h($row['nome']); ?>" data-opening="<?= installation_h(installation_date($row['processamento'])); ?>">
<summary><div><small>Cliente</small><strong><?= installation_h($row['nome']); ?></strong><span><?= installation_h($row['login']); ?></span></div><div><small>Instalação / situação</small><strong>#<?= (int)$row['id']; ?> · <?= installation_h(ucfirst($row['status'])); ?></strong></div><div><small>Técnico</small><span><?= installation_h($row['_unassigned']?'Sem técnico':($row['tecnico_nome']?:$row['tecnico'])); ?></span></div><div><small>Visita</small><span data-ticket-visit-label><?= installation_h(installation_date($row['visita'])); ?></span><span class="ticket-sla <?= $timing['class']; ?>"><?= installation_h($timing['text']); ?></span></div><span aria-hidden="true">⌄</span></summary>
<div class="installation-details"><p><strong>Cadastro:</strong> <?= installation_h(installation_date($row['processamento'])); ?> · <strong>Plano:</strong> <?= installation_h($row['plano']); ?></p><p><strong>Endereço:</strong> <?= installation_h(implode(' ',array_filter(array($row['endereco_res'],$row['numero_res'],$row['bairro_res'],$row['cidade_res'],$row['estado_res'])))); ?></p><p><strong>Contato:</strong> <?= installation_h($row['celular'].' '.$row['telefone']); ?></p><p><strong>Visitado:</strong> <?= installation_h($row['visitado']); ?> · <strong>Instalado:</strong> <?= installation_h($row['instalado']); ?></p><p class="installation-notes"><?= installation_h($row['obs']); ?></p><?php if($canEdit){ ?><a href="<?= installation_h($edit); ?>" data-open-native-installation class="ticket-create">Editar instalação</a><?php } ?></div>
</details><?php } ?>
<p id="ticket-empty" class="ticket-empty" <?= count($rows)?'hidden':''; ?>>Nenhuma instalação em aberto nos filtros atuais. Novas instalações aparecerão aqui após a gravação.</p>
</section></main>
<script src="../shared/ticket_board.js?v=20261015-6"></script><script src="../shared/ticket_native_modal.js?v=20261015-8"></script>
<?php include('../../baixo.php'); ?>
<script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
</body></html>
