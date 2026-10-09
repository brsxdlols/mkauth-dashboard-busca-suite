<?php
require __DIR__.'/standalone_access.php';
require_once __DIR__.'/radius_lib.php';
session_write_close();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$filter=radius_normalize_filter($_GET['filtro'] ?? 'todos','todos');
$lines=radius_normalize_lines($_GET['linhas'] ?? 100,100);
echo json_encode(radius_read_logs($filter,$lines),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
