<?php
ob_start();
require_once __DIR__ . '/config.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'Método não permitido.'));
    exit;
}

require_once __DIR__ . '/../busca_inteligente/api/routeros_api.class.php';

// Alguns MK-AUTH encerram ou substituem a conexão durante os includes do painel.
// O diagnóstico usa uma conexão própria para funcionar da mesma forma em PHP 7 e PHP 8.
if (!isset($conn) || !($conn instanceof mysqli) || !@mysqli_ping($conn)) {
    $conn = @mysqli_connect('127.0.0.1', 'root', 'vertrigo', 'mkradius');
}

$routers = array();
$routersOk = 0;
$routersFail = 0;
$query = $conn ? @mysqli_query($conn, "SELECT * FROM nas WHERE nasname IS NOT NULL AND nasname <> '' ORDER BY nasname") : false;
$queryError = (!$query && $conn) ? trim((string) mysqli_error($conn)) : '';
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $ip = trim((string) $row['nasname']);
        $routerName = '';
        foreach (array('shortname', 'descricao', 'nome') as $nameField) {
            if (isset($row[$nameField]) && trim((string) $row[$nameField]) !== '') {
                $routerName = trim((string) $row[$nameField]);
                break;
            }
        }
        if ($routerName === '') $routerName = $ip;

        $apiUser = 'mkauth';
        foreach (array('userapi', 'usuario', 'user', 'login') as $userField) {
            if (isset($row[$userField]) && trim((string) $row[$userField]) !== '') {
                $apiUser = trim((string) $row[$userField]);
                break;
            }
        }
        $apiPassword = '123456';
        foreach (array('senha', 'password', 'pass', 'secret') as $passwordField) {
            if (isset($row[$passwordField]) && trim((string) $row[$passwordField]) !== '') {
                $apiPassword = trim((string) $row[$passwordField]);
                break;
            }
        }
        $api = new RouterosAPI();
        $api->debug = false;
        $api->timeout = 3;
        $api->attempts = 1;
        $api->delay = 0;
        $connected = @$api->connect($ip, $apiUser, $apiPassword);
        if ($connected) {
            $api->disconnect();
            $routersOk++;
            $reason = 'Conexão com a API realizada com sucesso';
        } else {
            $routersFail++;
            $pingCode = null;
            if (function_exists('exec')) {
                $pingOutput = array();
                $pingExit = 1;
                @exec('ping -c 1 -W 1 ' . escapeshellarg($ip) . ' 2>&1', $pingOutput, $pingExit);
                $pingCode = $pingExit;
            }
            $socketError = 0;
            $socketMessage = '';
            $socket = @fsockopen($ip, 8728, $socketError, $socketMessage, 2);
            $portOpen = is_resource($socket);
            if ($portOpen) fclose($socket);
            if ($pingCode !== null && $pingCode !== 0) {
                $reason = 'Sem ping ou sem rota até o IP';
            } elseif (!$portOpen) {
                $reason = 'Porta 8728 fechada ou indisponível';
            } else {
                $reason = 'Usuário ou senha da API inválidos';
            }
        }
        $routers[] = array('router' => $ip, 'name' => $routerName, 'ok' => $connected, 'reason' => $reason);
    }
}

$queryOk = $query !== false;
echo json_encode(array(
    'ok' => $queryOk && $routersFail === 0,
    'executed' => $queryOk,
    'message' => !$queryOk
        ? 'Não foi possível consultar os ramais cadastrados.' . ($queryError !== '' ? ' Detalhe: ' . $queryError : ' Verifique a conexão com o banco mkradius.')
        : ($routersFail === 0 ? 'Todas as conexões de ramais estão corretas.' : $routersFail . ' conexão(ões) precisam de correção.'),
    'generated_at' => date('Y-m-d H:i:s'),
    'stats' => array('routers_ok' => $routersOk, 'routers_fail' => $routersFail),
    'routers' => $routers
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
