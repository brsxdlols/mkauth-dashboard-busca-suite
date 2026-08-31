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

$routers = array();
$routersOk = 0;
$routersFail = 0;
$query = @mysqli_query($conn, "SELECT nasname, shortname, userapi, senha FROM nas WHERE nasname IS NOT NULL AND nasname <> '' ORDER BY shortname, nasname");
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $ip = trim((string) $row['nasname']);
        $apiUser = trim((string) $row['userapi']) !== '' ? trim((string) $row['userapi']) : 'mkauth';
        $apiPassword = trim((string) $row['senha']) !== '' ? trim((string) $row['senha']) : '123456';
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
        $routers[] = array('router' => $ip, 'name' => trim((string) $row['shortname']), 'ok' => $connected, 'reason' => $reason);
    }
}

$queryOk = $query !== false;
echo json_encode(array(
    'ok' => $queryOk && $routersFail === 0,
    'executed' => $queryOk,
    'message' => !$queryOk ? 'Não foi possível consultar os ramais cadastrados.' : ($routersFail === 0 ? 'Todas as conexões de ramais estão corretas.' : $routersFail . ' conexão(ões) precisam de correção.'),
    'generated_at' => date('Y-m-d H:i:s'),
    'stats' => array('routers_ok' => $routersOk, 'routers_fail' => $routersFail),
    'routers' => $routers
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
