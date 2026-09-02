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
$tempDirectory = __DIR__ . '/tmp';
if (!is_dir($tempDirectory)) @mkdir($tempDirectory, 0770, true);
$temp = tempnam($tempDirectory, 'mka_client_');
if ($temp === false) { http_response_code(500); exit('Não foi possível preparar o PDF.'); }
$pdf = $temp . '.pdf';
@unlink($temp);
$cookieArguments = '';
foreach ($_COOKIE as $cookieName => $cookieValue) {
    if (!preg_match('/^[A-Za-z0-9_.-]+$/', (string) $cookieName) || !is_scalar($cookieValue)) continue;
    $cookieArguments .= ' --cookie ' . escapeshellarg((string) $cookieName) . ' ' . escapeshellarg((string) $cookieValue);
}
if (!isset($_COOKIE[session_name()]) && session_id() !== '') {
    $cookieArguments .= ' --cookie ' . escapeshellarg(session_name()) . ' ' . escapeshellarg(session_id());
}
// The renderer requests an authenticated MK-AUTH page using the same session.
// Release PHP's session file lock first, otherwise both requests wait forever.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$timeoutBinary = is_executable('/usr/bin/timeout') ? '/usr/bin/timeout 45s ' : '';
$styleSheet = __DIR__ . '/client_pdf.css';
$runtimeStyle = tempnam($tempDirectory, 'mka_css_');
if ($runtimeStyle !== false) {
    $css = @file_get_contents($styleSheet);
    $providerLogo = @file_get_contents('/opt/mk-auth/mkfiles/logo.jpg');
    if ($css !== false && $providerLogo !== false) {
        $css .= "\nimg[src*='img_nao_disp.gif']{content:url(data:image/jpeg;base64," . base64_encode($providerLogo) . ")!important;width:74px!important;height:74px!important;background:#fff!important;}\n";
        if (@file_put_contents($runtimeStyle, $css) !== false) $styleSheet = $runtimeStyle;
    }
}
$command = $timeoutBinary . '/usr/bin/wkhtmltopdf --quiet --lowquality --disable-javascript --enable-local-file-access --load-error-handling ignore --page-size A4 --margin-top 10mm --margin-right 9mm --margin-bottom 10mm --margin-left 9mm --zoom 0.88 --user-style-sheet ' . escapeshellarg($styleSheet) . $cookieArguments . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($pdf) . ' 2>&1';
exec($command, $output, $code);
if ($runtimeStyle !== false) @unlink($runtimeStyle);
if ($code !== 0 || !is_file($pdf) || filesize($pdf) < 500) { @error_log('MK-AUTH client PDF: ' . implode(' | ', $output)); @unlink($pdf); http_response_code(500); exit('Não foi possível gerar o PDF. Tente novamente ou use o botão Imprimir.'); }
$asciiName = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $client['nome']);
$safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $asciiName ? $asciiName : 'cliente');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="dados_cliente_' . $safeName . '.pdf"');
header('Content-Length: ' . filesize($pdf));
readfile($pdf);
@unlink($pdf);
exit;
