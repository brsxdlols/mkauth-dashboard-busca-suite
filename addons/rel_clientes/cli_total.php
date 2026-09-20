<!-- 
    RELATÃ¯Â¿Â½RIO DE CLIENTES
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php

$query_cli_total_atual = mysqli_query($link, "SELECT * FROM rel_clientes_total_cli WHERE ano = '$ano'");

while ($row = mysqli_fetch_array($query_cli_total_atual)) {
    $tot_cli_atual = $row['tot_clientes'];
    $tot_cli_atual_no_add = $row['tot_cli_no_add'];
}

// Para o Gráfico
$saldo_ano_ant_graf = $tot_cli_atual - $saldo_cli_ano;
$saldo_ano_ant_no_add_graf = $tot_cli_atual_no_add - $saldo_cli_ano_no_add;

// Soma total mes a mes do total de clientes
$saldo_ano_ant = $tot_cli_atual - $saldo_cli_ano;
$saldo_ano_ant_no_add = $tot_cli_atual_no_add - $saldo_cli_ano_no_add;

$ano_anterior = $ano - 1;
$query_cli_ano_anterior = mysqli_query($link, "INSERT INTO rel_clientes_total_cli (ano,tot_clientes, tot_cli_no_add) VALUES ($ano_anterior, $saldo_ano_ant, $saldo_ano_ant_no_add)");

//if($ano == date('Y')){
for ($i = 1; $i <= 12; $i++) {
    if ($i < 10) {
        $i = '0' . $i;
    }

    $total_clientes[$i] = 0;

    $total_clientes_no_add[$i] = 0;

    //Total Clientes
    $saldo_ano_ant += $total_clientes[$i] + $tot_cli_saldo[$i];
    $tot_clientes_mes[$i] = $saldo_ano_ant;

    // Total Clientes sem Adicionais
    $saldo_ano_ant_no_add += $total_clientes_no_add[$i] + $tot_cli_saldo_no_add[$i];
    $tot_clientes_mes_no_add[$i] = $saldo_ano_ant_no_add;
}
mysqli_close($link);
?>

<div class="col-12 col-md-8">
    <figure class="highcharts-figure">
        <div id="container4"></div>
        <p class="highcharts-description">
            <script>
                Highcharts.chart('container4', {
                    chart: {
                        type: 'line',
                    },
                    plotOptions: {
                        line: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    title: {
                        text: '<?php echo "Total de Clientes por Mes - $ano"; ?>'
                    },
                    xAxis: {
                        categories: ['<?php echo $ano_anterior; ?>',
                            <?php
                            foreach ($lista_mes as $key => $value) {
                                if ($mes > 0 && $mes < 10)
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
                        name: 'Total Clientes',
                        data: [<?php echo "$saldo_ano_ant_graf,"; ?>
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                if ($i < 10) {
                                    $i = "0$i";
                                }
                                echo "$tot_clientes_mes[$i],";
                            }
                            ?>
                        ]
                    }]
                });
            </script>
        </p>
    </figure>




</div>

<div class="col-12 col-md-4">
    <figure class="highcharts-figure">
        <div id="container3"></div>
        <p class="highcharts-description">
            <script>
                Highcharts.chart('container3', {
                    chart: {
                        type: 'column',
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    title: {
                        text: '<?php echo "Balanço Anual $ano"; ?>'
                    },
                    xAxis: {
                        categories: ['Balanço Anual']
                    },
                    credits: {
                        enabled: false
                    },
                    series: [{
                        name: 'Instalações + Reativações',
                        data: [
                            <?php
                            echo "$tot_cli_ativados";
                            ?>
                        ],
                        color: Highcharts.getOptions().colors[2]
                    }, {
                        name: 'Cancelamentos',
                        data: [
                            <?php
                            echo "$tot_cli_desativados";
                            ?>
                        ],
                        color: Highcharts.getOptions().colors[8]
                    }, {
                        name: 'Saldo',
                        data: [
                            <?php
                            echo "$saldo_cli_ano";
                            ?>
                        ],
                        color: Highcharts.getOptions().colors[3]
                    }]
                });
            </script>
        </p>
    </figure>
</div>

<div class="col-12 col-md-8">

    <figure class="highcharts-figure">
        <div id="container5"></div>
        <p class="highcharts-description">
            <script>
                Highcharts.chart('container5', {
                    chart: {
                        type: 'line',
                    },
                    plotOptions: {
                        line: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    title: {
                        text: '<?php echo "Total de Clientes sem Adicionais por Mes - $ano"; ?>'
                    },
                    xAxis: {
                        categories: ['<?php echo $ano_anterior; ?>',
                            <?php
                            foreach ($lista_mes as $key => $value) {
                                if ($mes > 0 && $mes < 10)
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
                        data: [<?php echo "$saldo_ano_ant_no_add_graf,"; ?>
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                if ($i < 10) {
                                    $i = "0$i";
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

</div>

<div class="col-12 col-md-4">

    <figure class="highcharts-figure">
        <div id="container9"></div>
        <p class="highcharts-description">
            <script>
                Highcharts.chart('container9', {
                    chart: {
                        type: 'column',
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true
                            }
                        }
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


</div>