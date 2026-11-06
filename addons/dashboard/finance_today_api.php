<?php
require __DIR__.'/../shared/notification_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
require_once __DIR__.'/finance_period.php';
try {
    $period = isset($_GET['period']) ? (string)$_GET['period'] : 'day';
    if (!in_array($period, array('day','week'), true)) { http_response_code(422); echo json_encode(array('ok'=>false)); exit; }
    $dueAllowed = permissao('perm_relFin') || permissao('perm_relFat');
    $cashAllowed = permissao('perm_caixa');
    $balanceAllowed = $cashAllowed && permissao('perm_saldoCaixa');
    $user = (string)(!empty($_SESSION['MKA_Usuario']) ? $_SESSION['MKA_Usuario'] : ($_SESSION['MM_Usuario'] ?? ''));
    $stmt = $conn->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');
    $stmt->bind_param('s', $user); $stmt->execute();
    $access = $stmt->get_result()->fetch_assoc();
    if (!$access || (!$dueAllowed && !$cashAllowed)) {
        http_response_code(403); echo json_encode(array('ok'=>false)); exit;
    }
    if (!mka_finance_summary_enabled($conn)) { http_response_code(403); echo json_encode(array('ok'=>false)); exit; }
    session_write_close();
    $groups = array_map('trim', explode(',', (string)$access['cli_grupos']));
    $full = trim((string)$access['cli_grupos']) === '' || in_array('full_clientes', $groups, true);
    $scope = '';
    if (!$full) {
        $quoted = array();
        foreach ($groups as $group) if ($group !== '' && $group !== 'ped_fil') $quoted[]="'".$conn->real_escape_string($group)."'";
        $scope = $quoted ? ' AND c.grupo IN ('.implode(',', $quoted).')' : ' AND 1=0';
    }
    // Cash entries are not linked to client groups: never expose the general cash to a restricted group.
    $cashAllowed = $cashAllowed && $full;
    $clock = $conn->query('SELECT CURRENT_DATE day, NOW() updated_at')->fetch_assoc();
    $range = mka_finance_period($clock['day'], $period);
    $start = $range['start']; $end = $range['end'];
    $due = $cash = null;
    if ($dueAllowed) {
        $result = $conn->query("SELECT COUNT(*) quantity, COALESCE(SUM(l.valor),0) amount, COALESCE(SUM(l.status='vencido'),0) overdue_quantity FROM sis_lanc l WHERE l.deltitulo=0 AND l.status IN ('aberto','vencido') AND l.datavenc>='$start' AND l.datavenc<'$end'".($full ? '' : " AND EXISTS (SELECT 1 FROM sis_cliente c WHERE c.login=l.login$scope)"));
        if (!$result) throw new RuntimeException('due query');
        $row = $result->fetch_assoc();
        $due = array('quantity'=>(int)$row['quantity'], 'amount'=>(string)$row['amount'], 'overdue_quantity'=>(int)$row['overdue_quantity']);
    }
    if ($cashAllowed) {
        $result = $conn->query("SELECT COALESCE(SUM(entrada),0) incoming, COALESCE(SUM(saida),0) outgoing, COALESCE(SUM(entrada),0)-COALESCE(SUM(saida),0) balance FROM sis_caixa WHERE data>='$start' AND data<'$end'");
        if (!$result) throw new RuntimeException('cash query');
        $cash = $result->fetch_assoc();
        if (!$balanceAllowed) unset($cash['balance']);
    }
    echo json_encode(array('ok'=>true, 'day'=>$clock['day'], 'period'=>$period, 'range'=>$range, 'updated_at'=>$clock['updated_at'], 'due'=>$due, 'cash'=>$cash), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(array('ok'=>false, 'message'=>'Resumo financeiro indisponível. Tente novamente.'));
}
