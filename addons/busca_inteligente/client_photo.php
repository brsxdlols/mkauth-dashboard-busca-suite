<?php
session_name('mka');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function mka_photo_reply($ok, $message, $status = 200) {
    http_response_code($status);
    echo json_encode(array('ok' => $ok, 'message' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') mka_photo_reply(false, 'Método inválido.', 405);
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Usuario']) && empty($_SESSION['MM_Usuario'])) mka_photo_reply(false, 'Sessão expirada.', 401);
$uuid = isset($_POST['uuid']) ? trim((string) $_POST['uuid']) : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) mka_photo_reply(false, 'Cliente inválido.', 422);
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) mka_photo_reply(false, 'Selecione uma imagem válida.', 422);
if ((int) $_FILES['foto']['size'] > 5 * 1024 * 1024) mka_photo_reply(false, 'A imagem deve ter no máximo 5 MB.', 422);

$imageInfo = @getimagesize($_FILES['foto']['tmp_name']);
$allowed = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
if (defined('IMAGETYPE_WEBP')) $allowed[IMAGETYPE_WEBP] = 'webp';
if (!$imageInfo || !isset($allowed[$imageInfo[2]])) mka_photo_reply(false, 'Use uma imagem JPG, PNG ou WebP.', 422);

$safeUuid = preg_replace('/[^A-Za-z0-9-]/', '', $uuid);
$filename = 'cliente_' . $safeUuid . '_' . time() . '.' . $allowed[$imageInfo[2]];
$destination = '/opt/mk-auth/mkfiles/' . $filename;
if (!@move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) mka_photo_reply(false, 'Não foi possível salvar a imagem.', 500);
@chmod($destination, 0644);

$uuidSql = mysqli_real_escape_string($link, $uuid);
$current = @mysqli_query($link, "SELECT foto FROM sis_cliente WHERE uuid_cliente='{$uuidSql}' LIMIT 1");
$oldPhoto = ($current && ($row = mysqli_fetch_assoc($current))) ? trim((string) $row['foto']) : '';
$filenameSql = mysqli_real_escape_string($link, $filename);
if (!@mysqli_query($link, "UPDATE sis_cliente SET foto='{$filenameSql}' WHERE uuid_cliente='{$uuidSql}' LIMIT 1") || mysqli_affected_rows($link) < 1) {
    @unlink($destination);
    mka_photo_reply(false, 'Cliente não encontrado ou foto não atualizada.', 404);
}
if ($oldPhoto !== '' && $oldPhoto !== 'img_nao_disp.gif' && strpos($oldPhoto, 'cliente_') === 0) {
    $oldPath = '/opt/mk-auth/mkfiles/' . basename($oldPhoto);
    if (is_file($oldPath)) @unlink($oldPath);
}
mka_photo_reply(true, 'Foto atualizada com sucesso.');
