<?php
include('config.php');

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('json_response_dashboard')) {
    function json_response_dashboard($payload)
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$cfg_popup = 'n';
$query_cfg_popup = mysqli_query($conn, "SELECT popup_clientes_sessao FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
if ($query_cfg_popup && ($row_cfg_popup = mysqli_fetch_assoc($query_cfg_popup))) {
    $cfg_popup = isset($row_cfg_popup['popup_clientes_sessao']) ? $row_cfg_popup['popup_clientes_sessao'] : 'n';
}

if ($cfg_popup !== 's') {
    json_response_dashboard(array(
        'enabled' => false,
        'events' => array(),
    ));
}

$events = array();
$query_events = mysqli_query($conn, "
    SELECT evento, username, nome, concentrador, data_evento, event_id
    FROM (
        SELECT 
            'login' AS evento,
            r.username,
            COALESCE(NULLIF(c.nome, ''), r.username) AS nome,
            COALESCE(NULLIF(r.nasipaddress, ''), '-') AS concentrador,
            r.acctstarttime AS data_evento,
            CONCAT('login-', r.radacctid) AS event_id
        FROM radacct r
        LEFT JOIN sis_cliente c ON c.login = r.username
        WHERE r.acctstarttime IS NOT NULL
          AND r.acctstarttime >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
          AND r.username IS NOT NULL
          AND r.username <> ''

        UNION ALL

        SELECT 
            'logout' AS evento,
            r.username,
            COALESCE(NULLIF(c.nome, ''), r.username) AS nome,
            COALESCE(NULLIF(r.nasipaddress, ''), '-') AS concentrador,
            r.acctstoptime AS data_evento,
            CONCAT('logout-', r.radacctid) AS event_id
        FROM radacct r
        LEFT JOIN sis_cliente c ON c.login = r.username
        WHERE r.acctstoptime IS NOT NULL
          AND r.acctstoptime >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
          AND r.username IS NOT NULL
          AND r.username <> ''
    ) AS eventos_recent
    ORDER BY data_evento DESC
    LIMIT 8
");

if ($query_events) {
    while ($row_event = mysqli_fetch_assoc($query_events)) {
        $events[] = array(
            'id' => (string) $row_event['event_id'],
            'type' => (string) $row_event['evento'],
            'name' => trim((string) $row_event['nome']),
            'login' => trim((string) $row_event['username']),
            'concentrator' => trim((string) $row_event['concentrador']),
            'datetime' => trim((string) $row_event['data_evento']),
            'formatted_time' => !empty($row_event['data_evento']) ? date('d/m H:i:s', strtotime($row_event['data_evento'])) : '',
        );
    }
}

json_response_dashboard(array(
    'enabled' => true,
    'events' => $events,
));
