<?php
function mka_finance_summary_enabled($db) {
    $q = $db->query("SELECT valor FROM sis_opcao WHERE nome='suite_finance_summary_enabled' LIMIT 1");
    if (!$q) throw new RuntimeException('Financial display settings unavailable');
    $row = $q->fetch_assoc();
    return !$row || $row['valor'] !== 'nao';
}
function mka_finance_summary_save($db, $value) {
    if (!in_array($value, array('sim', 'nao'), true)) throw new InvalidArgumentException('Invalid display option');
    $stmt = $db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_finance_summary_enabled',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    $stmt->bind_param('s', $value);
    if (!$stmt->execute()) throw new RuntimeException('Could not save display setting');
}
// Calendar bounds only: never change the server, database or PHP timezone.
function mka_finance_period($day, $period) {
    if (!in_array($period, array('day', 'week'), true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $day)) throw new InvalidArgumentException('Invalid financial period');
    $start = new DateTimeImmutable($day, new DateTimeZone('UTC'));
    if ($period === 'week') $start = $start->modify('-'.$start->format('w').' days');
    $end = $start->modify($period === 'week' ? '+7 days' : '+1 day');
    return array('start'=>$start->format('Y-m-d'), 'end'=>$end->format('Y-m-d'), 'last'=>$end->modify('-1 day')->format('Y-m-d'));
}
