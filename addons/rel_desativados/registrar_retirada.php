<?php
require_once __DIR__.'/config.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Método não permitido');}
$csrf=$_POST['csrf']??'';
if(!is_string($csrf)||!hash_equals($_SESSION['rd_csrf'],$csrf)){http_response_code(403);exit('Sessão expirada. Recarregue a página.');}
$login=$_POST['login']??'';$acao=$_POST['acao']??'';
if(!is_string($login)||$login===''||strlen($login)>100||!in_array($acao,array('sim','nao'),true)){http_response_code(422);exit('Dados inválidos');}
$stmt=$link->prepare("SELECT c.login FROM sis_cliente c WHERE c.login=? AND c.cli_ativado='n' AND ($rdScope) LIMIT 1");
$stmt->bind_param('s',$login);$stmt->execute();$allowed=$stmt->get_result()->num_rows>0;$stmt->close();
if(!$allowed){http_response_code(403);exit('Cliente indisponível para este operador.');}
// Only tracks equipment collection; never activates/deactivates a customer.
$stmt=$link->prepare("INSERT INTO cliente_desativados_ton (login,equipamento,data_retirada) VALUES (?,?,?) ON DUPLICATE KEY UPDATE equipamento=VALUES(equipamento),data_retirada=VALUES(data_retirada)");
$date=$acao==='sim'?date('Y-m-d H:i:s'):null;
$stmt->bind_param('sss',$login,$acao,$date);
if(!$stmt->execute()){http_response_code(500);exit('Não foi possível registrar a retirada.');}
$stmt->close();echo 'ok';
