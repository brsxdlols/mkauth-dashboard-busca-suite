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

$output = array();
$exitCode = 1;
@exec('sudo -n /usr/local/sbin/mkauth-radius-test 2>&1', $output, $exitCode);
$stateFile = '/var/lib/mkauth_radius_ppp_reconcile/status.json';
$state = array();
if (@is_readable($stateFile)) {
    $decoded = json_decode((string) @file_get_contents($stateFile), true);
    if (is_array($decoded)) $state = $decoded;
}

$failedByIp = array();
foreach ((array) ($state['failed_routers'] ?? array()) as $failed) {
    $failedByIp[(string) ($failed['router'] ?? '')] = $failed;
}

$routers = array();
$query = @mysqli_query($conn, "SELECT nasname, shortname FROM nas WHERE nasname IS NOT NULL AND nasname <> '' ORDER BY shortname, nasname");
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $ip = trim((string) $row['nasname']);
        $failed = isset($failedByIp[$ip]) ? $failedByIp[$ip] : null;
        $routers[] = array('router' => $ip, 'name' => trim((string) $row['shortname']), 'ok' => $failed === null,
            'reason' => $failed === null ? 'Conexão com a API realizada com sucesso' : (string) ($failed['reason'] ?? 'Falha de integração com o Radius'));
    }
}

$failedCount = count($failedByIp);
$runOk = $exitCode === 0 && !empty($state);
echo json_encode(array(
    'ok' => $runOk && $failedCount === 0,
    'executed' => $runOk,
    'message' => !$runOk ? ('Não foi possível executar o teste. ' . implode(' ', $output)) : ($failedCount === 0 ? 'Todas as conexões de ramais estão corretas.' : $failedCount . ' conexão(ões) precisam de correção.'),
    'generated_at' => (string) ($state['generated_at'] ?? ''), 'stats' => (array) ($state['stats'] ?? array()), 'routers' => $routers
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
