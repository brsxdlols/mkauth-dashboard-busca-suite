<?php
ob_start();
include('config.php');
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

function mka_undo_reply($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode(array('success' => $success, 'message' => $message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') mka_undo_reply(false, 'Método inválido.', 405);
if (!permissao('perm_instalacao')) mka_undo_reply(false, 'Você não possui permissão para restaurar solicitações.', 403);
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

$token = isset($_POST['undo_token']) ? trim((string) $_POST['undo_token']) : '';
if (!preg_match('/^[a-f0-9]{48}$/', $token)) mka_undo_reply(false, 'Código de restauração inválido.', 422);

$items = isset($_SESSION['mka_deleted_installation_requests']) ? $_SESSION['mka_deleted_installation_requests'] : array();
$backup = isset($items[$token]) ? $items[$token] : null;
if (!$backup || !isset($backup['row']) || (int) $backup['expires'] < time()) {
    unset($_SESSION['mka_deleted_installation_requests'][$token]);
    mka_undo_reply(false, 'O prazo para desfazer terminou. A solicitação não foi restaurada.', 410);
}

$row = $backup['row'];
$columns = array();
$values = array();
foreach ($row as $column => $value) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) continue;
    $columns[] = '`' . $column . '`';
    $values[] = $value === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
}
if (!$columns) mka_undo_reply(false, 'Não há dados para restaurar.', 500);

$sql = 'INSERT INTO sis_solic (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
if (!mysqli_query($conn, $sql)) {
    mka_undo_reply(false, 'Não foi possível restaurar a solicitação. Talvez ela já tenha sido recriada.', 409);
}

unset($_SESSION['mka_deleted_installation_requests'][$token]);
mka_undo_reply(true, 'Solicitação restaurada com sucesso.');
