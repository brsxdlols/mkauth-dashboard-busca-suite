<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Usuario']) && empty($_SESSION['MM_Usuario'])) { http_response_code(401); exit('Sessão expirada.'); }

$uuid = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) { http_response_code(422); exit('Cliente inválido.'); }
$uuidSql = mysqli_real_escape_string($link, $uuid);
$result = @mysqli_query($link, "SELECT nome FROM sis_cliente WHERE uuid_cliente='{$uuidSql}' LIMIT 1");
if (!$result || !($client = mysqli_fetch_assoc($result))) { http_response_code(404); exit('Cliente não encontrado.'); }

$extension = file_exists(__DIR__ . '/../../cliente_info.hhvm') ? 'hhvm' : 'php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST']) : '127.0.0.1';
$url = $scheme . '://' . $host . '/admin/cliente_info.' . $extension . '?cliente=' . rawurlencode($uuid);
$temp = tempnam(sys_get_temp_dir(), 'mka_client_');
if ($temp === false) { http_response_code(500); exit('Não foi possível preparar o PDF.'); }
$pdf = $temp . '.pdf';
@unlink($temp);
$command = '/usr/bin/wkhtmltopdf --quiet --enable-local-file-access --page-size A4 --margin-top 10mm --margin-right 8mm --margin-bottom 10mm --margin-left 8mm --cookie ' . escapeshellarg(session_name()) . ' ' . escapeshellarg(session_id()) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($pdf) . ' 2>&1';
exec($command, $output, $code);
if ($code !== 0 || !is_file($pdf) || filesize($pdf) < 500) { @unlink($pdf); http_response_code(500); exit('Não foi possível gerar o PDF. Tente novamente ou use o botão Imprimir.'); }
$asciiName = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $client['nome']);
$safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $asciiName ? $asciiName : 'cliente');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="dados_cliente_' . $safeName . '.pdf"');
header('Content-Length: ' . filesize($pdf));
readfile($pdf);
@unlink($pdf);
exit;
