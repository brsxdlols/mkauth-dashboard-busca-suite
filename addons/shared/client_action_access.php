<?php
// Call after the addon's authenticated config.php has initialized the session.
function mka_action_client($db, $uuid, $allowed) {
    $user = isset($_SESSION['MKA_Usuario']) && $_SESSION['MKA_Usuario'] !== ''
        ? $_SESSION['MKA_Usuario'] : (isset($_SESSION['MM_Usuario']) ? $_SESSION['MM_Usuario'] : '');
    if (!$allowed || $user === '') { http_response_code(403); exit('Acesso negado.'); }
    if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) { http_response_code(422); exit('Cliente inv?lido.'); }
    $safe = mysqli_real_escape_string($db, $uuid);
    $q = mysqli_query($db, "SELECT uuid_cliente,login,nome,grupo,bloqueado FROM sis_cliente WHERE uuid_cliente='$safe' LIMIT 1");
    $client = $q ? mysqli_fetch_assoc($q) : null;
    if (!$client) { http_response_code(404); exit('Cliente n?o encontrado.'); }
    $safeUser = mysqli_real_escape_string($db, $user);
    $q = mysqli_query($db, "SELECT cli_grupos FROM sis_acesso WHERE login='$safeUser' LIMIT 1");
    $access = $q ? mysqli_fetch_assoc($q) : null;
    if (!$access) { http_response_code(403); exit('Usu?rio sem acesso.'); }
    $groups = array_map('trim', explode(',', (string) $access['cli_grupos']));
    if (trim((string) $access['cli_grupos']) !== '' && !in_array('full_clientes', $groups, true) && !in_array($client['grupo'], $groups, true)) {
        http_response_code(403); exit('Cliente fora dos grupos permitidos.');
    }
    return $client;
}
function mka_action_token() {
    if (empty($_SESSION['mka_client_action_csrf'])) $_SESSION['mka_client_action_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['mka_client_action_csrf'];
}
function mka_action_check_token() {
    if (!isset($_POST['csrf']) || !hash_equals(mka_action_token(), (string) $_POST['csrf'])) {
        http_response_code(403); exit('Sess?o expirada. Reabra o formul?rio.');
    }
}
