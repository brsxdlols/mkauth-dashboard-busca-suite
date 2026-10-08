<?php
// Reuse the installation's admin session, including older addon configurations.
$notificationCwd = getcwd();
chdir(__DIR__.'/../dashboard');
ob_start();
http_response_code(403);
require_once 'addons.class.php';
if (session_status() === PHP_SESSION_NONE) {
    $adminCookies = array();
    foreach (array_keys($_COOKIE) as $name) {
        if (preg_match('/^_admin-[a-f0-9]{40}-MKA$/D', $name)) $adminCookies[] = $name;
    }
    if (count($adminCookies) === 1) session_name($adminCookies[0]);
    elseif (count($adminCookies) === 0 && isset($_COOKIE['mka'])) session_name('mka');
    else { ob_end_clean(); http_response_code(403); exit('Sessão administrativa necessária.'); }
    session_start();
}
if (empty($_SESSION['MKA_Logado']) && empty($_SESSION['MM_Usuario'])) {
    ob_end_clean(); http_response_code(403); exit('Acesso negado.');
}
require __DIR__.'/../dashboard/config.php';
ob_end_clean();
http_response_code(200);
chdir($notificationCwd);
