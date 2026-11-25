<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/web_session_counts.php';
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store, private');
$inventory=mka_web_session_inventory();
if($inventory===null){http_response_code(503);exit('Não foi possível consultar os usuários conectados.');}
$users=$inventory['users'];uksort($users,'strnatcasecmp');
function mka_web_user_escape($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuários conectados — MK-Auth</title>
<link rel="stylesheet" href="/admin/estilos/mk-auth.css"><link rel="stylesheet" href="/admin/estilos/bi-icons.css">
<style>body{padding:18px}h1{font-size:20px;font-weight:700;margin-bottom:8px}.web-users-note{margin-bottom:16px;color:#667085}.web-users-table{width:100%}.web-users-table td,.web-users-table th{text-align:left}.web-users-table td:last-child,.web-users-table th:last-child{text-align:center}</style></head><body>
<h1><i class="bi-people-fill" aria-hidden="true"></i> Usuários conectados</h1>
<p class="web-users-note">Cada usuário aparece uma vez. A quantidade indica suas sessões web no MK-Auth.</p>
<table class="table is-fullwidth web-users-table"><thead><tr><th scope="col">Usuário</th><th scope="col">Sessões</th></tr></thead><tbody>
<?php foreach($users as $name=>$count): ?><tr><td><?=mka_web_user_escape($name)?></td><td><?= (int)$count ?></td></tr><?php endforeach; ?>
<?php if(!$users): ?><tr><td colspan="2">Nenhum usuário conectado.</td></tr><?php endif; ?>
</tbody></table></body></html>
