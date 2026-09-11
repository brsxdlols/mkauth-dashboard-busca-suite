<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function mka_connections_reply($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
$uuid = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';
if (!preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) mka_connections_reply(array('ok' => false, 'message' => 'Cliente inválido.'), 422);
$uuidSql = mysqli_real_escape_string($link, $uuid);
$clientResult = @mysqli_query($link, "SELECT login FROM sis_cliente WHERE uuid_cliente='{$uuidSql}' LIMIT 1");
if (!$clientResult || !($client = mysqli_fetch_assoc($clientResult))) mka_connections_reply(array('ok' => false, 'message' => 'Cliente não encontrado.'), 404);
$loginSql = mysqli_real_escape_string($link, $client['login']);
$items = array();
$result = @mysqli_query($link, "SELECT framedipaddress, acctstarttime, acctstoptime, callingstationid FROM radacct WHERE username='{$loginSql}' ORDER BY acctstarttime DESC LIMIT 10");
if ($result) while ($row = mysqli_fetch_assoc($result)) {
    $start = strtotime($row['acctstarttime']);
    $stop = $row['acctstoptime'] && $row['acctstoptime'] !== '0000-00-00 00:00:00' ? strtotime($row['acctstoptime']) : false;
    $items[] = array(
        'start' => $start ? date('d/m/Y H:i:s', $start) : (string) $row['acctstarttime'],
        'end' => $stop ? date('d/m/Y H:i:s', $stop) : 'Online agora',
        'online' => !$stop,
        'ip' => $row['framedipaddress'] ? (string) $row['framedipaddress'] : '-',
        'mac' => $row['callingstationid'] ? (string) $row['callingstationid'] : '-'
    );
}
mka_connections_reply(array('ok' => true, 'items' => $items));
