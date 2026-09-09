<?php
ob_start();
include('config.php');
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

function mka_delete_installation_reply($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode(array('success' => $success, 'message' => $message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    mka_delete_installation_reply(false, 'A solicitação não existe mais ou já foi excluída.', 404);
}

mka_delete_installation_reply(true, 'Solicitação excluída com sucesso.');
