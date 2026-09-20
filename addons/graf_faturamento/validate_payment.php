<?php include 'nav/header.php'; ?>

<ul class="nav nav-tabs justify-content-center mb-3">
    <li class="nav-item">
        <a class="nav-link" aria-current="page" href="index.php"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'} . " - ANUAL"; ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_det.php">DETALHADO</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="graf_dia.php">DIÁRIO</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_ramal_cli.php">RAMAL 1</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_ramal.php">RAMAL 2</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_5a.php">GRAF 5 ANOS</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_nome.php">NOME</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_cidade_cli.php">CIDADE</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_bairro_cli.php">BAIRRO</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="graf_periodo.php">ULT MESES</a>
    </li>    
    <li class="nav-item">
        <a class="nav-link" href="graf_periodo.php">RELATÓRIO</a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" onClick="window.print()">
            <i class="fa-solid fa-print fs-4"></i>
        </a>
    </li>
</ul>

<?php
if ($acesso_permitido) {
?>

    <?php

    $data_atual = date('Y-m-d');
    $data_inicial = isset($_GET['data_inicial']) == '' ? $data_atual : $_GET['data_inicial'];
    //echo $data_inicial;
    //echo "</br>";
    $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];

    $historico_busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
    $historico_busca = trim($historico_busca);
    $palavra_busca = str_replace(" ", "%", $historico_busca);

    $usuario = isset($_GET['usuario']) == "" ? "%" : $_GET['usuario'];
    $plano_de_contas = isset($_GET['plano_de_contas']) == "" ? "%" : $_GET['plano_de_contas'];

    $mes = date('m', strtotime($data_inicial));
    $ano = date('Y', strtotime($data_inicial));

    $tot_entrada = 0;
    $tot_saida = 0;
    $estorno_entrada = 0;
    $estorno_saida = 0;
    ?>

    <form action="" method="get" id="" class="no_print">
        <div class="row g-1 justify-content-center">
            <div class="col-6 col-md-2">
                <div class="form-floating">
                    <input type="date" class="form-control" name="data_inicial" id="data_inicial" value="<?php echo $data_inicial; ?>" />
                    <label for="data_inicial"> Data Inicial:</label>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <div class="form-floating">
                    <input type="date" class="form-control" name="data_final" id="data_final" value="<?php echo $data_final; ?>" />
                    <label for="data_final"> Data Final:</label>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="form-floating">
                    <input type="text" name="busca" class="form-control" value="<?php echo $historico_busca; ?>" /></td>
                    <label for="data_final"> Pesquisar no histórico:</label>
                </div>
            </div>

            <div class="col-5 col-md-2">
                <div class="form-floating">
                    <select name="plano_de_contas" class="form-select">
                        <?php
                        $query_list_plano_de_contas = mysqli_query($link, "SELECT DISTINCT planodecontas FROM sis_caixa ORDER BY usuario");
                        if (!$query_list_plano_de_contas) {
                            echo mysqli_error($link);
                        }
                        $list_plano_de_contas['%'] = "Todos";
                        while ($row = mysqli_fetch_array($query_list_plano_de_contas)) {
                            $list_plano_de_contas[$row['planodecontas']] = $row['planodecontas'];
                        }

                        foreach ($list_plano_de_contas as $key => $value) {
                            $selected = ($plano_de_contas == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                        ?>

                    </select>
                    <label for="data_final"> Plano de Contas:</label>
                </div>
            </div>

            <div class="col-5 col-md-2">
                <div class="form-floating">
                    <select name="usuario" class="form-select">
                        <?php
                        $query_list_usuario = mysqli_query($link, "SELECT DISTINCT login FROM sis_acesso WHERE ativo = 'sim' ORDER BY login");
                        if (!$query_list_usuario) {
                            echo mysqli_error($link);
                        }
                        $list_usuario['%'] = "Todos";
                        $list_usuario['mk-bot'] = "mk-bot";
                        while ($row = mysqli_fetch_array($query_list_usuario)) {
                            $list_usuario[trim($row['login'])] = trim($row['login']);
                        }

                        //print_r($list_usuario);

                        foreach ($list_usuario as $key => $value) {
                            $selected = ($usuario == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                        ?>

                    </select>
                    <label for="data_final"> Usuário:</label>
                </div>
            </div>

            <div class="col-2 col-md-1">
                <div class="form-floating d-grid h-100">
                    <input type="submit" name="submit" value="OK" class="btn btn-primary btn-lg text-center" />
                </div>
            </div>


        </div>

    </form>

    <?php

    $tableDay .= "
        <table class='table table-sm table-striped small'>
        <tr class='table-dark fw-bold'>
            <td>Data / User</td>
            <td>Descrição</td>
            <td>Entrada</td>
            <td>Saída</td>
        </tr>
    ";

    //Gr�fico Di�rio
    $query_mysql = mysqli_query($link, "SELECT c.uuid_caixa, c.usuario, c.data, c.historico, c.complemento, c.entrada, c.saida, c.planodecontas /*, l.formapag */
    FROM sis_caixa c 
    /*LEFT JOIN sis_lanc l*/
    WHERE c.historico LIKE '%$palavra_busca%' AND c.data BETWEEN '$data_inicial' AND '$data_final 23:59:59' AND c.planodecontas LIKE '$plano_de_contas' AND TRIM(c.usuario) LIKE '$usuario' ORDER BY c.data");

    while ($row = mysqli_fetch_array($query_mysql)) {
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

        if (startsWith($historico, "estorno")) {
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
        if (startsWith($historico, "pagamento do titulo ")) {

            $num_titulo_cont = stripos($historico, " com");
            $num_titulo = substr($historico, 20, $num_titulo_cont - 20);

            if (startsWith($num_titulo, "0")) {
                $num_titulo = substr($num_titulo, 1);
            }

            $query_num_titulo = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE (SELECT login FROM sis_lanc WHERE id LIKE '$num_titulo')");
            while ($row2 = mysqli_fetch_array($query_num_titulo)) {
                $nome_por_num_titulo = $row2['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            $tableDay .= "
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
        }*/ else if (startsWith($historico, "recebimento do titulo ")) {
            $num_titulo_cont = stripos($historico, " / ");
            $num_titulo = substr($historico, 22, $num_titulo_cont - 22);
            //echo "$num_titulo</br>";

            if (startsWith($num_titulo, "0")) {
                $num_titulo = substr($num_titulo, 1);
            }

            $query_num_titulo2 = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE (SELECT login FROM sis_lanc WHERE id LIKE '$num_titulo')");
            while ($row2 = mysqli_fetch_array($query_num_titulo2)) {
                $nome_por_num_titulo = $row2['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            $query_forma_pag = mysqli_query($link, "SELECT formapag FROM sis_lanc WHERE id = '$num_titulo'");
            while ($row3 = mysqli_fetch_array($query_forma_pag)) {
                $forma_pag_cliente = $row3['formapag'];
            }

            $tableDay .= "
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
        } else if (startsWith($historico, "recebimento de titulo on-line, cliente: ")) {
            $login_pag = substr($historico, 40);
            //echo "login_pag = $login_pag</br>";
            //echo "$num_titulo</br>";

            $query_num_titulo3 = mysqli_query($link, "SELECT nome, uuid_cliente FROM sis_cliente WHERE login LIKE '$login_pag'");
            while ($row2 = mysqli_fetch_array($query_num_titulo3)) {
                $nome_por_num_titulo = $row2['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_por_num_titulo = html_entity_decode(htmlentities($nome_por_num_titulo, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $uuid_cliente = $row2['uuid_cliente'];
            }

            $tableDay .= "
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
        } else if (startsWith($historico, "pagamento da conta")) {
            $tableDay .= "
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
        } else {
            $tableDay .= "
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

    $tot_entrada = $tot_entrada - $estorno_entrada - $estorno_saida;

    $tot_saida = $tot_saida - $estorno_entrada - $estorno_saida;

    $saldo = $tot_entrada - $tot_saida;

    $tableDay .= "
    <tr class='linha_resultados'>
        <td colspan='2' id='align'><b>TOTAL - SEM ESTORNOS</b></td>
        <td class='entrada'>R$ $tot_entrada</td>
        <td class='saida'>R$ $tot_saida</td>
    </tr>";



    $query_saldo_atual = mysqli_query($link, "SELECT (sum(entrada) - sum(saida)) saldo_geral  FROM sis_caixa WHERE data <= '$data_final 23:59:59'");

    $data_query_saldo = date('d/m/Y', strtotime($data_final));

    while ($row = mysqli_fetch_array($query_saldo_atual)) {
        $saldo_dia_atual = $row['saldo_geral'];
        $tableDay .= "
        <tr class='table-primary'>
        <td>$data_query_saldo - 23:59</td>
        <td><b>Saldo atual</b></td>";
        if ($saldo_dia_atual > 0) {
            $tableDay .= "<td colspan='2' class='entrada center'>R$ $saldo_dia_atual</td>";
        } else {
            $tableDay .= "<td colspan='2' class='saida center'>R$ $saldo_dia_atual</td>";
        }
        $tableDay .= "</tr>";
    }


    $tableDay .= "
    <tr class='text-center'>
    <td colspan='4'>
    <a href='#' onclick=\"javascript:abrirJanela('../../caixa_ins$ext_mk', 530, 600);\">
    <i class='fa-solid fa-cash-register display-6' title='Adicionar Movimentacao'></i>
    </a>
    </td>
    </tr>
    </table>
    ";

    ?>

    <div class="row g-1">
        <div class="col-12 col-md-4">
            <?php echo "<p class='bg-primary my-1 px-3 py-1 display-6 text-light'><span class='lead'>Total Entradas:</span><br> R$ " . number_format($tot_entrada, 2, ',', '.') . "</p>"; ?>
        </div>

        <div class="col-12 col-md-4">
            <?php echo "<p class='bg-danger my-1 px-3 py-1 display-6 text-light'><span class='lead small'>Total Saídas:</span><br> R$ " . number_format($tot_saida, 2, ',', '.') . "</p>"; ?>
        </div>

        <div class="col-12 col-md-4">
            <?php echo "<p class='bg-success my-1 px-3 py-1 display-6 text-light'><span class='lead small'>Saldo:</span><br> R$ " . number_format($saldo, 2, ',', '.') . "</p>"; ?>
        </div>
    </div>


    <?php

    $tot_entrada = round($tot_entrada);
    $tot_saida = round($tot_saida);
    $saldo = round($saldo);

    ?>

    <?php



    $lista_tipo_pagamento = mysqli_query($link, "SELECT DISTINCT formapag FROM sis_lanc ORDER BY formapag");
    while ($lista = mysqli_fetch_array($lista_tipo_pagamento)) {
        $lista_tipo_pag[] = $lista['formapag'];
    }

    $query_tipo_pag = mysqli_query($link, "SELECT a.formapag, a.valorpag FROM sis_lanc a
WHERE (a.formapag NOT LIKE 'boleto' AND a.datapag BETWEEN '$data_inicial' AND '$data_final 23:59:59')
OR (a.formapag LIKE 'boleto' AND a.id IN (SELECT titulo FROM sis_rettitulos WHERE titulo = a.id AND datta BETWEEN '$data_inicial' AND '$data_final 23:59:59')) ORDER BY a.formapag");



    if (!$query_tipo_pag) {
        echo mysqli_error($link);
    }

    while ($tipo_pag = mysqli_fetch_array($query_tipo_pag)) {
        $formapag['formapag'] = $tipo_pag['formapag'];
        $valorpag = $tipo_pag['valorpag'];
        //$coletor_pag['formapag'] = $tipo_pag['coletor'];



        foreach ($lista_tipo_pag as $key => $value) {

            if ($formapag['formapag'] == $value) {
                //print_r($lista_tipo_pag);

                $pag_por_tipo_pag_[$value] += $valorpag;
            }
        }
    }

    $query_entradas_manuais = mysqli_query($link, "SELECT entrada, planodecontas FROM sis_caixa WHERE tipomov LIKE 'man' AND entrada > 0 AND data BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY data");


    while ($in_manual = mysqli_fetch_array($query_entradas_manuais)) {
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
    if (!$query_previsao_pag) {
        echo mysqli_error($link);
    }
    $previsao_pag = 0;
    while ($row2 = mysqli_fetch_array($query_previsao_pag)) {
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
    $prevFaturamento = "
<div class='bg-primary px-3 py-1 text-light'>
    <span class='small'>Faturamento Previsto:</span>
    </br>
    <span class='lead'>R$ $previsao_pag,00</span>   
    <span class='small'> / recebido R$ $tot_entrada,00 - $porc_recebido% do previsto.</span>
</div>";



    mysqli_close($link);

    ?>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <div class="row g-1">
        <div class="col-12 col-md-8">
            <?= $tableDay; ?>
        </div>

        <div class="col-12 col-md-4">
            <div class="row">
                <div class="col-12">
                    <?= $prevFaturamento; ?>
                    <figure class="highcharts-figure">
                        <div id="container"></div>
                        <p class="highcharts-description">
                            <script>
                                Highcharts.chart('container', {
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
                </div>
                <div class="col-12">
                    <figure class="highcharts-figure">
                        <div id="container2"></div>
                        <p class="highcharts-description">
                            <script>
                                Highcharts.chart('container2', {
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
                </div>
            </div>
        </div>
    </div>














<?php
    //Fim Permissao
}
?>

<?php include('../../baixo.php'); ?>

<script src="../../menu.js.php"></script>

</body>

</html>