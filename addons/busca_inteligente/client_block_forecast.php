<?php
ob_start(); require_once __DIR__.'/config.php'; ob_end_clean();
require_once __DIR__.'/../shared/client_action_access.php';
require_once __DIR__.'/../shared/block_forecast.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
$uuid=isset($_GET['uuid']) ? trim((string)$_GET['uuid']) : '';
$client=mka_action_client($link,$uuid,!empty($acesso_permitido));
$safe=mysqli_real_escape_string($link,$uuid);
$query=mysqli_query($link,"SELECT dias_corte,observacao,rem_obs,isento,cli_ativado FROM sis_cliente WHERE uuid_cliente='$safe' LIMIT 1");
$details=$query ? mysqli_fetch_assoc($query) : null;
$login=mysqli_real_escape_string($link,$client['login']);
$query=mysqli_query($link,"SELECT COUNT(*) open_count, MIN(DATE(datavenc)) oldest_due FROM sis_lanc WHERE login='$login' AND deltitulo=0 AND status IN ('aberto','vencido')");
$titles=$query ? mysqli_fetch_assoc($query) : null;
$query=mysqli_query($link,"SELECT nome,valor FROM sis_opcao WHERE nome IN ('auto_corte','auto_desbloqueio')");
if (!$details || !$titles || !$query) { http_response_code(503); echo json_encode(array('ok'=>false)); exit; }
$options=array(); while($row=mysqli_fetch_assoc($query)) $options[$row['nome']]=$row['valor'];
$date=mysqli_query($link,'SELECT CURDATE() today');
$today=$date ? mysqli_fetch_assoc($date) : null;
if (!$today) { http_response_code(503); echo json_encode(array('ok'=>false)); exit; }
echo json_encode(array('ok'=>true,'forecast'=>mka_block_forecast($details,$titles,$options,$today['today'])));
