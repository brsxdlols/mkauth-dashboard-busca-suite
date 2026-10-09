<?php
require __DIR__.'/standalone_access.php';
require_once __DIR__.'/client_links.php';
$login=trim((string)($_GET['login'] ?? ''));
if (strlen($login)>64) { http_response_code(422); exit('Login inválido.'); }
$clients=radius_live_clients($conn,array($login),true,array());
$match=$clients[strtolower($login)] ?? null;
if ($match) { header('Location: '.$match['url']); exit; }
?>
<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Log RADIUS</title>
<script src="../shared/compact_notice.js"></script>
<script>document.addEventListener('DOMContentLoaded',function(){mkaCompactNotice('Log RADIUS','Usuário não encontrado no sistema ou tentativa incorreta');});</script></html>
