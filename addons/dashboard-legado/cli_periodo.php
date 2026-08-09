<?php
            /*$mes = 1;
            $tot_meses = 12;*/
/*
            if ($cli_del_cancelalado){
                $query_total_clientes = mysqli_query($conn, "SELECT login FROM sis_cliente");
                $query_total_cli_adicionais = mysqli_query($conn, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login");

            }else{
                $query_total_clientes = mysqli_query($conn, "SELECT login FROM sis_cliente WHERE cli_ativado LIKE 's'");
                $query_total_cli_adicionais = mysqli_query($conn, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login AND sis_cliente.cli_ativado LIKE 's'");
            }

            $query_total_cli_adicionais = mysqli_query($conn, "SELECT sis_cliente.login, sis_adicional.login FROM sis_cliente, sis_adicional WHERE sis_cliente.login = sis_adicional.login AND sis_cliente.cli_ativado LIKE 's'");

            $total_clientes_atual = mysqli_num_rows($query_total_clientes) + mysqli_num_rows($query_total_cli_adicionais);

            // Total clientes sem adicionais
            $total_cli_atual_no_add = mysqli_num_rows($query_total_clientes);

            // Tabela total de clientes
            $query_table_tot_clientes = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rel_clientes_total_cli (
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
                $query_del_tot_clientes_atual = mysqli_query($conn, "DELETE FROM rel_clientes_total_cli");
                if (!$query_del_tot_clientes_atual){
                    echo mysqli_error($link);
                    echo "</br>";
                }

                $query_ins_tot_clientes = mysqli_query($conn, "INSERT INTO rel_clientes_total_cli (ano, tot_clientes, tot_cli_no_add) VALUES ($ano, $total_clientes_atual, $total_cli_atual_no_add)");

                if (!$query_ins_tot_clientes){
                    echo mysqli_error($link);
                    echo "</br>";
                }
            }*/

        for($c=$per_meses-1; $c >= 0; $c--){

            //Listagem de Meses e Anos
            $mes = date('m', strtotime("-$c month"));
            $ano = date('Y', strtotime("-$c month"));

            // Regra para exibição do periodo atual
            if($c == 0){
                $mes_cap = date('m');
                $ano_cap = date('Y');
            }

            $ano_mes = $ano.'-'.$mes;

            /*for($mes = $mes;$mes <= $tot_meses; $mes++)
            {
                //$exibe_mes = $mes;
                if ($mes > 0 && $mes<10){
                    $mes = "0$mes"; 
                }*/
            
                $tot_cli_add[$ano_mes] = 0;
                $tot_cli_reat[$ano_mes] = 0;
                $tot_cli_canc[$ano_mes] = 0;
                        
                $query_instalacoes = mysqli_query($conn, "SELECT nome FROM sis_cliente WHERE data_ins LIKE '%$ano-$mes%' ORDER BY nome");
                $num_novos[$ano_mes] = mysqli_num_rows($query_instalacoes);

                $query_add_ins = mysqli_query($conn, "SELECT registro FROM sis_logs WHERE registro LIKE 'inseriu o adicional:%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                $num_add_ins[$ano_mes] = mysqli_num_rows($query_add_ins);

                $query_reativados = mysqli_query($conn, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE '%ativou o cliente - IP:%' AND registro NOT LIKE '%desativou%' AND data LIKE '%$mes/$ano%' ORDER BY login");

                $num_reativados[$ano_mes] = mysqli_num_rows($query_reativados);

                if ($cli_del_cancelalado){
                    $query_desativados = mysqli_query($conn, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE 'deletou o cliente % do sistema%' AND data LIKE '%$mes/$ano%' ORDER BY login");
                }else{
                    $query_desativados = mysqli_query($conn, "SELECT DISTINCT login FROM sis_logs WHERE 
                    (registro LIKE '%desativou o cliente - IP%' AND data LIKE '%$mes/$ano%') 
                    OR 
                    (registro LIKE '%desativou pelo motivo%' AND data LIKE '%$mes/$ano%')
                     ORDER BY login");
                }

                $num_desativados[$ano_mes] = mysqli_num_rows($query_desativados);

                if ($num_desativados[$ano_mes] == 0)
                {
                    $query_cli_desativado = mysqli_query($conn, "SELECT uuid_cliente, nome, data_desativacao FROM sis_cliente WHERE data_desativacao LIKE '$ano-$mes%' ORDER BY nome");

                    $num_desativados[$ano_mes] = mysqli_num_rows($query_cli_desativado);
                }

                $query_adicional_del = mysqli_query($conn, "SELECT registro FROM sis_logs WHERE registro LIKE 'deletou o adicional%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                $num_adicional_del[$ano_mes] = mysqli_num_rows($query_adicional_del);

                // Somatorio dos Resultados
                $tot_cli_add[$ano_mes] += $num_novos[$ano_mes] + $num_add_ins[$ano_mes];
                $tot_cli_reat[$ano_mes] += $num_reativados[$ano_mes];

                $tot_cli_add_and_reat[$ano_mes] = $tot_cli_add[$ano_mes] + $tot_cli_reat[$ano_mes];

                $tot_cli_canc[$ano_mes] += $num_desativados[$ano_mes] + $num_adicional_del[$ano_mes];
                
                $tot_cli_saldo[$ano_mes] = $tot_cli_add_and_reat[$ano_mes] - $tot_cli_canc[$ano_mes];

                $tot_cli_saldo_no_add[$ano_mes] += $num_novos[$ano_mes] + $num_reativados[$ano_mes] - $num_desativados[$ano_mes];

                //$tot_inst_geral[$ano_mes] = $tot_inst_geral[$ano_mes] - $tot_cli_canc[$ano_mes];
                        

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

