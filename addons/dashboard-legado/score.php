<?php include('nav/header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center py-2">
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.history.back()">
            <i class="fa-solid fa-circle-chevron-left fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php" class="nav-link" aria-current="page"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php" class="nav-link">
            <i class="fa-solid fa-headset fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="score.php" class="nav-link active">
            <i class="fa-solid fa-ranking-star fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="relcontratos.php" class="nav-link">
            <i class="fa-solid fa-file-signature fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php" class="nav-link">
            <i class="fa-solid fa-gear fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.print()" >
            <i class="fa-solid fa-print fs-4"></i>
            </a>
        </li>

    </ul>

        <?php

            $busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
            $organizar = isset($_GET['organizar']) == '' ? 'arsort' : $_GET['organizar'];
            $registros_por_pagina = isset($_GET['num_registros']) == '' ? '100' : $_GET['num_registros'];

            $lista_organizar = array(
                "arsort" => "Maior Score",
                "asort" => "Menor Score"
            );

            $busca=trim($busca);		
            $palavras_buscas = str_replace(" ","%", $busca);	
        ?>

        <datalist id="sugestoes">
            <?php 
                $query_sugestoes = mysqli_query($link, "SELECT DISTINCT nome FROM sis_cliente WHERE cli_ativado LIKE 's' ORDER BY nome");
                while($s = mysqli_fetch_array($query_sugestoes)){
                    $s_nome = $s['nome'];

                    //Converter UTF-8 do Banco de Dados
                    //$s_nome = html_entity_decode(htmlentities($s_nome, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                    echo "<option value='$s_nome'>";
                }
            ?>
        </datalist>

        <form action="" method="get" id="form_pesquisa" class="no_print">
            <table id="buscar">
                <tr>
                <td class="input_pesquisar">Digite o que procura:</td>
                <td><b>Organizar por:</b></td>
                <td><b>Itens:</b></td>
                <td></td>
                </tr>
                <tr>
                <td><input type="search" class="input_pesquisar" name="busca" 
                placeholder="Busque por nome ou login" value="<?php echo $busca; ?>" list="sugestoes">
                </input>
                </td>
                <td>            
                <select name="organizar" class="select_organizar">
                <?php
                    foreach ($lista_organizar as $key => $value) {
                        $selected = ($organizar == $key) ? "selected=\"selected\"" : null;
                        echo "<option value=\"$key\" $selected >$value</option>";
                }
                ?>
                </select>
                </td>
                <td>
                <select name="num_registros" id="num_registros">
                <?php
                    for($i=100;$i<=1000;$i+=100){
                        $selected = ($i == $registros_por_pagina) ? "selected=\"selected\"" : null;
                        echo "<option value='$i' $selected>$i</option>";
                    }
                ?>
                </select>
                </td>
                <input type="hidden" name="pagina" value="1"></input>
                <td><input type="submit" name="submit" value="Buscar" id="btn_buscar" /></td>
                </tr>
                </table>
        </form>

        <table class="table table-sm table-hover table-striped small">

        <tr class="table-dark">
            <td>Nome Completo</td><td>Plano / Valor</td><td>Login</td><td>Score</td>
        </tr>
		<?php 
		//INICIO PERMISSAO
		if ($acesso_permitido){

            //$starttime = microtime(true);


            function difDatas($data_inicio, $data_fim){
                $d1 = new DateTime($data_inicio);
                $d2 = new DateTime($data_fim);
        
                // Resgata diferen�a entre as datas
                //$dateInterval = ;
                return $d1->diff($d2)->format('%m');
                //echo "</br>";
            }

            $create_table_score_cliente_cfg = mysqli_query($link, "CREATE TABLE IF NOT EXISTS score_cliente_cfg (
                id int NOT NULL AUTO_INCREMENT,
                score_base INT NOT NULL,
                ano_fidelizacao INT NOT NULL,
                tit_venc_0 INT NOT NULL,
                tit_venc_1 INT NOT NULL,
                tit_venc_2 INT NOT NULL,
                tit_venc_3 INT NOT NULL,
                tit_venc_4 INT NOT NULL,
                tit_venc_5 INT NOT NULL,
                tit_venc_6 INT NOT NULL,
                tit_venc_7 INT NOT NULL,
                tit_apos_venc INT NOT NULL,
                tit_dia_venc INT NOT NULL,
                tit_antes_venc INT NOT NULL,
                PRIMARY KEY (id)
            )");
    
            $verifica_tabela_vazia = mysqli_query($link, "SELECT * FROM score_cliente_cfg");
            if (mysqli_num_rows($verifica_tabela_vazia) == 0){
                $ins_table_score_cliente_cfg = mysqli_query($link, "INSERT INTO score_cliente_cfg 
                VALUES ('1', 
                        '100', '11', 
                        '10', '-5','-10','-15','-20','-25','-30','-50',
                        '-5','5','10')");

                if (!$ins_table_score_cliente_cfg){
                    echo mysqli_error($link);
                }
            }

            $query_atual_cfg = mysqli_query($link, "SELECT * FROM score_cliente_cfg ORDER BY id DESC LIMIT 1");
            while($cfg = mysqli_fetch_array($query_atual_cfg)){
    
                $score_base = $cfg['score_base'];
                $ano_fidelizacao = $cfg['ano_fidelizacao'];
    
                $tit_venc_0 = $cfg['tit_venc_0'];
                $tit_venc_1 = $cfg['tit_venc_1'];
                $tit_venc_2 = $cfg['tit_venc_2'];
                $tit_venc_3 = $cfg['tit_venc_3'];
                $tit_venc_4 = $cfg['tit_venc_4'];
                $tit_venc_5 = $cfg['tit_venc_5'];
                $tit_venc_6 = $cfg['tit_venc_6'];
                $tit_venc_7 = $cfg['tit_venc_7'];
    
                $tit_apos_venc = $cfg['tit_apos_venc'];
                $tit_dia_venc = $cfg['tit_dia_venc'];
                $tit_antes_venc = $cfg['tit_antes_venc'];
    
            }


            $query_clientes = mysqli_query($link, "SELECT login, nome, uuid_cliente, tit_vencidos, cadastro, plano FROM sis_cliente WHERE (nome LIKE '%$palavras_buscas%' OR login LIKE '%$palavras_buscas%') AND cli_ativado LIKE 's'");

            if(!$query_clientes){
                echo mysqli_error($link);
            }

            $ano_atual = date('Y');
            while($row = mysqli_fetch_array($query_clientes)){
                $login = $row['login'];
                $nome_completo[$login] = $row['nome'];
                $uuid[$login] = $row['uuid_cliente'];
                $plano[$login] = $row['plano'];

                $titulos_vencidos = $row['tit_vencidos'];
                                
                $cliente_tempo[$login] = 0;
                $score[$login] = $score_base;

                $ano_cadastro = substr($row['cadastro'], -4);

                $cliente_tempo[$login] = ($ano_atual - $ano_cadastro) * $ano_fidelizacao;
                if($cliente_tempo[$login] > $ano_fidelizacao * 5){
                    $cliente_tempo[$login] = $ano_fidelizacao * 5;
                }

                $score[$login] += $cliente_tempo[$login];

                //DEBUG
                //echo "$login - Score 0 - $score[$login]<br>";

                switch ($titulos_vencidos) {
                    case 0:
                        $score[$login] = $score[$login] + $tit_venc_0;
                        break;
                    case 1:
                        $score[$login] = $score[$login] + $tit_venc_1;
                        break;
                    case 2:
                        $score[$login] = $score[$login] + $tit_venc_2;
                        break;
                    case 3:
                        $score[$login] = $score[$login] + $tit_venc_3;
                        break;
                    case 4:
                        $score[$login] = $score[$login] + $tit_venc_4;
                        break;
                    case 5:
                        $score[$login] = $score[$login] + $tit_venc_5;
                        break;
                    case 6:
                        $score[$login] = $score[$login] + $tit_venc_6;
                        break;
                    default:
                        $score[$login] = $score[$login] + $tit_venc_7;
                        break;

                }

                //DEBUG
                //echo "$login - Score 1 - $score[$login]<br>";

                $query_titulos[$login] = mysqli_query($link, "SELECT datapag, datavenc FROM sis_lanc WHERE login = '$login' AND deltitulo = '0' AND datapag IS NOT NULL ORDER BY id DESC  LIMIT 12 ");

                
                if(!$query_titulos[$login]){
                    echo mysqli_error($link);
                }
                while($row2 = mysqli_fetch_array($query_titulos[$login])){
                    //$dif_datas = 0;

                    $data_pagamento = $row2['datapag'];

                    if($data_pagamento != ''){
                        
                        // Data de vencimento normal
                        //$data_vencimento = date('Y/m/d', strtotime($row2['datavenc']));

                        // Data de vencimento com +1 dia para evitar problemas com pagamentos via API
                        $data_vencimento = date('Y/m/d', strtotime("+1 day", strtotime($row2['datavenc'])));
                        

                        $data_vencimento = strtotime($data_vencimento);

                        $data_pagamento = date('Y/m/d', strtotime($row2['datapag']));
                        $data_pagamento = strtotime($data_pagamento);

                        //$data_pagamento_1_dia_apos = date(strtotime("+1 day", $data_vencimento)); // day after original date
                        
                        //echo "Paga: $data_pagamento</br>";
                        //echo "Venc+1:$data_pagamento_1_dia_apos";

                        /*if($data_pagamento == $data_pagamento_1_dia_apos)
                        {
                            echo "Pagamento: $data_pagamento</br>";
                        }*/


                        if ($data_vencimento < $data_pagamento){
                            $score[$login] = $score[$login] + $tit_apos_venc;
                            $tit_pag_apos_venc[$login] += 1;
                        }else if($data_vencimento > $data_pagamento){
                            $score[$login] = $score[$login] + $tit_antes_venc;
                            $tit_pag_antes_venc[$login] += 1;
                        }else{
                            $score[$login] = $score[$login] + $tit_dia_venc;
                            $tit_pag_dia_venc[$login] += 1;
                        }

                    }
                }
            }

        $max_score = $score_base + ($ano_fidelizacao * 5) + $tit_venc_0 + ($tit_antes_venc * 12);

        if ($organizar == "arsort"){
            arsort($score);
        }else{
            asort($score);
        }


        $count = 0;

        foreach ($score as $login_cli => $score_value) {
            echo "
            <tr class=''>
                <td>
                <a href='../../cliente_det$ext_mk?uuid=$uuid[$login_cli]' target='_blank' title='VER CLIENTE: $nome_completo[$login_cli]'>
                $nome_completo[$login_cli]
                </a>
                
                <p class='historico_cliente'>Histórico:
                ";
                for ($i = 0; $i < $tit_pag_apos_venc[$login_cli]; $i++){
                    echo "<img src='img/icon_bola_vermelha.png' title='Titulo pago em atraso' class='icon_p'/>";
                }
                for ($i = 0; $i < $tit_pag_dia_venc[$login_cli]; $i++){
                    echo "<img src='img/icon_bola_branca.png' title='Titulo pago em dia' class='icon_p'/>";
                }
                for ($i = 0; $i < $tit_pag_antes_venc[$login_cli]; $i++){
                    echo "<img src='img/icon_bola_verde.png' title='Titulo pago antecipamente' class='icon_p'/>";
                }
                echo "               
                
                
                </p>

                </td>

                <td>$plano[$login_cli]
                ";

                $query_valor_plano = mysqli_query($link, "SELECT valor FROM sis_plano WHERE nome = '$plano[$login_cli]'");
                while($row = mysqli_fetch_array($query_valor_plano)){
                    $valor_plano = $row['valor'];
                    echo "
                    <p class='historico_cliente'>Mensalidade: R$ $valor_plano</p>";

                }

                echo "
                </td>
                
                <td>$login_cli</td>";
                
                if ($score_value < ($max_score / 3)){
                    echo "<td class='table-danger lead'>$score_value</td>";
                }else if ($score_value >= ($max_score / 3)
                       && $score_value < ($max_score / 2.5)){
                    echo "<td class='table-danger lead'>$score_value</td>";
                }else if ($score_value >= ($max_score / 2.5)
                       && $score_value < ($max_score / 2)){
                    echo "<td class='table-warning lead'>$score_value</td>";
                }else if ($score_value >= ($max_score / 2) 
                       && $score_value < ($max_score / 1.4)){
                    echo "<td class='table-warning lead'>$score_value</td>";
                }else if ($score_value >= ($max_score / 1.4)
                       && $score_value < ($max_score / 1.05)){
                    echo "<td class='table-success lead'>$score_value</td>";
                }else{
                    echo "<td class='table-success lead'>$score_value
                    <img src='img/icon_trofeu_2.png' title='Melhores Clientes' class='icon'/>
                    </td>";
                }
                
            echo "</tr>";
            $count ++;
            if ($count == $registros_por_pagina){
                break;
            }
        }

        /*
        $endtime = microtime(true);

        $tempo_sql = (($endtime - $starttime) * 1000);
        $tempo_sql = number_format($tempo_sql, 2);

        echo "<span class='no_print'><b>Tempo de Consulta no Banco de Dados:</b> $tempo_sql milissegundos<span>";   
        */

        mysqli_close($link); // Finaliza a conexao com o Banco de Dados

     ?>

    


    </table>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

        <figure class="highcharts-figure">
        <div id="container"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 2
            Highcharts.chart('container', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    }
                },
                title: {
                    text: 'Maior Score'
                },
                xAxis: {
                    categories: [
                        <?php
                            /*foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }*/
                            arsort($score);
                            $i = 0;
                            foreach($score as $key => $value){
                                $i++;
                                echo "'$nome_completo[$key]',"; 
                                if ($i>=10){
                                    break;
                                }
                            }
                            //echo "'Score',";
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Score',
                    data: [
                    <?php 
                        arsort($score);
                        $i = 0;
                        foreach($score as $key => $value){
                            $i++;
                            echo "$value,"; 
                            if ($i>=10){
                                break;
                            }
                        }
                    ?>
                    ],
                    color: Highcharts.getOptions().colors[2] // color Green
                }]
            });
        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container2"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 2
            Highcharts.chart('container2', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    }
                },
                title: {
                    text: 'Menor Score'
                },
                xAxis: {
                    categories: [
                        <?php
                            /*foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }*/
                            asort($score);
                            $i = 0;
                            foreach($score as $key => $value){
                                $i++;
                                echo "'$nome_completo[$key]',"; 
                                if ($i>=10){
                                    break;
                                }
                            }
                            //echo "'Score',";
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Score',
                    data: [
                    <?php 
                        asort($score);
                        $i = 0;
                        foreach($score as $key => $value){
                            $i++;
                            echo "$value,"; 
                            if ($i>=10){
                                break;
                            }
                        }
                    ?>
                    ],
                    color: Highcharts.getOptions().colors[8] // color Red

                }]
            });
        </script>
        </p>
    </figure>


    <?php


        } //FIM PERMISSAO

        ?>

        <?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>

    </body>
</html>
