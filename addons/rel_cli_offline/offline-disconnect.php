<?php
function off_disconnect_reason($raw) {
    $names = array(1=>'User-Request',2=>'Lost-Carrier',3=>'Lost-Service',4=>'Idle-Timeout',5=>'Session-Timeout',6=>'Admin-Reset',7=>'Admin-Reboot',8=>'Port-Error',9=>'NAS-Error',10=>'NAS-Request',11=>'NAS-Reboot',12=>'Port-Unneeded',13=>'Port-Preempted',14=>'Port-Suspended',15=>'Service-Unavailable',16=>'Callback',17=>'User-Error',18=>'Host-Request');
    $raw = trim((string)$raw);
    $code = ctype_digit($raw) && isset($names[(int)$raw]) ? $names[(int)$raw] : $raw;
    $labels = array('user-request'=>'Encerramento solicitado pelo usuário','lost-carrier'=>'Perda de portadora informada','lost-service'=>'Perda de serviço informada','idle-timeout'=>'Tempo limite por inatividade','session-timeout'=>'Tempo máximo da sessão atingido','admin-reset'=>'Sessão encerrada administrativamente','admin-reboot'=>'Reinicialização administrativa','port-error'=>'Erro de porta informado','nas-error'=>'Erro no concentrador informado','nas-request'=>'Encerramento solicitado pelo concentrador','nas-reboot'=>'Reinicialização não administrativa informada','port-unneeded'=>'Porta dispensada pelo concentrador','port-preempted'=>'Porta alocada para outro uso','port-suspended'=>'Porta suspensa','service-unavailable'=>'Serviço indisponível informado','callback'=>'Encerramento para retorno de chamada','user-error'=>'Erro de entrada do usuário informado','host-request'=>'Encerramento solicitado pelo host');
    $key = strtolower(str_replace(array(' ','_'), '-', $code));
    return array('raw'=>$raw, 'label'=>$raw === '' ? 'Motivo não informado' : ($labels[$key] ?? 'Motivo não reconhecido'), 'signal'=>in_array($key,array('lost-carrier','lost-service','port-error','nas-error','nas-reboot','service-unavailable','user-error'),true));
}
function off_disconnect_query($db, $clients, &$records) {
    $records = array(); $anchors = array();
    foreach ($clients as $client) {
        $login = $client['username']; $value = (string)($client['acctstoptime'] ?? '');
        $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $records[$login] = array('state'=>'unavailable');
        if (!$end || $end->format('Y-m-d H:i:s') !== $value || substr($value,0,4) === '0000') continue;
        $from = $end->modify('-24 hours')->format('Y-m-d H:i:s'); $to = $end->format('Y-m-d H:i:s');
        $records[$login] = array('state'=>'ok','from'=>$from,'to'=>$to,'total'=>0,'signals'=>0,'unknown'=>0,'reasons'=>array());
        $anchors[$login] = "SELECT '".$db->real_escape_string($login)."' AS login, CAST('$from' AS DATETIME) AS began, CAST('$to' AS DATETIME) AS ended";
    }
    if (!$anchors) return '';
    return 'SELECT a.login, r.acctterminatecause AS reason, COUNT(*) AS entries, MAX(r.acctstoptime) AS last_seen FROM ('.implode(' UNION ALL ', $anchors).') a INNER JOIN radacct r ON r.username=a.login AND r.acctstoptime>=a.began AND r.acctstoptime<=a.ended GROUP BY a.login, r.acctterminatecause ORDER BY a.login, entries DESC';
}
function off_disconnect_summaries($db, $clients) {
    $records = array();
    try {
        $query = off_disconnect_query($db, $clients, $records);
        if ($query === '') return $records;
        $result = $db->query($query);
        if (!$result) throw new RuntimeException('Accounting indisponível');
        while ($row = $result->fetch_assoc()) {
            $login = $row['login']; if (!isset($records[$login]) || $records[$login]['state'] !== 'ok') continue;
            $reason = off_disconnect_reason($row['reason']); $count = (int)$row['entries'];
            $reason['count'] = $count; $reason['last_seen'] = $row['last_seen'];
            $records[$login]['total'] += $count;
            if ($reason['signal']) $records[$login]['signals'] += $count;
            if ($reason['raw'] === '') $records[$login]['unknown'] += $count;
            $records[$login]['reasons'][] = $reason;
        }
    } catch (Throwable $error) {
        foreach ($records as &$record) $record = array('state'=>'unavailable'); unset($record);
        error_log('Relatório offline: consulta de encerramentos indisponível.');
    }
    return $records;
}
function off_reconnections($db, $clients) {
    $windows = array(); off_disconnect_query($db, $clients, $windows);
    $anchors = array(); $counts = array();
    foreach ($windows as $login=>$window) {
        $counts[$login] = null;
        if ($window['state'] === 'ok') $anchors[] = "SELECT '".$db->real_escape_string($login)."' AS login, CAST('".$window['from']."' AS DATETIME) AS began, CAST('".$window['to']."' AS DATETIME) AS ended";
    }
    if (!$anchors) return $counts;
    $query = 'SELECT a.login, COUNT(DISTINCT r.acctstarttime) AS reconnections FROM ('.implode(' UNION ALL ', $anchors).') a INNER JOIN radacct r ON r.username=a.login AND r.acctstarttime>=a.began AND r.acctstarttime<=a.ended WHERE EXISTS (SELECT 1 FROM radacct previous WHERE previous.username=r.username AND previous.acctstarttime<r.acctstarttime AND previous.acctstoptime IS NOT NULL AND previous.acctstoptime<=r.acctstarttime) GROUP BY a.login';
    try {
        $result = $db->query($query); if (!$result) return $counts;
        foreach ($windows as $login=>$window) if ($window['state'] === 'ok') $counts[$login] = 0;
        while ($row = $result->fetch_assoc()) $counts[$row['login']] = (int)$row['reconnections'];
    } catch (Throwable $error) { error_log('Relatório offline: reconexões indisponíveis.'); }
    return $counts;
}
function off_disconnect_assessment($record) {
    $answer = array('title'=>'Causa física não determinada','evidence'=>'O histórico disponível não permite atribuir esta queda a energia ou LOS.','next'=>'Verifique na OLT/ONU os alarmes e horários de LOS e dying-gasp, além do uptime e da alimentação do equipamento.','final'=>'Motivo da queda final não localizado.');
    if ($record['state'] !== 'ok' || !$record['total']) return $answer;
    $final = array();
    foreach ($record['reasons'] as $reason) if ($reason['last_seen'] === $record['to']) $final[] = $reason;
    if (count($final) !== 1) {
        if (count($final) > 1) $answer['evidence'] = 'Há mais de um motivo registrado no instante da queda final. Os registros são ambíguos; não é possível escolher uma causa com segurança.';
        return $answer;
    }
    $reason = $final[0]; $code = strtolower(str_replace(array(' ','_'), '-', $reason['raw']));
    $answer['final'] = $reason['raw'] !== '' ? $reason['raw'].' — '.$reason['label'] : 'Motivo não informado no encerramento final.';
    if (in_array($code, array('lost-carrier','lost-service','2','3'), true)) {
        $answer['title'] = 'Indício de perda de conexão/serviço na queda final';
        $answer['evidence'] = 'O motivo da queda final informa perda de portadora ou serviço. Isso é compatível com interrupção da conexão, mas não distingue falta de energia, LOS óptico ou outro problema no caminho.';
    } elseif (in_array($code, array('port-error','nas-error','8','9'), true)) {
        $answer['title'] = 'Erro de porta ou concentrador informado na queda final';
        $answer['evidence'] = 'O RADIUS recebeu um motivo de erro de porta/concentrador no encerramento final. O motivo não identifica, sozinho, uma falha de energia ou de fibra.';
        $answer['next'] = 'Confira os logs e alarmes da porta/concentrador no mesmo horário e compare com outros clientes afetados.';
    } elseif (in_array($code, array('nas-reboot','admin-reboot','7','11'), true)) {
        $answer['title'] = 'Reinicialização do concentrador informada';
        $answer['evidence'] = 'O motivo final indica reinicialização do concentrador. Isso não comprova falta de energia na ONU do cliente.';
        $answer['next'] = 'Confira o uptime, o log de reinicialização e a alimentação do concentrador no horário da queda.';
    } elseif (in_array($code, array('idle-timeout','session-timeout','4','5'), true)) {
        $answer['title'] = 'Encerramento por limite de tempo informado';
        $answer['evidence'] = 'O encerramento final foi registrado como tempo limite de inatividade ou duração de sessão. O registro não aponta energia ou LOS.';
        $answer['next'] = 'Revise os limites de sessão/inatividade e os eventos PPPoE/RADIUS do equipamento.';
    } elseif (in_array($code, array('user-request','nas-request','admin-reset','host-request','1','6','10','18'), true)) {
        $answer['title'] = 'Solicitação de encerramento registrada na queda final';
        $answer['evidence'] = 'A sessão foi encerrada por solicitação registrada pelo RADIUS. Isso não comprova uma ação manual do cliente nem aponta, sozinho, energia ou LOS.';
        $answer['next'] = 'Compare os logs PPPoE do cliente e do concentrador, alterações administrativas, reinícios do CPE e alarmes da ONU.';
    } elseif (in_array($code, array('service-unavailable','15'), true)) {
        $answer['title'] = 'Indisponibilidade de serviço informada na queda final';
        $answer['evidence'] = 'O concentrador informou que o serviço não estava disponível. A origem física da indisponibilidade não está identificada neste registro.';
    }
    return $answer;
}
function off_disconnect_html($record, $clientName = '') {
    if ($record['state'] !== 'ok') return '<small class="off-diagnostic-empty">Registros das 24h indisponíveis</small>';
    $range = date('d/m/Y H:i',strtotime($record['from'])).' até '.date('d/m/Y H:i',strtotime($record['to']));
    if ($record['total'] === 0) return '<small class="off-diagnostic-empty">Nenhum encerramento registrado nas 24h anteriores.<br>'.off_h($range).'</small>';
    $assessment = off_disconnect_assessment($record);
    $reconnections = $record['reconnections'] ?? null;
    $reconnectLabel = $reconnections === null ? 'Reconexões · indisponível' : 'Reconexões · '.(int)$reconnections;
    $reconnectClass = $reconnections !== null && $reconnections > 0 ? ' is-warning' : '';
    $icon = $reconnectClass !== '' ? off_attention_icon() : '';
    $html = '<div class="off-analysis-entry"><button type="button" class="off-reconnect-chip'.$reconnectClass.'" data-off-analysis data-off-analysis-name="'.off_h($clientName).'" title="Reconexões identificadas nas 24h anteriores à queda">'.$icon.off_h($reconnectLabel).'</button>';

    $html .= '<template class="off-analysis-template"><section class="off-assessment"><span>LEITURA AUTOMÁTICA DOS REGISTROS</span><h3>'.off_h($assessment['title']).'</h3><p>'.off_h($assessment['evidence']).'</p><dl><dt>Motivo no instante da queda final</dt><dd>'.off_h($assessment['final']).'</dd><dt>Energia ou LOS óptico?</dt><dd>Não determinado por estes registros. Não há telemetria da OLT/ONU nesta análise.</dd><dt>Próxima verificação sugerida</dt><dd>'.off_h($assessment['next']).'</dd></dl></section><p class="off-analysis-period">De '.off_h($range).'<br>24h até a queda; inclui o encerramento final.</p><p>Reconexões nas 24h: <strong>'.($reconnections === null ? 'Indisponível' : (int)$reconnections).'</strong>. Contamos inícios de sessão distintos dentro do período com um encerramento anterior registrado. Inícios no mesmo segundo são deduplicados; o total depende do histórico disponível.</p><div class="off-analysis-stats"><span class="off-primary-reconnections"><strong>'.($reconnections === null ? '—' : (int)$reconnections).'</strong> reconexões nas 24h</span><span><strong>'.(int)$record['total'].'</strong> encerramentos, incluindo a queda final</span><span><strong>'.(int)$record['unknown'].'</strong> encerramentos sem motivo</span></div>'.($reconnections === 0 ? '<p class="off-count-explanation"><strong>Nenhuma reconexão identificada nas 24h antes da queda.</strong> O encerramento final é a queda que deixou o cliente offline; não é uma reconexão. Um motivo de perda/erro nessa queda também não aumenta a contagem de reconexões.</p>' : '').'<h3>O que aconteceu no período</h3><p>Os motivos abaixo resumem as 24h. A conclusão acima usa o motivo da queda final, não o mais frequente. Vários registros não comprovam a mesma quantidade de quedas físicas; pode haver duplicidade no accounting.</p><ul class="off-analysis-reasons">';
    foreach ($record['reasons'] as $reason) {
        $html .= '<li><strong>'.(int)$reason['count'].'× '.off_h($reason['label']).'</strong><small>'.off_h($reason['raw'] !== '' ? $reason['raw'] : 'Sem motivo no accounting').' · último: '.off_h(date('d/m/Y H:i:s',strtotime($reason['last_seen']))).'</small></li>';
    }
    return $html.'</ul><p class="off-analysis-source">Fonte: histórico de sessões (radacct), atributo Acct-Terminate-Cause do RADIUS. A análise depende dos registros disponíveis e não confirma a causa física.</p></template></div>';
}
