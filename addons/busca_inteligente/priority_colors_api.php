<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/priority_colors.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function priority_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!permissao('perm_chamados')&&!permissao('perm_config'))priority_reply(403,array('error'=>'Sem permissão.'));
$admin=permissao('perm_config');if(empty($_SESSION['suite_priority_csrf']))$_SESSION['suite_priority_csrf']=bin2hex(random_bytes(32));$csrf=$_SESSION['suite_priority_csrf'];session_write_close();$conn->set_charset('utf8mb4');
try{
 $method=$_SERVER['REQUEST_METHOD']??'GET';
 if($method==='POST'){
  if(!$admin)priority_reply(403,array('error'=>'Somente usuários com permissão de configuração podem alterar as cores.'));
  $data=json_decode(file_get_contents('php://input',false,null,0,20000),true);
  if(!is_array($data)||!is_string($data['csrf']??null)||!hash_equals($csrf,$data['csrf']))priority_reply(403,array('error'=>'Sessão expirada. Recarregue a página.'));
  mka_priority_save($conn,$data['colors']??null,$data['removed']??array());
 }elseif($method!=='GET')priority_reply(405,array('error'=>'Método não permitido.'));
 $colors=mka_priority_colors($conn);$hidden=mka_priority_hidden($conn);
 $rows=$conn->query("SELECT DISTINCT prioridade FROM sis_suporte WHERE prioridade<>'' ORDER BY prioridade LIMIT 100");foreach($rows->fetch_all(MYSQLI_ASSOC) as $r)if(!isset($colors[$r['prioridade']])&&!in_array($r['prioridade'],$hidden,true))$colors[$r['prioridade']]='#64748b';
 priority_reply(200,array('colors'=>$colors,'csrf'=>$csrf,'can_edit'=>$admin));
}catch(InvalidArgumentException $e){priority_reply(422,array('error'=>$e->getMessage()));}catch(Throwable $e){priority_reply(503,array('error'=>'Não foi possível carregar ou salvar as cores.'));}
