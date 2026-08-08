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
$cfg_id = 0;
$query_cfg_popup = mysqli_query($conn, "SELECT id, popup_clientes_sessao FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
if ($query_cfg_popup && ($row_cfg_popup = mysqli_fetch_assoc($query_cfg_popup))) {
    $cfg_id = isset($row_cfg_popup['id']) ? (int) $row_cfg_popup['id'] : 0;
    $cfg_popup = isset($row_cfg_popup['popup_clientes_sessao']) ? $row_cfg_popup['popup_clientes_sessao'] : 'n';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

    if ($action === 'disable_notifications') {
        if ($cfg_id > 0) {
            mysqli_query($conn, "UPDATE dashboard_am_sis_cfg SET popup_clientes_sessao = 'n' WHERE id = {$cfg_id} LIMIT 1");
        }

        json_response_dashboard(array(
            'success' => true,
            'enabled' => false,
        ));
    }

    json_response_dashboard(array(
        'success' => false,
        'enabled' => ($cfg_popup === 's'),
    ));
}

if ($cfg_popup !== 's') {
    json_response_dashboard(array(
        'enabled' => false,
        'events' => array(),
    ));
}

$events = array();
$query_events = mysqli_query($conn, "
    SELECT evento, username, nome, concentrador, data_evento, event_id, bloqueado, cli_ativado
    FROM (
        SELECT 
            'login' AS evento,
            r.username,
            COALESCE(NULLIF(c.nome, ''), r.username) AS nome,
            COALESCE(NULLIF(r.nasipaddress, ''), '-') AS concentrador,
            r.acctstarttime AS data_evento,
            CONCAT('login-', r.radacctid) AS event_id,
            COALESCE(NULLIF(c.bloqueado, ''), 'nao') AS bloqueado,
            COALESCE(NULLIF(c.cli_ativado, ''), 's') AS cli_ativado
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
            CONCAT('logout-', r.radacctid) AS event_id,
            COALESCE(NULLIF(c.bloqueado, ''), 'nao') AS bloqueado,
            COALESCE(NULLIF(c.cli_ativado, ''), 's') AS cli_ativado
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
        $bloqueado = isset($row_event['bloqueado']) ? trim((string) $row_event['bloqueado']) : 'nao';
        $cli_ativado = isset($row_event['cli_ativado']) ? trim((string) $row_event['cli_ativado']) : 's';
        $contract_status = 'active';
        $contract_label = 'Contrato ativo';
        $contract_icon = 'bi bi-shield-check';

        if ($bloqueado === 'sim') {
            $contract_status = 'blocked';
            $contract_label = 'Contrato bloqueado';
            $contract_icon = 'bi bi-lock-fill';
        } elseif ($cli_ativado !== 's') {
            $contract_status = 'inactive';
            $contract_label = 'Contrato inativo';
            $contract_icon = 'bi bi-pause-circle';
        }

        $events[] = array(
            'id' => (string) $row_event['event_id'],
            'type' => (string) $row_event['evento'],
            'name' => trim((string) $row_event['nome']),
            'login' => trim((string) $row_event['username']),
            'concentrator' => trim((string) $row_event['concentrador']),
            'datetime' => trim((string) $row_event['data_evento']),
            'formatted_time' => !empty($row_event['data_evento']) ? date('d/m H:i:s', strtotime($row_event['data_evento'])) : '',
            'contract_status' => $contract_status,
            'contract_label' => $contract_label,
            'contract_icon' => $contract_icon,
        );
    }
}

json_response_dashboard(array(
    'enabled' => true,
    'events' => $events,
));
