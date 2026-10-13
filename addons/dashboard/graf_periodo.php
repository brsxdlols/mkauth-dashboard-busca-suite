<?php
// Aggregate each financial table once for the requested months instead of repeating per-month scans.
$financeMonths=array();
for($offset=$per_meses-1;$offset>=0;$offset--)$financeMonths[]=date('Y-m',strtotime("-$offset month"));
$dueMonths=$payableMonths=$cashMonths=$ticketMonths=array();$balanceMonths=array();
if($financeMonths){
    // Include native partial zero-day dates that the previous YYYY-MM LIKE also counted.
    $financeStart=min($financeMonths).'-00 00:00:00';
    $financeEnd=date('Y-m-d H:i:s',strtotime(max($financeMonths).'-01 00:00:00 +1 month'));
    $loadMonths=function($sql)use($conn){
        $result=mysqli_query($conn,$sql);if(!$result)throw new RuntimeException('Falha ao consultar indicadores financeiros.');
        $rows=array();while($row=mysqli_fetch_assoc($result))$rows[$row['month_key']]=$row;return $rows;
    };
    $dueMonths=$loadMonths("SELECT DATE_FORMAT(l.datavenc,'%Y-%m') month_key,SUM(l.valor) fat_previsto,SUM(CASE WHEN l.status NOT LIKE 'pago' THEN l.valor END) f_pagar FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login=c.login WHERE $grupos l.datavenc >= '$financeStart' AND l.datavenc < '$financeEnd' AND l.deltitulo=0 AND c.cli_ativado='s' GROUP BY month_key");
    $payableMonths=$loadMonths("SELECT DATE_FORMAT(vencimento,'%Y-%m') month_key,SUM(valor) contas_a_pagar FROM sis_contaspagar WHERE vencimento >= '$financeStart' AND vencimento < '$financeEnd' AND status NOT LIKE 'liquidado' GROUP BY month_key");
    $cashMonths=$loadMonths("SELECT DATE_FORMAT(data,'%Y-%m') month_key,SUM(entrada) tot_entrada,SUM(saida) tot_saida,SUM(CASE WHEN historico LIKE 'estorno%' THEN entrada END) estorno_entrada,SUM(CASE WHEN historico LIKE 'estorno%' THEN saida END) estorno_saida,SUM(CASE WHEN historico LIKE 'emprestimo%' OR historico LIKE 'credito emprestimo%' THEN entrada END) emprestimo FROM sis_caixa WHERE data >= '$financeStart' AND data < '$financeEnd' GROUP BY month_key");
    $ticketMonths=$loadMonths("SELECT DATE_FORMAT(datapag,'%Y-%m') month_key,COUNT(login) ticket FROM sis_lanc WHERE datapag >= '$financeStart' AND datapag < '$financeEnd' AND valorpag <= $limite_ticket GROUP BY month_key");
    // Keep the original balance cutoff and decimal SUM arithmetic, including short months.
    $balanceColumns=array();$uniqueMonths=array_values(array_unique($financeMonths));
    foreach($uniqueMonths as $n=>$month){$cutoff=$month.'-31 23:59:59';$balanceColumns[]="(SUM(CASE WHEN data <= '$cutoff' THEN entrada END)-SUM(CASE WHEN data <= '$cutoff' THEN saida END)) b$n";}
    $balances=mysqli_query($conn,'SELECT '.implode(',',$balanceColumns)." FROM sis_caixa WHERE data < '$financeEnd'");
    if(!$balances)throw new RuntimeException('Falha ao consultar saldo financeiro.');$balanceRow=mysqli_fetch_assoc($balances);
    foreach($uniqueMonths as $n=>$month)$balanceMonths[$month]=$balanceRow['b'.$n];
}
for ($i = $per_meses - 1; $i >= 0; $i--) {
    $mes_cap=date('m',strtotime("-$i month"));$ano_cap=date('Y',strtotime("-$i month"));
    if($i==0){$mes_cap=date('m');$ano_cap=date('Y');}
    $ano_mes=$ano_cap.'-'.$mes_cap;
    $previsao_mes_[$ano_mes]=$dueMonths[$ano_mes]['fat_previsto']??null;
    $a_receber_mes_[$ano_mes]=$dueMonths[$ano_mes]['f_pagar']??null;
    $contas_a_pagar_[$ano_mes]=$payableMonths[$ano_mes]['contas_a_pagar']??null;
    $tot_entrada_[$ano_mes]=$cashMonths[$ano_mes]['tot_entrada']??null;
    $tot_saida_[$ano_mes]=$cashMonths[$ano_mes]['tot_saida']??null;
    $cont_ticket_[$ano_mes]=$ticketMonths[$ano_mes]['ticket']??'0';
    $estorno_entrada_[$ano_mes]=0;$estorno_entrada_[$ano_mes]+=$cashMonths[$ano_mes]['estorno_entrada']??null;
    $estorno_saida_[$ano_mes]=0;$estorno_saida_[$ano_mes]+=$cashMonths[$ano_mes]['estorno_saida']??null;
    $desc_emprestimo_[$ano_mes]=$cashMonths[$ano_mes]['emprestimo']??null;
    $saldo_conta_[$ano_mes]=$balanceMonths[$ano_mes]??null;
    $tot_entrada_[$ano_mes] = $tot_entrada_[$ano_mes] - $estorno_entrada_[$ano_mes] - $estorno_saida_[$ano_mes];
    $tot_entrada_[$ano_mes] = round($tot_entrada_[$ano_mes]);

    $tot_saida_[$ano_mes] = $tot_saida_[$ano_mes] - $estorno_entrada_[$ano_mes] - $estorno_saida_[$ano_mes];
    $tot_saida_[$ano_mes] = round($tot_saida_[$ano_mes]);

    $saldo_[$ano_mes] = $tot_entrada_[$ano_mes] - $tot_saida_[$ano_mes];
    $saldo_[$ano_mes] = round($saldo_[$ano_mes]);

    $saldo_conta_ [$ano_mes] = round($saldo_conta_[$ano_mes]);
    
    $diff_prev_realizado_[$ano_mes] = $a_receber_mes_[$ano_mes];
    $diff_prev_realizado_[$ano_mes] = round($diff_prev_realizado_[$ano_mes]);

    /*if($diff_prev_realizado_[$ano_mes] < 0){
        $diff_prev_realizado_[$ano_mes] = 0;
    }*/

    $previsao_mes_[$ano_mes] = round($previsao_mes_[$ano_mes]);

    $contas_a_pagar_[$ano_mes] = round($contas_a_pagar_[$ano_mes]);


    if ($cont_ticket_[$ano_mes] != 0) {
        $ticket_medio_[$ano_mes] = number_format(($tot_entrada_[$ano_mes] - $desc_emprestimo_[$ano_mes]) / $cont_ticket_[$ano_mes], 2);
    } else {
        $ticket_medio_[$ano_mes] = 0;
    }

    //Entrada sem emprestimo
    $tot_entrada_sem_emprestimo[$ano_mes] = $tot_entrada_[$ano_mes] - $desc_emprestimo_[$ano_mes];
    //Entrada com emprestimo
    $tot_entrada_com_emprestimo[$ano_mes] = $tot_entrada_[$ano_mes];

    //Total Emprestimos
    $total_emprestimo_[$ano_mes] = $tot_entrada_com_emprestimo[$ano_mes] - $tot_entrada_sem_emprestimo[$ano_mes];

    //Condição para exibição.
    if ($desc_emprestimo_[$ano_mes] == 0) {
        $tot_entrada_com_emprestimo[$ano_mes] = 0;
        $total_emprestimo_[$ano_mes] = 0;
    }

    // Total graf Anual
    $tot_fat_previsto += $previsao_mes_[$ano_mes];
    $tot_entrada += $tot_entrada_[$ano_mes];

    $tot_a_receber += $diff_prev_realizado_[$ano_mes];

    $tot_contas_pagar += $contas_a_pagar_[$ano_mes];

    $tot_saida += $tot_saida_[$ano_mes];
    $saldo = $tot_entrada - $tot_saida;

    $tot_geral_entrada_sem_emprestimo += $tot_entrada_sem_emprestimo[$ano_mes];
    $tot_geral_emprestimos += $total_emprestimo_[$ano_mes];
}


//debug($tot_entrada_);

$mes_nome = array(
    "01" => "JAN",
    "02" => "FEV",
    "03" => "MAR",
    "04" => "ABR",
    "05" => "MAI",
    "06" => "JUN",
    "07" => "JUL",
    "08" => "AGO",
    "09" => "SET",
    "10" => "OUT",
    "11" => "NOV",
    "12" => "DEZ",

);
?>

