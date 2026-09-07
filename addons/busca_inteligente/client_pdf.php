<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Use o botão Baixar PDF dentro das informações do cliente.'); }
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Usuario']) && empty($_SESSION['MM_Usuario'])) { http_response_code(401); exit('Sessão expirada.'); }
$uuid = isset($_POST['uuid']) ? trim((string) $_POST['uuid']) : '';
$html = isset($_POST['report_html']) ? (string) $_POST['report_html'] : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) { http_response_code(422); exit('Cliente inválido.'); }
if ($html === '' || strlen($html) > 6 * 1024 * 1024) { http_response_code(422); exit('As informações do cliente não puderam ser preparadas.'); }
$uuidSql = mysqli_real_escape_string($link, $uuid);
$result = @mysqli_query($link, "SELECT nome FROM sis_cliente WHERE uuid_cliente='{$uuidSql}' LIMIT 1");
if (!$result || !($client = mysqli_fetch_assoc($result))) { http_response_code(404); exit('Cliente não encontrado.'); }
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST']) : '127.0.0.1';
$base = htmlspecialchars($scheme . '://' . $host . '/admin/', ENT_QUOTES, 'UTF-8');
if (stripos($html, '<base ') === false) $html = preg_replace('/(<head[^>]*>)/i', '$1<base href="' . $base . '">', $html, 1);
$html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
$tempDirectory = __DIR__ . '/tmp';
if (!is_dir($tempDirectory)) @mkdir($tempDirectory, 0770, true);
$htmlFile = tempnam($tempDirectory, 'mka_html_');
$pdfFile = tempnam($tempDirectory, 'mka_pdf_');
if ($htmlFile === false || $pdfFile === false) { http_response_code(500); exit('Não foi possível preparar o PDF.'); }
@unlink($pdfFile); $pdfFile .= '.pdf';
$providerLogo = @file_get_contents('/opt/mk-auth/mkfiles/logo.jpg');
if ($providerLogo !== false) {
    $mime = function_exists('mime_content_type') ? @mime_content_type('/opt/mk-auth/mkfiles/logo.jpg') : 'image/jpeg';
    if (!$mime) $mime = 'image/jpeg';
    $logoData = 'data:' . $mime . ';base64,' . base64_encode($providerLogo);
    $html = preg_replace_callback('#<img\b([^>]*)(?:src=["\'][^"\']*(?:img_nao_disp\.gif|/mkfiles/logo\.jpg)[^"\']*["\'])([^>]*)>#i', function ($match) use ($logoData) { return '<img' . $match[1] . 'src="' . $logoData . '"' . $match[2] . '>'; }, $html);
}
if (@file_put_contents($htmlFile, $html) === false) { @unlink($htmlFile); http_response_code(500); exit('Não foi possível preparar o PDF.'); }
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$timeoutBinary = is_executable('/usr/bin/timeout') ? '/usr/bin/timeout 45s ' : '';
$command = $timeoutBinary . '/usr/bin/wkhtmltopdf --quiet --lowquality --disable-javascript --enable-local-file-access --load-error-handling ignore --page-size A4 --margin-top 10mm --margin-right 9mm --margin-bottom 10mm --margin-left 9mm --zoom 0.88 --user-style-sheet ' . escapeshellarg(__DIR__ . '/client_pdf.css') . ' ' . escapeshellarg('file://' . $htmlFile) . ' ' . escapeshellarg($pdfFile) . ' 2>&1';
exec($command, $output, $code);
@unlink($htmlFile);
if ($code !== 0 || !is_file($pdfFile) || filesize($pdfFile) < 500) { @error_log('MK-AUTH client PDF: ' . implode(' | ', $output)); @unlink($pdfFile); http_response_code(500); exit('Não foi possível gerar o PDF. Tente novamente ou use o botão Imprimir.'); }
$asciiName = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $client['nome']);
$safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $asciiName ? $asciiName : 'cliente');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="dados_cliente_' . $safeName . '.pdf"');
header('Content-Length: ' . filesize($pdfFile));
readfile($pdfFile);
@unlink($pdfFile);
exit;
