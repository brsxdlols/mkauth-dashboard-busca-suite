<!-- 
    RELAT�RIO DE FATURAMENTO
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
    if(isset($_SESSION['MM_Usuario'])){
        echo '<html lang="pt-BR">'; // Fix vers�o antiga MK-AUTH
    }else{
        echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
    }
?>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="utf-8">
        <title>MK - AUTH :: <?php echo $Manifest->{'name'}." - V ". $Manifest->{'version'};  ?></title>

        <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
        <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />

        <script src="../../scripts/jquery.js"></script>
        <script src="../../scripts/mk-auth.js"></script>

        <style type="text/css">
            #tabela_fat {float:left; min-width: 500px;}
            .buscar { width: 100%; }
            #form_graf { width: 100%; }

            #btn_buscar, #form_graf input, #form_graf select { padding: 10px; font-size:18px; }
            #btn_buscar { background: Navy; color:#FFF; border: 1.5px solid Navy;}

            .linha_titulo {font-weight: bold; text-align:center; background: Navy; font-size: 14px; }
            .linha_titulo td { width: auto; padding: 4px 3px; color: #FFF; } 

            .linha_resultados { }
            .linha_resultados:nth-last-child(2n) {background:#E0E0F8;}
            .linha_resultados td{ width: auto; font-size: 13px; padding: 4px 3px;}

            .entrada { font-weight: bold; color: DarkGreen; }
            .saida { font-weight: bold; color: DarkRed; }

            .nome_cliente { font-weight: bold;  }
            .nome_cliente a:hover { text-decoration:underline;}

            .center { text-align:center;  }

            #align { text-align: center;}

            #prev_faturamento { width: auto; height: auto; background:#209cee; color:#FFF; float:left; margin: 2px 10px; padding: 10px 40px; }
            #prev_faturamento #prev_titulo { font-weight: bold; font-size: 15px; }
            #prev_faturamento #prev_valor { font-size: 30px; }
            #prev_faturamento #prev_porc_recebido { font-size: 15px; font-weight: bold; }
            
            #container, #container2 {
            float:left;
            width: 480px;
            height: 400px; 
            }
            

            .highcharts-figure, .highcharts-data-table table {
                width:100%;
                margin: 1em auto;
            }
            .icon { width: 20px; height: 20px; vertical-align: middle;}
            .icon_add { width: 40px; }

            #totais { display:block; }
            .tot { width: 33%; height: auto; border-radius: 10px; padding: 5px 20px; margin: 5px 0px; display: inline-flex; color:#FFF; text-align: left; align: middle;}
            .red { background: #DC143C; }
            .blue { background: #1E90FF; }
            .green { background: #90EE90; color:#000; }
            .tot_values{ font-size: 30px; font-weight: 500; margin:0; padding:0; }
            .tit_small { font-size: 13px;  }
        </style>

                    
        <script type="text/javascript">
                function abrirJanela(pagina, largura, altura) {
                // Definindo centro da tela
                var esquerda = (screen.width - largura)/2;
                var topo = (screen.height - altura)/2;
                // Abre a nova janela
                minhaJanela = window.open(pagina,'','height=' + altura + ', width=' + largura + ', top=' + topo + ', left=' + esquerda);
                }
        </script>

</head>
<body>



<?php include('../../topo.php'); ?>



    <nav class="breadcrumb has-bullet-separator is-centered" aria-label="breadcrumbs">
        <ul>
            <li class="is-active">
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - DIÁRIO";?></a>
            </li>
            <li><a href="index.php">ANUAL</a></li>
            <li><a href="graf_det.php">DETALHADO</a></li>
            <li><a href="graf_ramal_cli.php">RAMAL 1</a></li>
            <li><a href="graf_ramal.php">RAMAL 2</a></li>
            <li><a href="graf_5a.php">GRAF 5 ANOS</a></li>
            <li><a href="graf_nome.php">NOME</a></li>
            <li><a href="graf_cidade_cli.php">CIDADE</a></li>
            <li><a href="graf_bairro_cli.php">BAIRRO</a></li>
            <li><a href="graf_periodo.php">ULT MESES</a></li>
            <li>
                <img src="img/icon_print.png" class="icon" title="Imprimir" onClick="window.print()"></img>
            </li>
        </ul>
    </nav>          

    <?php include('config.php'); ?>

    <?php 
        if ($acesso_permitido)
        {
    ?>
    
    <?php
        
        $data_atual = date('Y-m-d');
        $data_inicial = isset($_GET['data_inicial']) == '' ? $data_atual : $_GET['data_inicial'];
        //echo $data_inicial;
        //echo "</br>";
        $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];

        $historico_busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
        $historico_busca=trim($historico_busca);		
        $palavra_busca = str_replace(" ","%", $historico_busca);

        $usuario = isset($_GET['usuario']) == "" ? "%" : $_GET['usuario'];
        $plano_de_contas = isset($_GET['plano_de_contas']) == "" ? "%" : $_GET['plano_de_contas'];

        $mes = date('m', strtotime($data_inicial));
        $ano = date('Y', strtotime($data_inicial));

        //echo $data_final;

        //Função parar verificação de String iniciada por um termo
        // Case sensitive
        function startsWith($string, $startString) { 
            $len = strlen($startString); 
            return (strtolower(substr($string, 0, $len)) === $startString); 
        }

        $tot_entrada = 0;
        $tot_saida = 0;
        $estorno_entrada = 0;
        $estorno_saida = 0;
    ?>


    <form action="" method="get">
        <table id="form_graf">
            <tr>
                <td><b>Data Inicial:</b></td>
                <td><b>Data Final:</b></td>
                <td class="buscar"><b>Pesquisar no histórico:</b></td>
                <td><b>Plano de Contas:</b></td>
                <td><b>Usuário:</b></td>
                <td></td>
            </tr>
            <tr>
                <td><input type="date" name="data_inicial" value="<?php echo $data_inicial; ?>"/></td>
                <td><input type="date" name="data_final" value="<?php echo $data_final; ?>"/></td>
                <td><input type="text" name="busca" class="buscar" value="<?php echo $historico_busca; ?>"/></td>
                <td>
                    <select name="plano_de_contas">
                    <?php
                        $query_list_plano_de_contas = mysqli_query($link, "SELECT DISTINCT planodecontas FROM sis_caixa ORDER BY usuario");
                        if(!$query_list_plano_de_contas){
                            echo mysqli_error($link);
                        }
                        $list_plano_de_contas['%'] = "Todos";
                        while($row = mysqli_fetch_array($query_list_plano_de_contas)){
                            $list_plano_de_contas[$row['planodecontas']] = $row['planodecontas'];
                        }

                        foreach ($list_plano_de_contas as $key => $value) {
                            $selected = ($plano_de_contas == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                    ?>

                    </select>
                </td>
                <td>
                    <select name="usuario">
                    <?php
                        $query_list_usuario = mysqli_query($link, "SELECT DISTINCT login FROM sis_acesso WHERE ativo = 'sim' ORDER BY login");
                        if(!$query_list_usuario){
                            echo mysqli_error($link);
                        }
                        $list_usuario['%'] = "Todos";
                        $list_usuario['mk-bot'] = "mk-bot";
                        while($row = mysqli_fetch_array($query_list_usuario)){
                            $list_usuario[$row['login']] = $row['login'];
                        }

                        //print_r($list_usuario);

                        foreach ($list_usuario as $key => $value) {
                            $selected = ($usuario == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                    ?>

                    </select>
                </td>
                <td><input type="submit" name="submit" id="btn_buscar" value="OK" /></td>
            </tr>
        </table>
    </form>

    <?php
    
    echo "
        <table id='tabela_fat'>
        <tr class='linha_titulo'>
            <td>Data / User</td>
            <td>Descrição</td>
            <td>Entrada</td>
            <td>Saída</td>
        </tr>
    ";

    // Saldo Dia Anterior
    $data_query_saldo = date('Y-m-d', strtotime('-1 days', strtotime($data_inicial)));
    
        //echo $data_query_saldo;
    $query_saldo_anterior = mysqli_query($link, "SELECT (sum(entrada) - sum(saida)) saldo_geral  FROM sis_caixa WHERE data <= '$data_query_saldo 23:59:59'");

    $data_query_saldo = date('d/m/Y', strtotime($data_query_saldo));

    while($row = mysqli_fetch_array($query_saldo_anterior)){
        $saldo_dia_anterior = $row['saldo_geral'];
        echo "
        <tr class='linha_resultados'>
        <td>$data_query_saldo - 23:59</td>
        <td><b>Saldo anterior</b></td>";
        if($saldo_dia_anterior > 0){
            echo "<td colspan='2' class='entrada center'>R$ $saldo_dia_anterior</td>";
        }else{
            echo "<td colspan='2' class='saida center'>R$ $saldo_dia_anterior</td>";
        }
        echo "</tr>";
    }
        
    //Gr�fico Di�rio
    $query_mysql = mysqli_query($link, "SELECT c.uuid_caixa, c.usuario, c.data, c.historico, c.complemento, c.entrada, c.saida, c.planodecontas /*, l.formapag */
    FROM sis_caixa c 
    /*LEFT JOIN sis_lanc l*/
    WHERE c.historico LIKE '%$palavra_busca%' AND c.data BETWEEN '$data_inicial' AND '$data_final 23:59:59' AND c.planodecontas LIKE '$plano_de_contas' AND c.usuario LIKE '$usuario' ORDER BY c.data");

    while ($row = mysqli_fetch_array($query_mysql)){
        //print_r($row);
        //echo "</br>";
        $uuid_caixa = $row['uuid_caixa'];
        $data_mov = $row['data'];
        $data_mov = date('d/m/Y - H:i', strtotime($data_mov));
        $user_mov = $row['usuario'];
        $historico = $row['historico'];
        $plano_contas = $row['planodecontas'];

        // $forma_pag_cliente = $row['formapag'];
        //Converter UTF-8 do Banco de Dados
        //$historico = html_entity_decode(htmlentities($historico, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

        $complemento = $row['complemento'];
        
        $entrada = $row['entrada'];
        $saida = $row['saida'];

        if(startsWith($historico, "estorno")){
            $estorno_entrada += $entrada;
            $estorno_saida += $saida;
            /*echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</td>
                <td>$historico</td>
                <td>$entrada</td>
                <td>$saida</td>
            </tr>
            ";*/
        }
        if (startsWith($historico, "pagamento do titulo ")){
            
            $num_titulo_cont = stripos($historico, " com");
            $num_titulo = substr($historico, 20, $num_titulo_cont - 20);
           
            if(startsWith($num_titulo, "0")){
                $num_titulo = substr($num_titulo, 1);
            }
            
            $query_num_titulo = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE (SELECT login FROM sis_lanc WHERE id LIKE '$num_titulo')");
            while ($row2 = mysqli_fetch_array($query_num_titulo)){
                $nome_por_num_titulo = $row2['nome'];
                
                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            echo "
            <tr class='linha_resultados'>   
                <td>
                $data_mov</br>
                <a href='#' onclick=\"javascript:abrirJanela('../../caixa_alt$ext_mk?uuid=$uuid_caixa', 530, 600);\"><img src='img/icon_edit.png' title='Editar' class='icon'/></a>
                $user_mov - <b>$plano_contas</b>
                </td>
                <td>$historico </br>
                <span class='nome_cliente'><a href='../../cliente_det$ext_mk?uuid=$uuid_cliente' target='_blank' title='VER CLIENTE: $nome_por_num_titulo'>
                $nome_por_num_titulo</a></span></td>
                <td class='entrada'>R$ $entrada</td>    
                <td class='saida'>R$ $saida</td>
            </tr>";
        }
        /*else if (startsWith($historico, "tarifa paga no retorno do gerencianet")){
            $saida = 0.00; // Taxa do Gerencianet
            echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</br>$user_mov</td>
                <td>$historico </br>
                <td class='entrada'>R$ $entrada</td>    
                <td class='saida'>R$ $saida</td>
            </tr>";
        }*/
        else if (startsWith($historico, "recebimento do titulo ")){
            $num_titulo_cont = stripos($historico, " / ");
            $num_titulo = substr($historico, 22, $num_titulo_cont - 22);
            //echo "$num_titulo</br>";
           
            if(startsWith($num_titulo, "0")){
                $num_titulo = substr($num_titulo, 1);
            }

            $query_num_titulo2 = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE (SELECT login FROM sis_lanc WHERE id LIKE '$num_titulo')");
            while ($row2 = mysqli_fetch_array($query_num_titulo2)){
                $nome_por_num_titulo = $row2['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            $query_forma_pag = mysqli_query($link, "SELECT formapag FROM sis_lanc WHERE id = '$num_titulo'");
            while($row3 = mysqli_fetch_array($query_forma_pag)){
                $forma_pag_cliente = $row3['formapag'];
            }

            echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</br>
                <a href='#' onclick=\"javascript:abrirJanela('../../caixa_alt$ext_mk?uuid=$uuid_caixa', 530, 600);\"><img src='img/icon_edit.png' title='Editar' class='icon'/></a>
                $user_mov - <b>$forma_pag_cliente</b>
                </td>
                <td>$historico </br>
                <span class='nome_cliente'><a href='../../cliente_det$ext_mk?uuid=$uuid_cliente' target='_blank' title='VER CLIENTE: $nome_por_num_titulo'>
                $nome_por_num_titulo</a></span></td>
                <td class='entrada'>R$ $entrada</td>
                <td class='saida'>R$ $saida</td>
            </tr>";   
        }
        else if (startsWith($historico, "recebimento de titulo on-line, cliente: ")){
            $login_pag = substr($historico, 40);
            //echo "login_pag = $login_pag</br>";
            //echo "$num_titulo</br>";
           
            $query_num_titulo3 = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE '$login_pag'");
            while ($row2 = mysqli_fetch_array($query_num_titulo3)){
                $nome_por_num_titulo = $row2['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</br>
                <a href='#' onclick=\"javascript:abrirJanela('../../caixa_alt$ext_mk?uuid=$uuid_caixa', 530, 600);\"><img src='img/icon_edit.png' title='Editar' class='icon'/></a>
                $user_mov - <b>$plano_contas</b>
                </td>
                <td>$historico </br>
                <span class='nome_cliente'><a href='../../cliente_det$ext_mk?uuid=$uuid_cliente' target='_blank' title='VER CLIENTE: $nome_por_num_titulo'>
                $nome_por_num_titulo</a></span></td>
                <td class='entrada'>R$ $entrada</td>
                <td class='saida'>R$ $saida</td>
            </tr>";   
        }
        else if(startsWith($historico, "pagamento da conta")){
            echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</br>
                <a href='#' onclick=\"javascript:abrirJanela('../../caixa_alt$ext_mk?uuid=$uuid_caixa', 530, 600);\"><img src='img/icon_edit.png' title='Editar' class='icon'/></a>
                $user_mov - <b>$plano_contas</b>
                </td>
                <td><b>$historico</b></td>
                <td class='entrada'>R$ $entrada</td>
                <td class='saida'>R$ $saida</td>
            </tr>
            ";
        }
        else{
            echo "
            <tr class='linha_resultados'>   
                <td>$data_mov</br>
                <a href='#' onclick=\"javascript:abrirJanela('../../caixa_alt$ext_mk?uuid=$uuid_caixa', 530, 600);\"><img src='img/icon_edit.png' title='Editar' class='icon'/></a>
                $user_mov - <b>$plano_contas</b>
                </td>
                <td>$historico</td>
                <td class='entrada'>R$ $entrada</td>
                <td class='saida'>R$ $saida</td>
            </tr>
        ";
        }

        $tot_entrada += $entrada;
        //$tot_saida += $saida + $taxa_cobranca;
        $tot_saida += $saida;

    
    }



    /*$query_prev_fat = mysqli_query($link, "SELECT valor FROM sis_lanc WHERE datavenc BETWEEN '$data_inicial' AND '$data_final' AND deltitulo LIKE '0'");

    if ($query_prev_fat){
        echo mysql_error();
    }
    
    while($row3 = mysqli_fetch_array($query_prev_fat)){
        $valor_previsto += $row3['valor'];

        
        //echo "$valor_previsto</br>";
    }*/

    $tot_entrada = $tot_entrada - $estorno_entrada - $estorno_saida;

    $tot_saida = $tot_saida - $estorno_entrada - $estorno_saida;

    $saldo = $tot_entrada - $tot_saida;

    echo "
    <tr class='linha_resultados'>
        <td colspan='2' id='align'><b>TOTAL - SEM ESTORNOS</b></td>
        <td class='entrada'>R$ $tot_entrada</td>
        <td class='saida'>R$ $tot_saida</td>
    </tr>";


        
    $query_saldo_atual = mysqli_query($link, "SELECT (sum(entrada) - sum(saida)) saldo_geral  FROM sis_caixa WHERE data <= '$data_final 23:59:59'");

    $data_query_saldo = date('d/m/Y', strtotime($data_final));

    while($row = mysqli_fetch_array($query_saldo_atual)){
        $saldo_dia_atual = $row['saldo_geral'];
        echo "
        <tr class='linha_resultados'>
        <td>$data_query_saldo - 23:59</td>
        <td><b>Saldo atual</b></td>";
        if($saldo_dia_atual > 0){
            echo "<td colspan='2' class='entrada center'>R$ $saldo_dia_atual</td>";
        }else{
            echo "<td colspan='2' class='saida center'>R$ $saldo_dia_atual</td>";
        }
        echo "</tr>";
    }
    
    
    echo "
    <tr>
    <td colspan='4'>
    <a href='#' onclick=\"javascript:abrirJanela('../../caixa_ins$ext_mk', 530, 600);\">
    <img src='img/icon_fluxo_caixa.png' class='icon_add' title='Adicionar Movimentação'/>
    </a>
    </td>
    </tr>
    ";

    ?>

    <div id="totais">
        <div class="tot red">
            <?php echo "<p class='tot_values'><span class='tit_small'>Total Saídas:</span><br> R$ ".number_format($tot_saida,2,',','.')."</p>"; ?>
        </div>

        <div class="tot blue">
            <?php echo "<p class='tot_values'><span class='tit_small'>Total Entradas:</span><br> R$ ".number_format($tot_entrada,2,',','.')."</p>"; ?>
        </div>

        <div class="tot green">
            <?php echo "<p class='tot_values'><span class='tit_small'>Saldo:</span><br> R$ ".number_format($saldo,2,',','.')."</p>"; ?>
        </div>
    </div>


    <?php
    

    echo "</table>";




    $tot_entrada = round($tot_entrada);
    $tot_saida = round($tot_saida);
    $saldo = round($saldo);

    /*$data_inicial = date('d/m/Y', strtotime($data_inicial));
    $data_final = date('d/m/Y', strtotime($data_final));*/


    ?>

    <?php


    
        $lista_tipo_pagamento = mysqli_query($link, "SELECT DISTINCT formapag FROM sis_lanc ORDER BY formapag");
        while($lista = mysqli_fetch_array($lista_tipo_pagamento)){
            $lista_tipo_pag[] = $lista['formapag'];


        }

        $query_tipo_pag = mysqli_query($link, "SELECT a.formapag, a.valorpag FROM sis_lanc a
        WHERE (a.formapag NOT LIKE 'boleto' AND a.datapag BETWEEN '$data_inicial' AND '$data_final 23:59:59')
        OR (a.formapag LIKE 'boleto' AND a.id IN (SELECT titulo FROM sis_rettitulos WHERE titulo = a.id AND datta BETWEEN '$data_inicial' AND '$data_final 23:59:59')) ORDER BY a.formapag");
        


        if(!$query_tipo_pag){
            echo mysqli_error($link);
        }

        while($tipo_pag = mysqli_fetch_array($query_tipo_pag)){
            $formapag['formapag'] = $tipo_pag['formapag'];
            $valorpag = $tipo_pag['valorpag'];
            //$coletor_pag['formapag'] = $tipo_pag['coletor'];



            foreach($lista_tipo_pag as $key => $value){

                if($formapag['formapag'] == $value){
                    //print_r($lista_tipo_pag);

                    $pag_por_tipo_pag_[$value] += $valorpag;

                }
            }

        }

        $query_entradas_manuais = mysqli_query($link, "SELECT entrada, planodecontas FROM sis_caixa WHERE tipomov LIKE 'man' AND entrada > 0 AND data BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY data");


        while($in_manual = mysqli_fetch_array($query_entradas_manuais)){
            /*echo "<pre>";
            print_r($in_manual);
            echo "</pre>";*/
            $in_manual_valor = $in_manual['entrada'];
            $in_manual_planodecontas = $in_manual['planodecontas'];
    
            $pag_por_tipo_pag_[$in_manual_planodecontas] += $in_manual_valor;
        }


    ?>

    <?php
    $query_previsao_pag = mysqli_query($link, "SELECT a.datavenc, a.valor,a.login FROM sis_lanc a WHERE a.datavenc BETWEEN '$data_inicial' AND '$data_final' AND a.deltitulo = '0' AND a.login LIKE (SELECT login FROM sis_cliente WHERE login = a.login AND cli_ativado LIKE 's') ORDER BY a.login");
    if (!$query_previsao_pag){
        echo mysqli_error($link);
    }
    $previsao_pag = 0;
    while($row2 = mysqli_fetch_array($query_previsao_pag)){
        //$data_pag = $row2['datavenc'];
        //$data_pag = date('d/m', strtotime($data_pag));
        $valor = $row2['valor'];
        //$valor = intval($valor);
        //$login = $row2['login'];

        $previsao_pag += $valor;
        //echo "Data: $data_pag - Login: $login - Valor: $valor </br>";
        //print_r($row2);

    }

    //$previsao_pag = number_format($previsao_pag, 2, ',', '.');
    $porc_recebido = ($tot_entrada / $previsao_pag) * 100;
    $porc_recebido = number_format($porc_recebido, 2);
    echo "
        <div id='prev_faturamento'>
            <span id='prev_titulo'>Faturamento Previsto:</span>
            </br>
            <span id='prev_valor'>R$ $previsao_pag,00</span>   
            <span id='prev_porc_recebido'> / recebido R$ $tot_entrada,00 - $porc_recebido% do previsto.</span>
        </div>";
    


    mysqli_close($link);

    ?>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">
        <script>

    Highcharts.chart('container', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Balanço'
        },
        xAxis: {
            categories: ['Faturado']
        },
        credits: {
            enabled: false
        },
        series: [{
            name: 'Entradas',
            data: [<?php echo "$tot_entrada"; ?>]
        }, {
            name: 'Saidas',
            data: [<?php echo "$tot_saida"; ?>],
        }, {
            name: 'Saldo',
            data: [<?php echo "$saldo"; ?>],
        }]
    });

    </script>
    </p>
    </figure>


    <figure class="highcharts-figure">
        <div id="container2"></div>
        <p class="highcharts-description">
        <script>

    Highcharts.chart('container2', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Faturamento por Tipo de Pagamento'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            foreach ($pag_por_tipo_pag_ as $key => $value) {
                echo "
                {
                    name: '$key',
                    data: [$value]
                },
                    ";
            }
        ?>
        
        ]
    });

    </script>
    </p>
    </figure>

    <?php 
        //Fim Permissao
        } 
    ?>

    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

    </body>
</html>