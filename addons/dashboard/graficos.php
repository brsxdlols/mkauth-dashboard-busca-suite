<!-- GRAFICO FATURAMENTO -->
<?php if ((permissao('perm_relFin') || permissao('perm_relFat'))) {
    if ($exb_balanco_faturamento == 's') {
?>
        <div class='card border-light mb-2'>
            <div class="card-header text-uppercase">
                Faturamento
            </div>
            <div class="card-body m-0 p-0">
                <div class='row'>
                    <div class='col-12 col-md-8 col-lg-8'>
                        <figure class="highcharts-figure">
                            <div id="container-graf-fat-1"></div>
                            <p class="highcharts-description">
                                <script>
                                    // Balanco Faturamento
                                    Highcharts.chart('container-graf-fat-1', {
                                        plotOptions: {
                                            column: {
                                                dataLabels: {
                                                    enabled: true
                                                }
                                            }
                                        },
                                        title: {
                                            text: '<?php echo "Balanço Faturamento $string_per"; ?>'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($tot_entrada_ as $key => $value) {
                                                    echo "'$key',";
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                                type: 'column',
                                                name: 'Previsao Faturamento',
                                                data: [
                                                    <?php
                                                    foreach ($previsao_mes_ as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ]
                                            },
                                            {
                                                type: 'column',
                                                name: 'Faturamento Realizado',
                                                data: [
                                                    <?php
                                                    foreach ($tot_entrada_ as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ]
                                            },
                                            {
                                                type: 'column',
                                                name: 'A receber',
                                                data: [
                                                    <?php
                                                    foreach ($diff_prev_realizado_ as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ]
                                            },
                                            {
                                                type: 'column',
                                                name: 'Contas a pagar',
                                                data: [
                                                    <?php
                                                    foreach ($contas_a_pagar_ as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ]
                                            },
                                            {
                                                type: 'column',
                                                name: 'Despesas',
                                                data: [
                                                    <?php
                                                    foreach ($tot_saida_ as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ]
                                            }
                                        ]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>


                    <div class='col-12 col-md-4 col-lg-4'>

                        <figure class="highcharts-figure">
                            <div id="container-graf-fat-2"></div>
                            <p class="highcharts-description">
                                <script>
                                    // Balanco Periodo Total
                                    Highcharts.chart('container-graf-fat-2', {
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
                                            text: 'Balanço <?php echo "$string_per"; ?>'
                                        },
                                        xAxis: {
                                            categories: ['Balanço']
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [
                                        {
                                            name: 'Previsao Faturamento',
                                            data: [<?php echo "$tot_fat_previsto,"; ?>]
                                        },
                                        {
                                            name: 'Faturamento Realizado',
                                            data: [<?php echo "$tot_entrada,"; ?>]
                                        },
                                        {
                                            name: 'A receber',
                                            data: [<?php echo "$tot_a_receber,"; ?>]
                                        },
                                        {
                                            name: 'Contas a Pagar',
                                            data: [<?php echo "$tot_contas_pagar,"; ?>]
                                        },
                                        {
                                            name: 'Despesas',
                                            data: [<?php echo "$tot_saida,"; ?>]
                                        },
                                        {
                                            name: 'Lucro / Prejuizo',
                                            data: [<?php echo "$saldo,"; ?>]
                                        }
                                    ]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                    
            <!-- Ticket Medio e Saldo Conta -->
            <?php 
                if ($exb_ticket_medio == 's') {
            ?>
                    <div class='col-12 col-md-6'>


                        <figure class="highcharts-figure">
                            <div id="container-graf-fat-6"></div>
                            <p class="highcharts-description">
                                <script>
                                    // Ticket Medio
                                    Highcharts.chart('container-graf-fat-6', {
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
                                            text: 'Ticket Médio Mensal'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($ticket_medio_ as $key => $value) {
                                                    echo "'$key',";
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Ticket Médio',
                                            data: [
                                                <?php
                                                foreach ($ticket_medio_ as $key => $value) {
                                                    echo "$value,";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                <?php
                }
                if ($exb_saldo_conta == 's') {
                ?>

                    <div class='col-12 col-md-6'>


                        <figure class="highcharts-figure">
                            <div id="container-graf-fat-7"></div>
                            <p class="highcharts-description">
                                <script>
                                    // Saldo em Conta
                                    Highcharts.chart('container-graf-fat-7', {
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
                                            text: '<?php echo "Saldo em Conta $string_per"; ?>'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($saldo_conta_ as $key => $value) {
                                                    echo "'$key',";
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Saldo em Conta',
                                            data: [
                                                <?php
                                                foreach ($saldo_conta_ as $key => $value) {
                                                    echo "$value,";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>

                    </div>

                <?php } ?>

                </div>
            </div>
        </div>


<?php }
} ?>

<?php if ((permissao('perm_relFin') || permissao('perm_relFat'))) {
    if ($exb_balanco_clientes == 's') {
?>

        <div class='card border-light mb-2'>
            <div class="card-header text-uppercase">
                Clientes
            </div>
            <div class="card-body m-0 p-0">
                <div class='row'>
                    <div class='col-12 col-md-12 col-lg-5'>

                        <!-- Graf Planos dos Clientes -->
                        <figure class="highcharts-figure">
                            <div id="container-rel-cli-planos-1"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-cli-planos-1', {
                                        chart: {
                                            plotBackgroundColor: null,
                                            plotBorderWidth: null,
                                            plotShadow: false,
                                            type: 'pie'
                                        },
                                        title: {
                                            text: 'Planos por Clientes'
                                        },
                                        tooltip: {
                                            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                                        },
                                        accessibility: {
                                            point: {
                                                valueSuffix: '%'
                                            }
                                        },
                                        plotOptions: {
                                            pie: {
                                                allowPointSelect: true,
                                                cursor: 'pointer',
                                                dataLabels: {
                                                    enabled: true,
                                                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                                                }
                                            }
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Brands',
                                            colorByPoint: true,
                                            data: [
                                                <?php
                                                foreach ($lista_planos as $key => $value) {
                                                    echo "
                    { 
                        name: '$key',
                        y: $value
                    },
                    
                    ";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>

                            </p>
                        </figure>
                    </div>
                    <!-- REL CLIENTES -->
                    <div class='col-12 col-md-6 col-lg-4'>

                        <figure class="highcharts-figure">
                            <div id="container-rel-cli-1"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-cli-1', {
                                        chart: {
                                            type: 'column',
                                        },
                                        plotOptions: {
                                            column: {
                                                dataLabels: {
                                                    enabled: true
                                                }
                                            },
                                            line: {
                                                dataLabels: {
                                                    enabled: true
                                                },
                                                enableMouseTracking: false
                                            },
                                        },
                                        title: {
                                            text: '<?php echo "Balanço Clientes $string_per"; ?>'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($tot_cli_add_and_reat as $key => $value) {
                                                    echo "'$key',";
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                                name: 'Instalações + Reativações',
                                                data: [
                                                    <?php
                                                    foreach ($tot_cli_add_and_reat as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ],
                                                color: Highcharts.getOptions().colors[2]
                                            }, {
                                                name: 'Cancelamentos',
                                                data: [
                                                    <?php
                                                    foreach ($tot_cli_canc as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ],
                                                color: Highcharts.getOptions().colors[8]
                                            },
                                            {
                                                name: 'Saldo',
                                                data: [
                                                    <?php
                                                    foreach ($tot_cli_saldo as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                    ?>
                                                ],
                                                color: Highcharts.getOptions().colors[3]
                                            }
                                        ]
                                    });
                                </script>
                            </p>
                        </figure>

                    </div>

                    <div class='col-12 col-md-6 col-lg-3'>


                        <figure class="highcharts-figure">
                            <div id="container-rel-cli-2"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-cli-2', {
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
                                            text: '<?php echo "Balanço $string_per"; ?>'
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
                </div>
            </div>
        </div>

<?php }
} ?>


<!-- RELATORIO DE CHAMADOS -->
<?php if (permissao('perm_relFin') && permissao('perm_chamados')) {
    if ($exb_balanco_chamados == 's') {
?>
        <div class='card border-light mb-2'>
            <div class="card-header text-uppercase">
                Chamados
            </div>
            <div class="card-body m-0 p-0">
                <div class='row'>
                    <div class='col-12 col-md-6 col-lg-6'>
                        <figure class="highcharts-figure">
                            <div id="container-rel-chamados-1"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-chamados-1', {
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
                                            text: '<?php echo "Chamados por Assunto"; ?>'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($assunto_ticket_aberto_ as $k => $v) {
                                                    foreach ($v as $key2 => $value) {
                                                        echo "'$k - $key2',";
                                                    }
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Aberto',
                                            data: [
                                                <?php
                                                foreach ($assunto_ticket_aberto_ as $key) {
                                                    foreach ($key as $key2 => $value)
                                                        echo "$value,";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                    <div class='col-12 col-md-6 col-lg-6'>
                        <figure class="highcharts-figure">
                            <div id="container-rel-chamados-2"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-chamados-2', {
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
                                            text: '<?php echo "Chamados por Técnico"; ?>'
                                        },
                                        xAxis: {
                                            categories: [
                                                <?php
                                                foreach ($tecnico_ticket_aberto_ as $k => $v) {
                                                    foreach ($v as $key => $value) {
                                                        echo "'$k - $nome_tecnico[$key]',";
                                                    }
                                                }
                                                ?>
                                            ]
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Aberto',
                                            data: [
                                                <?php
                                                foreach ($tecnico_ticket_aberto_ as $k) {
                                                    foreach ($k as $key => $value) {
                                                        echo "$value,";
                                                    }
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                    <div class='col-12 col-md-4 col-lg-5'>
                        <figure class="highcharts-figure">
                            <div id="container-rel-chamados-3"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-chamados-3', {
                                        chart: {
                                            plotBackgroundColor: null,
                                            plotBorderWidth: null,
                                            plotShadow: false,
                                            type: 'pie'
                                        },
                                        title: {
                                            text: 'Total Chamados Abertos por Assunto'
                                        },
                                        tooltip: {
                                            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                                        },
                                        accessibility: {
                                            point: {
                                                valueSuffix: '%'
                                            }
                                        },
                                        plotOptions: {
                                            pie: {
                                                allowPointSelect: true,
                                                cursor: 'pointer',
                                                dataLabels: {
                                                    enabled: true,
                                                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                                                }
                                            }
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Brands',
                                            colorByPoint: true,
                                            data: [
                                                <?php
                                                foreach ($tot_assunto_ticket_aberto_ as $key => $value) {
                                                    echo "
                        { 
                            name: '$key',
                            y: $value
                        },
                        
                        ";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                    <div class='col-12 col-md-4 col-lg-5'>
                        <figure class="highcharts-figure">
                            <div id="container-rel-chamados-4"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-chamados-4', {
                                        chart: {
                                            plotBackgroundColor: null,
                                            plotBorderWidth: null,
                                            plotShadow: false,
                                            type: 'pie'
                                        },
                                        title: {
                                            text: 'Total Chamados Abertos por Técnico'
                                        },
                                        tooltip: {
                                            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                                        },
                                        accessibility: {
                                            point: {
                                                valueSuffix: '%'
                                            }
                                        },
                                        plotOptions: {
                                            pie: {
                                                allowPointSelect: true,
                                                cursor: 'pointer',
                                                dataLabels: {
                                                    enabled: true,
                                                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                                                }
                                            }
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Brands',
                                            colorByPoint: true,
                                            data: [
                                                <?php
                                                foreach ($tot_tecnico_ticket_aberto_ as $key => $value) {
                                                    echo "
                        { 
                            name: '$key',
                            y: $value
                        },
                        
                        ";
                                                }
                                                ?>
                                            ]
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>

                    <div class='col-12 col-md-4 col-lg-2'>
                        <figure class="highcharts-figure">
                            <div id="container-rel-chamados-5"></div>
                            <p class="highcharts-description">
                                <script>
                                    Highcharts.chart('container-rel-chamados-5', {
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
                                            text: '<?php echo "Total Chamados"; ?>'

                                        },
                                        xAxis: {
                                            categories: ['Chamados']
                                        },
                                        credits: {
                                            enabled: false
                                        },
                                        series: [{
                                            name: 'Abertos',
                                            data: [<?php echo "$cont_ticket_aberto"; ?>]
                                        }, {
                                            name: 'Fechados',
                                            data: [<?php echo "$cont_ticket_fechado"; ?>],
                                        }, {
                                            name: 'Saldo',
                                            data: [<?php echo "$saldo_tickets"; ?>],
                                        }]
                                    });
                                </script>
                            </p>
                        </figure>
                    </div>
                </div>
            </div>
        </div>

<?php }
} ?>
