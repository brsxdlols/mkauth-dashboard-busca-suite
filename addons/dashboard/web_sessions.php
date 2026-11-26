<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/web_session_counts.php';
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store, private');
$scope=($_GET['scope']??'mine')==='all'?'all':'mine';
$native=dirname(__DIR__,2).'/sessoes.hhvm';if(!is_file($native))$native=dirname(__DIR__,2).'/sessoes.php';
if(is_file($native)){header('Location: /admin/'.basename($native).'?scope='.$scope);exit;}
$inventory=mka_web_session_inventory();
if($inventory===null){http_response_code(503);exit('Lista de sessoes indisponivel.');}
$actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));
function mka_session_text($text){return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo $scope==='all'?'Usuários conectados':'Minhas sessões'; ?> — MK-Auth</title>
<style>body{margin:14px;font:14px/1.55 Arial,sans-serif;color:#29425b}.session{display:flex;gap:16px;padding:18px;margin:0 0 20px;border:1px solid #ddd;border-radius:5px;box-shadow:0 2px 3px #0001;overflow-wrap:anywhere}.device{font-size:32px;color:#555}.details{min-width:0}h1{font-size:18px}p{color:#60758b}</style>
<h1><?php echo $scope==='all'?'Usuários conectados':'Minhas sessões'; ?></h1>
<?php foreach($inventory['entries']as$entry){if($scope==='mine'&&$entry['user']!==$actor)continue; ?>
<article class="session"><span class="device" aria-hidden="true">&#128421;</span><div class="details"><strong>Usuário:</strong> <?php echo mka_session_text($entry['user']); ?><br>
<strong>Navegador:</strong> <?php echo mka_session_text($entry['agent']!==''?$entry['agent']:'Não registrado nesta versão'); ?><br>
<strong>Última atividade:</strong> <?php echo date('d/m H:i',(int)$entry['updated']); ?><br>
<strong>IP:</strong> <?php echo mka_session_text($entry['ip']!==''?$entry['ip']:'Não registrado nesta versão'); ?></div></article>
<?php } ?>
<p>Esta versão não oferece a ação nativa de deslogar sessões por esta tela.</p></html>
