<?php
ob_start();
include('config.php');
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

function mka_delete_installation_reply($success, $message, $status = 200, $extra = array())
{
    http_response_code($status);
    echo json_encode(array_merge(array('success' => $success, 'message' => $message), $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mka_delete_installation_reply(false, 'Método inválido.', 405);
}

if (!permissao('perm_instalacao')) {
    mka_delete_installation_reply(false, 'Você não possui permissão para excluir solicitações.', 403);
}

$uuid = isset($_POST['uuid']) ? trim((string) $_POST['uuid']) : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) {
    mka_delete_installation_reply(false, 'Solicitação inválida.', 422);
}

$select = mysqli_prepare($conn, "SELECT * FROM sis_solic WHERE uuid_solic = ? LIMIT 1");
if (!$select) {
    mka_delete_installation_reply(false, 'Não foi possível preparar a exclusão.', 500);
}
mysqli_stmt_bind_param($select, 's', $uuid);
mysqli_stmt_execute($select);
$result = mysqli_stmt_get_result($select);
$deletedRow = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($select);

if (!$deletedRow) {
    mka_delete_installation_reply(false, 'A solicitação não existe mais ou já foi excluída.', 404);
}

$statement = mysqli_prepare($conn, "DELETE FROM sis_solic WHERE uuid_solic = ? LIMIT 1");
if (!$statement) {
    mka_delete_installation_reply(false, 'Não foi possível preparar a exclusão.', 500);
}

mysqli_stmt_bind_param($statement, 's', $uuid);
$executed = mysqli_stmt_execute($statement);
$affected = $executed ? mysqli_stmt_affected_rows($statement) : 0;
mysqli_stmt_close($statement);

if (!$executed) {
    mka_delete_installation_reply(false, 'Não foi possível excluir a solicitação.', 500);
}
if ($affected < 1) {
    mka_delete_installation_reply(false, 'A solicitação não pôde ser excluída.', 409);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
$undoToken = bin2hex(random_bytes(24));
if (!isset($_SESSION['mka_deleted_installation_requests']) || !is_array($_SESSION['mka_deleted_installation_requests'])) {
    $_SESSION['mka_deleted_installation_requests'] = array();
}
$_SESSION['mka_deleted_installation_requests'][$undoToken] = array(
    'expires' => time() + 600,
    'row' => $deletedRow
);

mka_delete_installation_reply(true, 'Solicitação excluída com sucesso.', 200, array('undo_token' => $undoToken));
