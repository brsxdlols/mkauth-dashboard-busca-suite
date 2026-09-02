<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Usuario']) && empty($_SESSION['MM_Usuario'])) { http_response_code(401); exit('Sessão expirada.'); }
$uuid = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) { http_response_code(422); exit('Cliente inválido.'); }
$uuidSql = mysqli_real_escape_string($link, $uuid);
$clientResult = @mysqli_query($link, "SELECT login FROM sis_cliente WHERE uuid_cliente='{$uuidSql}' LIMIT 1");
if (!$clientResult || !($client = mysqli_fetch_assoc($clientResult))) { http_response_code(404); exit('Cliente não encontrado.'); }

$loginSql = mysqli_real_escape_string($link, $client['login']);
$connections = array();
$connectionResult = @mysqli_query($link, "SELECT framedipaddress, acctstarttime, acctstoptime, callingstationid FROM radacct WHERE username='{$loginSql}' ORDER BY acctstarttime DESC LIMIT 5");
if ($connectionResult) while ($row = mysqli_fetch_assoc($connectionResult)) $connections[] = $row;

$extension = file_exists(__DIR__ . '/../../cliente_info.hhvm') ? 'hhvm' : 'php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST']) : '127.0.0.1';
$nativeUrl = $scheme . '://' . $host . '/admin/cliente_info.' . $extension . '?cliente=' . rawurlencode($uuid);
$cookieParts = array();
foreach ($_COOKIE as $name => $value) if (preg_match('/^[A-Za-z0-9_.-]+$/', (string) $name) && is_scalar($value)) $cookieParts[] = $name . '=' . $value;
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

$html = '';
if (function_exists('curl_init')) {
    $curl = curl_init($nativeUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: ' . implode('; ', $cookieParts)));
    $html = (string) curl_exec($curl);
    curl_close($curl);
}
if ($html === '') { http_response_code(502); exit('Não foi possível carregar os dados do cliente.'); }

function mka_report_date($value) {
    if (!$value || $value === '0000-00-00 00:00:00') return '';
    $time = strtotime($value);
    return $time ? date('d/m/Y H:i:s', $time) : $value;
}
function mka_report_escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

$section = '<section class="mka-last-connections"><div class="mka-connections-title">Últimas 5 conexões do cliente</div>';
if ($connections) {
    $section .= '<table><thead><tr><th>Início</th><th>Encerramento</th><th>IP</th><th>MAC</th></tr></thead><tbody>';
    foreach ($connections as $connection) {
        $ended = mka_report_date($connection['acctstoptime']);
        $section .= '<tr><td>' . mka_report_escape(mka_report_date($connection['acctstarttime'])) . '</td><td>' . ($ended !== '' ? mka_report_escape($ended) : '<strong class="mka-online-now">Online agora</strong>') . '</td><td>' . mka_report_escape($connection['framedipaddress'] ?: '-') . '</td><td>' . mka_report_escape($connection['callingstationid'] ?: '-') . '</td></tr>';
    }
    $section .= '</tbody></table>';
} else {
    $section .= '<p class="mka-no-connections">Nenhuma conexão encontrada para este cliente.</p>';
}
$section .= '</section><style>.mka-last-connections{margin-top:14px!important;page-break-inside:avoid}.mka-connections-title{padding:8px 10px;background:#08c7ad;color:#fff;font-weight:700}.mka-last-connections table{width:100%;border-collapse:collapse}.mka-last-connections th,.mka-last-connections td{padding:6px 8px;border:1px solid #d7dce2;text-align:left}.mka-last-connections th{background:#eef3f7}.mka-online-now{color:#07834f}.mka-no-connections{padding:10px;border:1px solid #d7dce2;margin:0}</style>';

$html = str_replace('img_nao_disp.gif', 'logo.jpg', $html);
if (stripos($html, '<head') !== false) $html = preg_replace('/(<head[^>]*>)/i', '$1<base href="/admin/">', $html, 1);
if (stripos($html, '</body>') !== false) $html = preg_replace('/<\/body>/i', $section . '</body>', $html, 1); else $html .= $section;
header('Content-Type: text/html; charset=utf-8');
echo $html;
