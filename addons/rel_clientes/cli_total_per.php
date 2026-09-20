<!-- 
    RELATÃ¯Â¿Â½RIO DE CLIENTES
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php

    $saldo_anterior = $total_clientes_atual + $saldo_cli_ano;

    for($i=0; $i <= 23; $i++){

        //Listagem de Meses e Anos
        $mes = date('m', strtotime("-$i month"));
        $ano = date('Y', strtotime("-$i month"));

        // Regra para exibição do periodo atual
        if($i == 0){
            $mes = date('m');
            $ano = date('Y');
        }


        //$total_clientes_no_add[$ano.$mes] = 0;

        
        //Total Clientes
        $total_clientes[$ano.$mes] += $tot_cli_saldo[$ano.$mes];

        //$saldo_geral[$ano.$mes] = $saldo_cli_hoje + $tot_cli_saldo[$ano.$mes];
        //$tot_clientes_mes[$ano.$mes] = $saldo_geral[$ano.$mes];

        // Total Clientes sem Adicionais
        //$saldo_ano_ant_no_add += $total_clientes_no_add[$i] + $tot_cli_saldo_no_add[$i];
        //$tot_clientes_mes_no_add[$i] = $saldo_ano_ant_no_add;
    }
    mysqli_close($link);

    debug($total_clientes);
?>

<figure class="highcharts-figure">
            <div id="container4"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container4', {
            chart: {
                type: 'line',
            },
            plotOptions: {
                line: { dataLabels: { enabled: true } }
            },
            title: {
                text: '<?php echo "Total de Clientes por Mes - $ano"; ?>'
            },
            xAxis: {
                categories: [
                    <?php
                        foreach($total_clientes as $key => $value){
                            echo "$key,"; 
                        }
                        ?>
                ]
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Total Clientes',
                data: [
                    <?php 
                        foreach($total_clientes as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ]
            }]
        });

        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
            <div id="container9"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container9', {
            chart: {
                type: 'column',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } }
            },
            title: {
                text: '<?php echo "Balanço Anual sem Adicionais $ano"; ?>'
            },
            xAxis: {
                categories: ['Balanço Anual sem Adicionais']
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Instalações + Reativações',
                data: [
                    <?php 
                        echo "$tot_cli_inst_no_add";
                    ?>
                ],
                color: Highcharts.getOptions().colors[2]
            }, {
                name: 'Cancelamentos',
                data: [
                    <?php 
                        echo "$tot_cli_canc_no_add";
                    ?>
                ],
                color: Highcharts.getOptions().colors[8]
            }, {
                name: 'Saldo',
                data: [
                    <?php 
                        echo "$saldo_cli_ano_no_add";
                    ?>
                ],
                color: Highcharts.getOptions().colors[3]
            }]
        });

        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
            <div id="container5"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container5', {
            chart: {
                type: 'line',
            },
            plotOptions: {
                line: { dataLabels: { enabled: true } }
            },
            title: {
                text: '<?php echo "Total de Clientes sem Adicionais por Mes - $ano"; ?>'
            },
            xAxis: {
                categories: ['<?php echo $ano_anterior; ?>',
                    <?php
                            foreach($lista_mes as $key => $value){
                                if ($mes >0 && $mes < 10)
                                    $mes = "0$mes";
                                echo "'$value',";
                            }
                        ?>
                ]
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Total Clientes sem Adicionais',
                data: [<?php echo "$saldo_ano_ant_no_add_graf,";?>
                    <?php 
                        for($i=1; $i <= 12; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$tot_clientes_mes_no_add[$i],"; 
                        }
                    ?>
                ]
            }]
        });

        </script>
        </p>
    </figure>