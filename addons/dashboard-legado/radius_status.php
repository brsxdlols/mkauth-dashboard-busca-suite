<?php
header('Content-Type: application/json; charset=utf-8');
$file = '/var/lib/mkauth_radius_ppp_reconcile/status.json';
if (!is_file($file)) {
    echo json_encode(array('ok' => true, 'message' => 'Sem status gerado ainda', 'failed_routers' => array()));
    exit;
}
$raw = file_get_contents($file);
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(array('ok' => false, 'message' => 'Status invalido', 'failed_routers' => array()));
    exit;
}
$data['ok'] = empty($data['failed_routers']);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
