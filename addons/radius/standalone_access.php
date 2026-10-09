<?php
require __DIR__.'/../shared/notification_bootstrap.php';
if (!permissao('perm_clientes')) { http_response_code(403); exit('Acesso negado.'); }
$radiusUser=(string)($_SESSION['MKA_Usuario'] ?? $_SESSION['MM_Usuario'] ?? '');
$radiusAccess=$conn->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');
$radiusAccess->bind_param('s',$radiusUser);
$radiusAccess->execute();
$radiusPermissions=$radiusAccess->get_result()->fetch_assoc();
if (!$radiusPermissions || (trim((string)$radiusPermissions['cli_grupos'])!=='' && !in_array('full_clientes',array_map('trim',explode(',',(string)$radiusPermissions['cli_grupos'])),true))) {
    http_response_code(403); exit('Log geral requer acesso a todos os grupos de clientes.');
}
