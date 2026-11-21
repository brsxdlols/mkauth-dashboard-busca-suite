<?php
require_once __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
$link=$conn;
$link->set_charset('utf8mb4');
$usuario_logado=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
$acesso_permitido=mka_installation_permission($link,$usuario_logado,'perm_relFat');
if(!$acesso_permitido){http_response_code(403);exit('Sem permissão para consultar clientes desativados.');}
$rdScope=mka_attendance_scope($link,$usuario_logado);
$Manifest=json_decode(file_get_contents(__DIR__.'/manifest.json'));
if(empty($_SESSION['rd_csrf']))$_SESSION['rd_csrf']=bin2hex(random_bytes(24));

$ext_mk=is_file(__DIR__.'/../../login.hhvm')?'.hhvm':'.php';
