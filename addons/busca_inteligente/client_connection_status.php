<?php
ob_start(); include('config.php'); ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
if (!isset($link) && isset($conn)) $link = $conn;
$login = isset($_GET['login']) ? trim((string) $_GET['login']) : '';
if (!isset($link) || !$link || $login === '' || strlen($login) > 64) { http_response_code(422); exit(json_encode(array('online' => false))); }
$stmt = mysqli_prepare($link, "SELECT framedipaddress,nasipaddress,acctstarttime FROM radacct WHERE LOWER(TRIM(username))=LOWER(TRIM(?)) AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1");
$session = null;
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $session = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }
if (!$session) exit(json_encode(array('online' => false)));
$seconds = max(0, time() - strtotime($session['acctstarttime'])); $days = floor($seconds / 86400); $hours = floor(($seconds % 86400) / 3600); $minutes = floor(($seconds % 3600) / 60);
echo json_encode(array('online' => true, 'ip' => $session['framedipaddress'], 'nas' => $session['nasipaddress'], 'uptime' => ($days ? $days . 'd ' : '') . $hours . 'h ' . $minutes . 'min'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
