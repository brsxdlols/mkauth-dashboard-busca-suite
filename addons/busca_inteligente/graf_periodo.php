

    <?php


//$starttime = microtime(true);

for ($i = $per_meses - 1; $i >= 0; $i--) {

    //Listagem de Meses e Anos
    $mes_cap = date('m', strtotime("-$i month"));
    $ano_cap = date('Y', strtotime("-$i month"));

    // Regra para exibição do periodo atual
    if ($i == 0) {
        $mes_cap = date('m');
        $ano_cap = date('Y');
    }
    //echo "<br>Mes: $mes_cap";
    //echo "<br>Ano: $ano_cap";

    $ano_mes = $ano_cap . '-' . $mes_cap;

    // Tot Previsao Faturamento
    $query_previsao_fat_[$ano_mes] = mysqli_query($conn, "SELECT sum(l.valor) fat_previsto FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE $grupos l.datavenc LIKE '$ano_cap-$mes_cap%' AND l.deltitulo = 0 AND c.cli_ativado = 's'");
    while ($prev = mysqli_fetch_array($query_previsao_fat_[$ano_mes])) {
        $previsao_mes_[$ano_mes] = $prev['fat_previsto'];
    }

        // Tot Previsao Faturamento
        $query_a_receber_fat_[$ano_mes] = mysqli_query($conn, "SELECT sum(l.valor) f_pagar FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE $grupos l.datavenc LIKE '$ano_cap-$mes_cap%' AND l.deltitulo = 0 AND c.cli_ativado = 's' AND l.status NOT LIKE 'pago'");
        while ($prev = mysqli_fetch_array($query_a_receber_fat_[$ano_mes])) {
            $a_receber_mes_[$ano_mes] = $prev['f_pagar'];
        }

    // Tot Contas a pagar mes
    $query_contas_a_pagar_[$ano_mes] = mysqli_query($conn, "SELECT sum(valor) contas_a_pagar FROM sis_contaspagar WHERE vencimento LIKE '$ano_cap-$mes_cap%' AND status NOT LIKE 'liquidado'");
    while ($contas = mysqli_fetch_array($query_contas_a_pagar_[$ano_mes])) {
        $contas_a_pagar_[$ano_mes] = $contas['contas_a_pagar'];
    }

    //Tot Entrada e Saida
    $query_mes[$ano_mes] = mysqli_query($conn, "SELECT sum(entrada) tot_entrada, sum(saida) tot_saida FROM sis_caixa 
        WHERE 
        data LIKE '$ano_cap-$mes_cap%' 
        ORDER BY data");

    $tot_entrada_[$ano_mes] = 0;
    $tot_saida_[$ano_mes] = 0;

    $cont_ticket_[$ano_mes] = 0;

    while ($row = mysqli_fetch_array($query_mes[$ano_mes])) {
        $tot_entrada_[$ano_mes] = $row['tot_entrada'];
        $tot_saida_[$ano_mes] = $row['tot_saida'];

        $query_ticket_[$ano_mes] = mysqli_query($conn, "SELECT count(login) ticket FROM sis_lanc WHERE datapag LIKE '$ano_cap-$mes_cap%' AND valorpag <= $limite_ticket");
        while ($cont = mysqli_fetch_array($query_ticket_[$ano_mes])) {
            $cont_ticket_[$ano_mes] = $cont['ticket'];
        }
    }


    //Tot Estorno
    $query_mes[$ano_mes] = mysqli_query($conn, "SELECT sum(entrada) tot_estorno_entrada, sum(saida) tot_estorno_saida FROM sis_caixa 
        WHERE historico LIKE 'estorno%' AND
        data LIKE '$ano_cap-$mes_cap%' 
        ORDER BY data");

    $estorno_entrada_[$ano_mes] = 0;
    $estorno_saida_[$ano_mes] = 0;

    while ($row = mysqli_fetch_array($query_mes[$ano_mes])) {
        $estorno_entrada_[$ano_mes] += $row['tot_estorno_entrada'];
        $estorno_saida_[$ano_mes] += $row['tot_estorno_saida'];
    }

    //Desconto Emprestimo Ticket
    $query_mes[$ano_mes] = mysqli_query($conn, "SELECT sum(entrada) tot_emprestimo FROM sis_caixa 
        WHERE (historico LIKE 'emprestimo%' OR historico LIKE 'credito emprestimo%') AND
        data LIKE '$ano_cap-$mes_cap%' 
        ORDER BY data");

    $desc_emprestimo_[$ano_mes] = 0;

    while ($row = mysqli_fetch_array($query_mes[$ano_mes])) {
        $desc_emprestimo_[$ano_mes] = $row['tot_emprestimo'];
    }

    $query_saldo_conta[$ano_mes] = mysqli_query($conn, "SELECT (sum(entrada) - sum(saida)) saldo_conta  FROM sis_caixa WHERE data <= '$ano_cap-$mes_cap-31 23:59:59'");

    if (!$query_saldo_conta[$ano_mes]) {
        echo mysqli_error($link);
    }

    while ($row = mysqli_fetch_array($query_saldo_conta[$ano_mes])) {
        $saldo_conta_[$ano_mes] = $row['saldo_conta'];
    }

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

