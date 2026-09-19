<?php
ob_start(); require_once __DIR__ . '/config.php'; ob_end_clean();
require_once __DIR__ . '/../shared/manual_block_audit.php';
require_once __DIR__ . '/../shared/client_action_access.php';

header('Content-Type: application/json; charset=utf-8');
mka_manual_block_audit_ensure_table($link);

$uuid = isset($_REQUEST['uuid']) ? trim((string) $_REQUEST['uuid']) : '';
$authorizedClient = mka_action_client($link, $uuid, !empty($acesso_permitido));
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Cliente inv?lido.'));
    exit;
}

$safeUuid = mysqli_real_escape_string($link, $uuid);
$clientQuery = @mysqli_query($link, "SELECT uuid_cliente,login,nome FROM sis_cliente WHERE uuid_cliente='{$safeUuid}' LIMIT 1");
$client = $clientQuery ? mysqli_fetch_assoc($clientQuery) : null;
if (!$client) {
    http_response_code(404);
    echo json_encode(array('ok' => false, 'message' => 'Cliente n?o encontrado.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(array('ok' => true, 'csrf' => mka_action_token(), 'blocked' => $authorizedClient['bloqueado'] === 'sim', 'block' => mka_manual_block_audit_latest_block($link, $uuid)));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'M?todo n?o permitido.'));
    exit;
}

$action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
mka_action_check_token();
$reason = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';
if (!in_array($action, array('bloqueio', 'desbloqueio'), true) || $reason === '') {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Informe o motivo da opera??o.'));
    exit;
}

if (isset($_POST['phase']) && $_POST['phase'] === 'confirm') {
    $expected = $action === 'bloqueio' ? 'sim' : 'nao';
    if ($authorizedClient['bloqueado'] !== $expected) {
        http_response_code(409);
        echo json_encode(array('ok'=>false,'message'=>'A opera??o nativa n?o confirmou a altera??o do cliente. Atualize e confira o estado antes de tentar novamente.'));
        exit;
    }
    if ($action === 'bloqueio') {
        $column = mysqli_query($link, "SHOW COLUMNS FROM sis_cliente LIKE 'tipobloq'");
        if ($column && mysqli_num_rows($column)) {
            if (!mysqli_query($link, "UPDATE sis_cliente SET tipobloq='man' WHERE uuid_cliente='$safeUuid' AND bloqueado='sim'")) {
                http_response_code(500); echo json_encode(array('ok'=>false,'message'=>'Bloqueado, mas n?o foi poss?vel confirmar a marca??o manual.')); exit;
            }
        }
    }
    echo json_encode(array('ok'=>true)); exit;
}

$login = mysqli_real_escape_string($link, (string) $client['login']);
$name = mysqli_real_escape_string($link, (string) $client['nome']);
$safeAction = mysqli_real_escape_string($link, $action);
$safeReason = mysqli_real_escape_string($link, substr($reason, 0, 2000));
$safeUser = mysqli_real_escape_string($link, mka_manual_block_audit_user());
$saved = @mysqli_query($link, "INSERT INTO dashboard_am_manual_block_audit (uuid_cliente,login_cliente,nome_cliente,acao,motivo,usuario,criado_em) VALUES ('{$safeUuid}','{$login}','{$name}','{$safeAction}','{$safeReason}','{$safeUser}',NOW())");

if (!$saved) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => 'N?o foi poss?vel registrar a opera??o.'));
    exit;
}

echo json_encode(array('ok' => true));
