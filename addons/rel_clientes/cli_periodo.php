<!-- 
    RELATï¿½RIO DE CLIENTES
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
        <?php
            /*$mes = 1;
            $tot_meses = 12;*/

            if ($cli_del_cancelalado){
                $query_total_clientes = mysqli_query($link, "SELECT login FROM sis_cliente");
                $query_total_cli_adicionais = mysqli_query($link, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login");

            }else{
                $query_total_clientes = mysqli_query($link, "SELECT login FROM sis_cliente WHERE cli_ativado LIKE 's'");
                $query_total_cli_adicionais = mysqli_query($link, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login AND sis_cliente.cli_ativado LIKE 's'");
            }

            $query_total_cli_adicionais = mysqli_query($link, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login AND sis_cliente.cli_ativado LIKE 's'");

            $total_clientes_atual = mysqli_num_rows($query_total_clientes) + mysqli_num_rows($query_total_cli_adicionais);

            // Total clientes sem adicionais
            $total_cli_atual_no_add = mysqli_num_rows($query_total_clientes);

            // Tabela total de clientes
            $query_table_tot_clientes = mysqli_query($link, "CREATE TABLE IF NOT EXISTS rel_clientes_total_cli (
                ano INT NOT NULL,
                tot_clientes INT NOT NULL,
                tot_cli_no_add INT NOT NULL,
                PRIMARY KEY (ano)
            ) ");

            if (!$query_table_tot_clientes){
                echo mysqli_error($link);
                echo "</br>";
            }

           if ($ano == date('Y')){
                $query_del_tot_clientes_atual = mysqli_query($link, "DELETE FROM rel_clientes_total_cli");
                if (!$query_del_tot_clientes_atual){
                    echo mysqli_error($link);
                    echo "</br>";
                }

                $query_ins_tot_clientes = mysqli_query($link, "INSERT INTO rel_clientes_total_cli (ano, tot_clientes, tot_cli_no_add) VALUES ($ano, $total_clientes_atual, $total_cli_atual_no_add)");

                if (!$query_ins_tot_clientes){
                    echo mysqli_error($link);
                    echo "</br>";
                }
            }

        for($i=23; $i >= 0; $i--){

            //Listagem de Meses e Anos
            $mes = date('m', strtotime("-$i month"));
            $ano = date('Y', strtotime("-$i month"));

            // Regra para exibição do periodo atual
            if($i == 0){
                $mes = date('m');
                $ano = date('Y');
            }

            /*for($mes = $mes;$mes <= $tot_meses; $mes++)
            {
                //$exibe_mes = $mes;
                if ($mes > 0 && $mes<10){
                    $mes = "0$mes"; 
                }*/
            
                $tot_cli_add[$ano.$mes] = 0;
                $tot_cli_reat[$ano.$mes] = 0;
                $tot_cli_canc[$ano.$mes] = 0;
                        
                $query_instalacoes = mysqli_query($link, "SELECT nome FROM sis_cliente WHERE data_ins LIKE '%$ano-$mes%' ORDER BY nome");
                $num_novos[$ano.$mes] = mysqli_num_rows($query_instalacoes);

                $query_add_ins = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE 'inseriu o adicional:%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                $num_add_ins[$ano.$mes] = mysqli_num_rows($query_add_ins);

                $query_reativados = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE '%ativou o cliente%' AND registro NOT LIKE '%desativou%' AND data LIKE '%$mes/$ano%' ORDER BY login");

                $num_reativados[$ano.$mes] = mysqli_num_rows($query_reativados);

                if ($cli_del_cancelalado){
                    $query_desativados = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE 'deletou o cliente % do sistema%' AND data LIKE '%$mes/$ano%' ORDER BY login");
                }else{
                    $query_desativados = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE 
                    (registro LIKE '%desativou o cliente%' AND data LIKE '%$mes/$ano%') 
                    OR 
                    (registro LIKE '%desativou pelo motivo%' AND data LIKE '%$mes/$ano%')
                     ORDER BY login");
                }

                $num_desativados[$ano.$mes] = mysqli_num_rows($query_desativados);

                if ($num_desativados[$ano.$mes] == 0)
                {
                    $query_cli_desativado = mysqli_query($link, "SELECT uuid_cliente, nome, data_desativacao FROM sis_cliente WHERE data_desativacao LIKE '$ano-$mes%' ORDER BY nome");

                    $num_desativados[$ano.$mes] = mysqli_num_rows($query_cli_desativado);
                }

                $query_adicional_del = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE 'deletou o adicional%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                $num_adicional_del[$ano.$mes] = mysqli_num_rows($query_adicional_del);

                // Somatorio dos Resultados
                $tot_cli_add[$ano.$mes] += $num_novos[$ano.$mes] + $num_add_ins[$ano.$mes];
                $tot_cli_reat[$ano.$mes] += $num_reativados[$ano.$mes];

                $tot_cli_add_and_reat[$ano.$mes] = $tot_cli_add[$ano.$mes] + $tot_cli_reat[$ano.$mes];

                $tot_cli_canc[$ano.$mes] += $num_desativados[$ano.$mes] + $num_adicional_del[$ano.$mes];
                
                $tot_cli_saldo[$ano.$mes] = $tot_cli_add_and_reat[$ano.$mes] - $tot_cli_canc[$ano.$mes];

                $tot_cli_saldo_no_add[$ano.$mes] += $num_novos[$ano.$mes] + $num_reativados[$ano.$mes] - $num_desativados[$ano.$mes];

                //$tot_inst_geral[$ano.$mes] = $tot_inst_geral[$ano.$mes] - $tot_cli_canc[$ano.$mes];
                        

            } // Fim Loop

            /*echo "<pre>";
            //print_r($num_desativados);
            echo "</pre>";

            echo "<pre>";
            print_r($tot_inst_geral);
            echo "</pre>";*/


            $mes = $mes -1;

            //print_r($saldo_cli_ano);
            //echo "</br>";
            //echo array_sum($tot_cli_add_and_reat);

            // Total Clientes
            $tot_cli_ativados = array_sum($tot_cli_add_and_reat);
            $tot_cli_desativados = array_sum($tot_cli_canc);
            $saldo_cli_ano = $tot_cli_ativados - $tot_cli_desativados;

            //Total Clientes sem  Adicionais
            $tot_cli_inst_no_add = array_sum($num_novos) + array_sum($num_reativados);
            $tot_cli_canc_no_add = array_sum($num_desativados);
            $saldo_cli_ano_no_add = $tot_cli_inst_no_add - $tot_cli_canc_no_add;   
            
            // Total de Clientes Mensais

        
            // debug
            /*debug($tot_cli_add_and_reat);
            debug($tot_cli_canc);
            debug($saldo_cli_ano);*/

            
        ?>

        

        <script src="js/highcharts.js"></script>
        <script src="js/exporting.js"></script>

        <figure class="highcharts-figure">
            <div id="container2"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container2', {
            chart: {
                type: 'column',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } },
                line: {
                dataLabels: { 
                    enabled: true 
                }, enableMouseTracking: false }, 
            },
            title: {
                text: '<?php echo "Balanço Clientes $ano"; ?>'
            },
            xAxis: {
                categories: [
                    <?php
                        foreach($tot_cli_add_and_reat as $key => $value){
                            echo "$key,"; 
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
                        foreach($tot_cli_add_and_reat as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ],
                color: Highcharts.getOptions().colors[2]
            }, {
                name: 'Cancelamentos',
                data: [
                    <?php 
                        foreach($tot_cli_canc as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ],
                color: Highcharts.getOptions().colors[8]
            }, {
                name: 'Saldo',
                data: [
                    <?php 
                        foreach($tot_cli_saldo as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ],
                color: Highcharts.getOptions().colors[3]
            }]
        });

        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
            <div id="container6"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container6', {
            chart: {
                type: 'line',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } },
                line: {
                dataLabels: { 
                    enabled: true 
                }}, 
            },
            title: {
                text: '<?php echo "Instalações por Mês - Clientes $ano"; ?>'
            },
            xAxis: {
                categories: [
                    <?php
                            foreach($tot_cli_add_and_reat as $key => $value){
                                echo "$key,"; 
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
                        foreach($tot_cli_add_and_reat as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ],
            }]
        });

        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
            <div id="container7"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container7', {
            chart: {
                type: 'line',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } },
                line: {
                dataLabels: { 
                    enabled: true 
                }}, 
            },
            title: {
                text: '<?php echo "Cancelamentos por Mês - Clientes $ano"; ?>'
            },
            xAxis: {
                categories: [
                    <?php
                            foreach($tot_cli_canc as $key => $value){
                                echo "$key,"; 
                            }
                        ?>
                ]
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Cancelamentos',
                data: [
                    <?php 
                        foreach($tot_cli_canc as $key => $value){
                            echo "$value,"; 
                        }
                    ?>
                ],
                color: Highcharts.getOptions().colors[8]
            }]
        });

        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
            <div id="container8"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container8', {
            chart: {
                type: 'line',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } },
                line: {
                dataLabels: { 
                    enabled: true 
                }}, 
            },
            title: {
                text: '<?php echo "Saldo por Mês - Clientes $ano"; ?>'
            },
            xAxis: {
                categories: [
                    <?php
                        foreach($tot_cli_saldo as $key => $value){
                            echo "$key,"; 
                        }                            
                        ?>
                ]
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Saldo',
                data: [
                    <?php 
                        foreach($tot_cli_saldo as $key => $value){
                            echo "$value,"; 
                        }   
                    ?>
                ],
                color: Highcharts.getOptions().colors[3]
            }]
        });

        </script>
        </p>
    </figure>
    
    <figure class="highcharts-figure">
            <div id="container3"></div>
            <p class="highcharts-description">
            <script>

        Highcharts.chart('container3', {
            chart: {
                type: 'column',
            },
            plotOptions: {
                column: { dataLabels: { enabled: true } }
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

    <?php 
        if($_GET['mes'] == "per"){
            include('cli_total_per.php');
        }else{
            include('cli_total.php'); 
        }
    ?>

