<?php
function off_ticket_summaries($db, $clients) {
    $records = array();
    $windows = array();
    foreach ($clients as $client) {
        $records[$client['username']] = array('state'=>'unavailable');
        $value = (string)($client['acctstoptime'] ?? '');
        $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        if ($end && $end->format('Y-m-d H:i:s') === $value && substr($value,0,4) !== '0000') $windows[$client['username']] = array('from'=>$end->modify('-30 days'), 'to'=>$end);
    }
    if (!$records) return $records;
    $operator = (string)(!empty($_SESSION['MKA_Usuario']) ? $_SESSION['MKA_Usuario'] : ($_SESSION['MM_Usuario'] ?? ''));
    if ($operator === '') return $records;
    try {
        $shared = __DIR__.'/../shared/';
        if (!is_file($shared.'installations.php') || !is_file($shared.'support_attendance.php')) return $records;
        require_once $shared.'installations.php';
        require_once $shared.'support_attendance.php';
        if (!mka_installation_permission($db, $operator, 'perm_chamados')) {
            foreach ($records as &$record) $record = array('state'=>'denied'); unset($record);
            return $records;
        }
        $scope = mka_attendance_scope($db, $operator);
        $quoted = array(); foreach (array_keys($records) as $login) $quoted[] = "'".$db->real_escape_string($login)."'";
        $in = implode(',', $quoted);
        $allowed = $db->query("SELECT c.login FROM sis_cliente c WHERE c.login IN ($in) AND ($scope)");
        if (!$allowed) return $records;
        foreach ($records as &$record) $record = array('state'=>'denied'); unset($record);
        while ($client = $allowed->fetch_assoc()) {
            $login = $client['login'];
            $records[$login] = isset($windows[$login]) ? array('state'=>'ok','recent'=>0,'open'=>0,'latest'=>array(),'from'=>$windows[$login]['from']->format('Y-m-d H:i:s'),'to'=>$windows[$login]['to']->format('Y-m-d H:i:s')) : array('state'=>'unavailable');
        }
        $result = $db->query("SELECT s.login, s.chamado, s.assunto, s.status, CAST(s.abertura AS DATETIME) AS opened FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE s.login IN ($in) AND ($scope) ORDER BY CAST(s.abertura AS DATETIME) DESC, s.id DESC");
        if (!$result) { foreach ($records as &$record) if ($record['state'] === 'ok') $record = array('state'=>'unavailable'); unset($record); return $records; }
        while ($ticket = $result->fetch_assoc()) {
            $login = $ticket['login'];
            if (!isset($records[$login]) || $records[$login]['state'] !== 'ok') continue;
            $opened = strtotime((string)$ticket['opened']);
            if ($opened === false || $opened < $windows[$login]['from']->getTimestamp() || $opened > $windows[$login]['to']->getTimestamp()) continue;
            $records[$login]['recent']++;
            if (strtolower((string)$ticket['status']) === 'aberto') $records[$login]['open']++;
            if (count($records[$login]['latest']) < 3) $records[$login]['latest'][] = $ticket;
        }
    } catch (Throwable $error) {
        error_log('Relatório offline: consulta de chamados indisponível.');
        foreach ($records as &$record) if ($record['state'] !== 'denied') $record = array('state'=>'unavailable'); unset($record);
    }
    return $records;
}
function off_ticket_html($record, $login, $extension) {
    if ($record['state'] === 'denied') return '<span class="off-history-empty">Sem acesso ao histórico</span>';
    if ($record['state'] !== 'ok') return '<span class="off-history-empty">Histórico indisponível</span>';
    $range = 'De '.date('d/m/Y H:i', strtotime($record['from'])).' até '.date('d/m/Y H:i', strtotime($record['to']));
    $label = (int)$record['recent'].' chamados nos 30 dias antes da desconexão';
    if (!$record['latest']) return '<div class="off-ticket-history">'.off_h($label).'<br><small>'.off_h($range).'</small></div>';
    if ($record['recent'] >= 3) $label = 'Recorrência: '.$label;
    $html = '<details class="off-ticket-history"><summary><span class="off-warning-sign">'.off_attention_icon().' Atenção: há chamados</span><span class="off-ticket-label">'.off_h($label).'</span>'.'<small>'.off_h($range).'</small><small>'.(int)$record['open'].' desses chamados abertos atualmente · ver até 3 do período</small></summary><ul>';
    foreach ($record['latest'] as $ticket) {
        $url = '../../suporte_info'.$extension.'?'.http_build_query(array('login'=>$login,'chamado'=>$ticket['chamado']));
        $stamp = strtotime((string)$ticket['opened']); $date = $stamp !== false ? date('d/m/Y H:i', $stamp) : 'Data não informada';
        $html .= '<li><a target="_blank" rel="noopener" href="'.off_h($url).'">#'.off_h($ticket['chamado']).' · '.off_h($ticket['assunto']).'</a><small>'.off_h($date).' · Situação atual: '.off_h($ticket['status']).'</small></li>';
    }
    return $html.'</ul></details>';
}

function off_ticket_compact_html($record, $login, $extension, $name) {
    if ($record['state'] === 'denied') return '<span class="off-client-note">Histórico restrito</span>';
    if ($record['state'] !== 'ok') return '<span class="off-client-note">Histórico indisponível</span>';
    if (!$record['recent']) return '<span class="off-client-note" title="Nenhum chamado nos 30 dias anteriores à desconexão">Sem chamados no período</span>';
    $count = (int)$record['recent'];
    return '<div class="off-ticket-compact"><button type="button" class="off-ticket-chip" data-off-ticket-modal data-off-ticket-name="'.off_h($name).'" title="Ver chamados dos 30 dias anteriores à desconexão">'.off_attention_icon().''.$count.' '.($count === 1 ? 'chamado' : 'chamados').'</button><template>'.off_ticket_html($record,$login,$extension).'</template></div>';
}
