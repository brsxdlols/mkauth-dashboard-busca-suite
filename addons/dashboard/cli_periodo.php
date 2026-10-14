<?php
// Aggregate the monthly log counters in one pass. Keep legacy LIKE rules,
// DISTINCT-login semantics (including NULL), and the deactivation fallback.
$customerMonths = array();
for ($c = $per_meses - 1; $c >= 0; $c--) {
    $customerMonths[] = date('Y-m', strtotime("-$c month"));
}
$customerMonthKeys = array_values(array_unique($customerMonths));
$customerCounts = array();
$customerLogParts = $customerDataParts = $customerLogMonths = array();
$customerCancelRule = !empty($cli_del_cancelalado)
    ? "registro LIKE 'deletou o cliente % do sistema%'"
    : "(registro LIKE '%desativou o cliente - IP%' OR registro LIKE '%desativou pelo motivo%')";
foreach ($customerMonthKeys as $key => $month) {
    $logMonth = substr($month, 5, 2).'/'.substr($month, 0, 4);
    $match = "data LIKE '%$logMonth%'";
    $customerLogMonths[] = $match;
    $rules = array(
        'additional' => "registro LIKE 'inseriu o adicional:%'",
        'reactivated' => "(registro LIKE '%ativou o cliente - IP:%' AND registro NOT LIKE '%desativou%')",
        'cancelled' => $customerCancelRule,
        'additional_deleted' => "registro LIKE 'deletou o adicional%'"
    );
    foreach ($rules as $name => $rule) {
        $condition = "($match AND $rule)";
        if ($name === 'reactivated' || $name === 'cancelled') {
            $expression = "COUNT(DISTINCT CASE WHEN $condition THEN login END) + COALESCE(MAX(CASE WHEN $condition AND login IS NULL THEN 1 ELSE 0 END),0)";
        } else {
            $expression = "COALESCE(SUM(CASE WHEN $condition THEN 1 ELSE 0 END),0)";
        }
        $customerLogParts[] = "$expression AS m{$key}_{$name}";
    }
    $customerDataParts[] = "COALESCE(SUM(CASE WHEN data_ins LIKE '%$month%' THEN 1 ELSE 0 END),0) AS m{$key}_installed";
    $customerDataParts[] = "COALESCE(SUM(CASE WHEN data_desativacao LIKE '$month%' THEN 1 ELSE 0 END),0) AS m{$key}_fallback";
}
$customerLogSql = 'SELECT '.implode(',', $customerLogParts).' FROM sis_logs WHERE ('.implode(' OR ', $customerLogMonths).')';
$customerLogQuery = mysqli_query($conn, $customerLogSql);
$customerDataQuery = mysqli_query($conn, 'SELECT '.implode(',', $customerDataParts).' FROM sis_cliente');
if (!$customerLogQuery || !$customerDataQuery) throw new RuntimeException('Unable to calculate monthly customer totals.');
$customerLogCounts = mysqli_fetch_assoc($customerLogQuery);
$customerDataCounts = mysqli_fetch_assoc($customerDataQuery);
foreach ($customerMonthKeys as $key => $month) {
    $customerCounts[$month] = array(
        'installed' => (int)$customerDataCounts["m{$key}_installed"],
        'additional' => (int)$customerLogCounts["m{$key}_additional"],
        'reactivated' => (int)$customerLogCounts["m{$key}_reactivated"],
        'cancelled' => (int)$customerLogCounts["m{$key}_cancelled"] ?: (int)$customerDataCounts["m{$key}_fallback"],
        'additional_deleted' => (int)$customerLogCounts["m{$key}_additional_deleted"]
    );
}
for ($c = $per_meses - 1; $c >= 0; $c--) {
    $mes = date('m', strtotime("-$c month"));
    $ano = date('Y', strtotime("-$c month"));
    if ($c == 0) { $mes_cap = date('m'); $ano_cap = date('Y'); }
    $ano_mes = $ano.'-'.$mes;
    $counts = $customerCounts[$ano_mes];
    $num_novos[$ano_mes] = $counts['installed'];
    $num_add_ins[$ano_mes] = $counts['additional'];
    $num_reativados[$ano_mes] = $counts['reactivated'];
    $num_desativados[$ano_mes] = $counts['cancelled'];
    $num_adicional_del[$ano_mes] = $counts['additional_deleted'];
    $tot_cli_add[$ano_mes] = $num_novos[$ano_mes] + $num_add_ins[$ano_mes];
    $tot_cli_reat[$ano_mes] = $num_reativados[$ano_mes];
    $tot_cli_add_and_reat[$ano_mes] = $tot_cli_add[$ano_mes] + $tot_cli_reat[$ano_mes];
    $tot_cli_canc[$ano_mes] = $num_desativados[$ano_mes] + $num_adicional_del[$ano_mes];
    $tot_cli_saldo[$ano_mes] = $tot_cli_add_and_reat[$ano_mes] - $tot_cli_canc[$ano_mes];
    $tot_cli_saldo_no_add[$ano_mes] = ($tot_cli_saldo_no_add[$ano_mes] ?? 0) + $num_novos[$ano_mes] + $num_reativados[$ano_mes] - $num_desativados[$ano_mes];
}
$mes = $mes - 1;
$tot_cli_ativados = array_sum($tot_cli_add_and_reat);
$tot_cli_desativados = array_sum($tot_cli_canc);
$saldo_cli_ano = $tot_cli_ativados - $tot_cli_desativados;
$tot_cli_inst_no_add = array_sum($num_novos) + array_sum($num_reativados);
$tot_cli_canc_no_add = array_sum($num_desativados);
$saldo_cli_ano_no_add = $tot_cli_inst_no_add - $tot_cli_canc_no_add;
