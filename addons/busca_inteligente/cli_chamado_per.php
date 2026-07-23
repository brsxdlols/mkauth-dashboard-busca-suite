        <?php

            $string_per = "";
 
            if ($_GET['assunto'] == "TODOS" || $_GET['assunto'] == ""){
                $assunto = "%";
            }else{
                $assunto = $_GET['assunto'];
            }
            //$assunto = $_GET['assunto'] == "TODOS" ? "%" : $_GET['assunto'];
            
            $query_motivo_chamado = mysqli_query($conn, "SELECT DISTINCT assunto FROM sis_suporte ORDER BY assunto");
            $motivo_chamado["%"] = "TODOS";
            while ($row2 = mysqli_fetch_array($query_motivo_chamado)){
                $motivo_chamado[$row2['assunto']] = $row2['assunto'];
            }

            if ($_GET['tecnico'] == "TODOS" || $_GET['tecnico'] == ""){
                $tecnico = "%";
            }else{
                $tecnico = $_GET['tecnico'];
            }
           
            $query_tecnico_chamado = mysqli_query($conn, "SELECT id,nome FROM sis_func ORDER BY nome");

            
            $nome_tecnico["%"] = "TODOS";
            $id_tecnico["%"] = "%";
            while ($row3 = mysqli_fetch_array($query_tecnico_chamado)){
                $nome_tecnico[$row3['id']] = $row3['nome'];
                $id_tecnico[$row3['nome']] = $row3['id'];
            }
            
        ?>

        <!--<table id="chamados_table">
        <tr class="linha_titulo">
            <td>Cliente</td>
            <td>Assunto</td>
        </tr>-->

        <?php   
            unset($motivo_chamado['%']); //Retirar item % da Array
            unset($nome_tecnico['%']);
            $nome_tecnico[0] = "Nenhum Técnico";
            //print_r($nome_tecnico);


            for($i=$per_meses-1; $i >= 0; $i--){

                //Listagem de Meses e Anos
                $mes_cap = date('m', strtotime("-$i month"));
                $ano_cap = date('Y', strtotime("-$i month"));
    
                // Regra para exibição do periodo atual
                if($i == 0){
                    $mes_cap = date('m');
                    $ano_cap = date('Y');
                }

                $ano_mes = "$ano_cap-$mes_cap";

                /*echo "
                <tr class='linha_titulo'>
                <td colspan='2'>$ano_mes</td>
                </tr>
                ";*/
         
                $query_chamados_[$ano_mes] = mysqli_query($conn, "SELECT assunto, nome, login, abertura, fechamento, status, chamado, tecnico
                FROM sis_suporte 
                WHERE 
                
                (abertura LIKE '$ano_cap-$mes_cap%' AND fechamento LIKE '$ano_cap-$mes_cap%') OR
                (abertura LIKE '$ano_cap-$mes_cap%') 
                /*OR
                (fechamento LIKE '$ano_mes%' AND status = 'fechado')) AND 
                assunto LIKE '%$assunto%' AND
                (tecnico LIKE '$id_tecnico[$tecnico]' OR tecnico IS NULL)*/
                ORDER BY assunto, abertura");

                //echo $id_tecnico[$tecnico];
                //echo "</br>";

                //echo $query_chamados_[$ano_mes];

                $cont_ticket_aberto_[$ano_mes] = 0;
                $cont_ticket_fechado_[$ano_mes] = 0;
                $saldo_tickets_[$ano_mes] = 0;

                while($row = mysqli_fetch_array($query_chamados_[$ano_mes])){
                    $ticket_assunto_[$ano_mes] = $row['assunto'];
                    $ticket_nome_cli_[$ano_mes] = $row['nome'];
                    
                    $ticket_abertura = $row['abertura'];
                    $ticket_abertura = date('Y-m', strtotime($ticket_abertura));
                    
                    $ticket_fechamento = $row['fechamento'];
                    if ($ticket_fechamento != ''){
                        $ticket_fechamento = date('Y-m', strtotime($ticket_fechamento));
                    }

                    $ticket_abertura_[$ano_mes] = $row['abertura'];
                    if ($ticket_abertura_[$ano_mes]  != ''){
                        $ticket_abertura_[$ano_mes]  = date('Y-m', strtotime($ticket_abertura_[$ano_mes]));
                    }
                    $ticket_fechamento_[$ano_mes] = $row['fechamento'];
                    if ($ticket_fechamento_[$ano_mes]  != ''){
                        $ticket_fechamento_[$ano_mes]  = date('Y-m', strtotime($ticket_fechamento_[$ano_mes]));
                    }

                    $ticket_status = $row['status']; // Usado no contador
                    $ticket_status_[$ano_mes] = $row['status'];
                    $ticket_tecnico_[$ano_mes] = $row['tecnico'];
                    $ticket_chamado_[$ano_mes] = $row['chamado'];
                    $ticket_login_[$ano_mes] = $row['login'];
        
                    //Tickets por Assunto
                    foreach ($motivo_chamado as $key => $value) {
                        if ($ticket_assunto_[$ano_mes] == $value && $ticket_abertura_[$ano_mes] == "$ano_mes"){
                            $assunto_ticket_aberto_[$ano_mes][$value] += 1;

                            $tot_assunto_ticket_aberto_[$value] += 1;
                        }
                        if ($ticket_assunto_[$ano_mes] == $value && $ticket_status_[$ano_mes] == "fechado" 
                        && $ticket_fechamento_[$ano_mes] == "$ano_mes"){
                            $assunto_ticket_fechado_[$ano_mes][$value] += 1;
                            
                        }

                        if ($ticket_assunto_[$ano_mes] == $value){
                            $assunto_ticket_saldo_[$ano_mes][$value] = $assunto_ticket_aberto_[$ano_mes][$value] - $assunto_ticket_fechado_[$ano_mes][$value];
                        }

                        if (!isset($assunto_ticket_aberto_[$ano_mes][$value]) && !isset($assunto_ticket_fechado_[$ano_mes][$value])){

                        }else{
                            if(!isset($assunto_ticket_aberto_[$ano_mes][$value])){
                                $assunto_ticket_aberto_[$ano_mes][$value] = 0;
                            }
                            if(!isset($assunto_ticket_fechado_[$ano_mes][$value])){
                                $assunto_ticket_fechado_[$ano_mes][$value] = 0;
                            }
                        }
                    }
                    
                    //Tickets por T�cnico
                    foreach ($nome_tecnico as $key => $value) {
                        
                        if ($ticket_tecnico_[$ano_mes] == $key && $ticket_abertura_[$ano_mes] == "$ano_mes"){
                            $tecnico_ticket_aberto_[$ano_mes][$key] += 1;

                            $tot_tecnico_ticket_aberto_[$value] += 1;

                        }
                        if ($ticket_tecnico_[$ano_mes] == $key && $ticket_status_[$ano_mes] == "fechado"
                        && $ticket_fechamento_[$ano_mes] == "$ano_mes"){
                            $tecnico_ticket_fechado_[$ano_mes][$key] += 1;
                        }
                        
                        if ($ticket_tecnico_[$ano_mes] == $key){
                            $tecnico_ticket_saldo_[$ano_mes][$key] = $tecnico_ticket_aberto_[$ano_mes][$key] - $tecnico_ticket_fechado_[$ano_mes][$key];
                        }

                        if (!isset($tecnico_ticket_aberto_[$ano_mes][$key]) && !isset($tecnico_ticket_fechado_[$ano_mes][$key])){

                        }else {
                            if(!isset($tecnico_ticket_aberto_[$ano_mes][$key])){
                                $tecnico_ticket_aberto_[$ano_mes][$key] = 0;
                            }
                            if(!isset($tecnico_ticket_fechado_[$ano_mes][$key])){
                                $tecnico_ticket_fechado_[$ano_mes][$key] = 0;
                            }
                        }
                    }

                   

                   /* echo "
                    <tr class='linha_resultados'>
                        <td>
                        <b>Nome: </b><a href='../busca_inteligente/index.php?busca=$ticket_login_[$ano_mes]' target='_blank'>$ticket_nome_cli_[$ano_mes]</a></br>
                        <b>Chamado: </b><a href='../../suporte_info$ext_mk?login=$ticket_login_[$ano_mes]&chamado=$ticket_chamado_[$ano_mes]' target='_blank'>$ticket_chamado_[$ano_mes]</a>
                        </td>
                        <td>$ticket_assunto_[$ano_mes]";

                        if ($ticket_status_[$ano_mes] == "fechado"){
                            echo "</br><img src='img/icon_ticket_fechado2.png' title='Chamado Fechado' class='icon'/>";
                        }
                        echo"
                        </td></tr>
                    ";*/

                    if ($ticket_abertura == "$ano_mes"){
                        $cont_ticket_aberto += 1;
                    }
                    if ($ticket_status == "fechado" && $ticket_fechamento == "$ano_mes"){
                        $cont_ticket_fechado += 1;
                    }

                    

                }

                $saldo_tickets = $cont_ticket_aberto - $cont_ticket_fechado;
                
                /*$total_tickets_abertos += $cont_ticket_aberto_[$ano_cap.$mes_cap];
                $total_tickets_fechados += $cont_ticket_fechado_[$ano_cap.$mes_cap];

                $total_saldo_ticket = $total_tickets_abertos - $total_tickets_fechados;*/

        }

        //debug($assunto_ticket_aberto_); 
        //echo "</table>";

        ?>
           
        
        
        

 