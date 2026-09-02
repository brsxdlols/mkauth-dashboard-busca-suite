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
        @unlink(sys_get_temp_dir() . '/mkauth_dashboard_session_events.json');

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

$events_cache_file = sys_get_temp_dir() . '/mkauth_dashboard_session_events.json';
if (@is_file($events_cache_file) && @filemtime($events_cache_file) >= (time() - 8)) {
    $cached_events = @file_get_contents($events_cache_file);
    if (is_string($cached_events) && $cached_events !== '') {
        echo $cached_events;
        exit;
    }
}

// Eventos automáticos de bloqueio/desbloqueio registrados pelo MK-BOT.
// Além do motivo, informa se o cliente estava conectado e foi derrubado.
$query_client_state_events = mysqli_query($conn, "
    SELECT id, registro, data
    FROM sis_logs
    WHERE login = 'mk-bot'
      AND (registro LIKE '%bloqueio do cliente%' OR registro LIKE '%desbloqueio do cliente%')
    ORDER BY id DESC
    LIMIT 12
");

if ($query_client_state_events) {
    while ($state_event = mysqli_fetch_assoc($query_client_state_events)) {
        $event_time = DateTime::createFromFormat('d/m/Y H:i:s', trim((string) $state_event['data']));
        if (!$event_time || $event_time->getTimestamp() < (time() - 600)) {
            continue;
        }

        $record = trim((string) $state_event['registro']);
        if (!preg_match('/cliente(?:\s+em\s+observacao)?\s+([a-z0-9_.-]+)/i', $record, $login_match)) {
            continue;
        }

        $client_login = trim($login_match[1]);
        if ($client_login === '' || strtolower($client_login) === 'em') {
            continue;
        }

        $safe_login = mysqli_real_escape_string($conn, $client_login);
        $client_name = $client_login;
        if ($client_query = mysqli_query($conn, "SELECT nome, login FROM sis_cliente WHERE LOWER(login) = LOWER('$safe_login') LIMIT 1")) {
            if ($client_row = mysqli_fetch_assoc($client_query)) {
                $client_name = trim((string) $client_row['nome']) !== '' ? trim((string) $client_row['nome']) : $client_login;
                $client_login = trim((string) $client_row['login']);
                $safe_login = mysqli_real_escape_string($conn, $client_login);
            }
        }

        $mysql_event_time = $event_time->format('Y-m-d H:i:s');
        $is_online = false;
        $was_disconnected = false;
        $connection_query = mysqli_query($conn, "
            SELECT
                EXISTS(SELECT 1 FROM radacct WHERE username = '$safe_login' AND acctstoptime IS NULL LIMIT 1) AS is_online,
                EXISTS(SELECT 1 FROM radacct WHERE username = '$safe_login' AND acctstoptime BETWEEN DATE_SUB('$mysql_event_time', INTERVAL 15 SECOND) AND DATE_ADD('$mysql_event_time', INTERVAL 2 MINUTE) LIMIT 1) AS was_disconnected
        ");
        if ($connection_query && ($connection_row = mysqli_fetch_assoc($connection_query))) {
            $is_online = (int) $connection_row['is_online'] === 1;
            $was_disconnected = (int) $connection_row['was_disconnected'] === 1;
        }

        $is_unlock = stripos($record, 'desbloqueio') !== false;
        $is_trust = stripos($record, 'observacao') !== false;
        $label = $is_unlock ? ($is_trust ? 'Desbloqueio de confiança' : 'Desbloqueio por pagamento') : 'Cliente bloqueado';
        $icon = $is_unlock ? 'bi bi-unlock-fill' : 'bi bi-lock-fill';
        $connection_state = 'offline';
        $connection_label = 'Cliente já estava offline';

        if ($is_online) {
            $connection_state = 'online';
            $connection_label = $is_unlock ? 'Cliente conectado' : 'Estava online — desconexão em andamento';
        } elseif ($was_disconnected) {
            $connection_state = 'disconnected';
            $connection_label = 'Estava online — conexão derrubada';
        }

        // Marcadores exclusivos para testes visuais. Eles apenas simulam o
        // estado exibido no aviso e não alteram cliente, Radius ou conexão.
        if (stripos($record, 'teste_status_online') !== false) {
            $connection_state = 'online';
            $connection_label = 'Cliente conectado';
        } elseif (stripos($record, 'teste_status_desconectado') !== false) {
            $connection_state = 'disconnected';
            $connection_label = 'Estava online — conexão derrubada';
        } elseif (stripos($record, 'teste_status_offline') !== false) {
            $connection_state = 'offline';
            $connection_label = 'Cliente já estava offline';
        }

        $events[] = array(
            'id' => 'client-state-' . (int) $state_event['id'],
            'type' => 'client-state',
            'name' => $client_name,
            'login' => $client_login,
            'concentrator' => '',
            'datetime' => $mysql_event_time,
            'formatted_time' => $event_time->format('d/m H:i:s'),
            'label' => $label,
            'action_style' => $is_trust ? 'trust' : ($is_unlock ? 'unlocked' : 'blocked'),
            'icon' => $icon,
            'description' => $is_trust ? 'Liberação temporária registrada pelo sistema' : ($is_unlock ? 'Pagamento identificado pelo sistema' : 'Bloqueio financeiro aplicado'),
            'connection_state' => $connection_state,
            'connection_label' => $connection_label,
            'show_contract' => false,
        );
    }
}

usort($events, function ($a, $b) {
    return strcmp((string) $b['datetime'], (string) $a['datetime']);
});
$events = array_slice($events, 0, 12);

$events_response = array(
    'enabled' => true,
    'events' => $events,
);
$events_json = json_encode($events_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (is_string($events_json)) {
    $cache_tmp = $events_cache_file . '.' . getmypid() . '.tmp';
    if (@file_put_contents($cache_tmp, $events_json, LOCK_EX) !== false) {
        @rename($cache_tmp, $events_cache_file);
    }
    echo $events_json;
    exit;
}
json_response_dashboard($events_response);
