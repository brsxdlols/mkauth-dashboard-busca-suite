<!-- 
    RELATï¿½RIO DE CLIENTES
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versï¿½o antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
}
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/css.css" rel="stylesheet" type="text/css" />

</head>

<body>

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center">
        <li class="nav-item">
            <a class="nav-link" href="index.php"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">PLANOS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="cli_filtro.php">REL CLI FILTRO</a>
        </li>
    </ul>


    <?php
    //INICIO PERMISSAO
    if ($acesso_permitido) {
    ?>




        <?php
        $sql_planos = mysqli_query($link, "SELECT nome, valor FROM sis_plano ORDER BY valor, nome");
        if (!$sql_planos) {
            echo "Invalid query<br/>";
            echo mysqli_error($link);
        } else {
            //$num_planos = mysql_num_rows($result);
            //echo "<b>Total Clientes Cancelados = $num_desativados</b></br></br>";

            while ($row = mysqli_fetch_array($sql_planos)) {
                $nome_plano = $row['nome'];
                $valor_plano = $row['valor'];

                $sql_clientes_plano = mysqli_query($link, "SELECT plano FROM sis_cliente WHERE plano LIKE '$nome_plano' AND cli_ativado LIKE 's'");

                $sql_clientes_plano_add = mysqli_query($link, "SELECT plano FROM sis_adicional WHERE plano LIKE '$nome_plano'");

                $num_clientes_plano = mysqli_num_rows($sql_clientes_plano) + mysqli_num_rows($sql_clientes_plano_add);

                $lista_planos[$nome_plano] = $num_clientes_plano;
                $valores_planos[$nome_plano] = $valor_plano;

                $soma_valores[$nome_plano] = $valor_plano * $num_clientes_plano;


                /* Planos por Valor */
                $sql_clientes_valor_plano = mysqli_query($link, "SELECT c.plano FROM sis_cliente c LEFT JOIN sis_plano p ON c.plano = p.nome WHERE p.valor LIKE '$valor_plano' AND c.cli_ativado LIKE 's'");

                $sql_clientes_valor_plano_add = mysqli_query($link, "SELECT c.plano FROM sis_adicional c LEFT JOIN sis_plano p ON c.plano = p.nome  WHERE p.valor LIKE '$valor_plano'");

                if(!$sql_clientes_valor_plano){
                    echo mysqli_error($link);
                }

                
                if(!$sql_clientes_valor_plano_add){
                    echo mysqli_error($link);
                }

                $num_clientes_valor_plano = mysqli_num_rows($sql_clientes_valor_plano) + mysqli_num_rows($sql_clientes_valor_plano_add);

                $lista_valores_planos[$valor_plano] = $num_clientes_valor_plano;

            }
        }

        //debug($lista_valores_planos);
        ?>


        <script src="js/highcharts.js"></script>
        <script src="js/exporting.js"></script>


        <div class="row">
        <div class="col-12 col-md-6">
                <figure class="highcharts-figure">
                    <div id="container"></div>
                    <p class="highcharts-description">
                        <script>
                            Highcharts.chart('container', {
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
            <div class="col-12 col-md-6">
                <figure class="highcharts-figure">
                    <div id="container2"></div>
                    <p class="highcharts-description">
                        <script>
                            Highcharts.chart('container2', {
                                chart: {
                                    plotBackgroundColor: null,
                                    plotBorderWidth: null,
                                    plotShadow: false,
                                    type: 'pie'
                                },
                                title: {
                                    text: 'Planos Valores por Clientes'
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
                                        foreach ($lista_valores_planos as $key => $value) {
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
            <div class="col-12 col-md-6">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th class="bg-success lead text-center text-light" scope="col" colspan="4">
                                <p class='m-0 p-0'>Clientes Ativos</p>
                            </th>
                        </tr>
                        <tr class='bg-secondary text-center fw-bold'>
                            <td class="text-light">Nome do Plano</td>
                            <td class="text-light">Valor</td>
                            <td class="text-light">Clientes</td>
                            <td class="text-light">Total</td>
                        </tr>
                    </thead>
                    <tbody>


                        <?php
                        foreach ($lista_planos as $key => $value) {
                            if ($busca_inteligente_instalado) {
                                $url_busca = "../busca_inteligente/?busca=$key'";
                            } else {
                                $url_busca = "../../clientes$ext_mk?tipo=todos&busca=$key&campo=plano&IR=IR' target='_blank'";
                            }

                            /*echo "
                    <tr class='linha_planos'>
                    <td>
                    <b><a href='$url_busca title='VER CLIENTES: $key''>$key</a></b>
                    </td>
                    <td>R$ $valores_planos[$key]</td>
                    <td>$value</td>
                    <td>R$ $soma_valores[$key]</td>
                    ";
                } */

                            echo "
                <tr class=''>
                <th class='fw-bold' scope='row'><a href='$url_busca title='VER CLIENTES: $key''>$key</a></th>
                <td>R$ $valores_planos[$key]</td>
                <td>$value</td>
                <td>R$ $soma_valores[$key]</td>
              </tr>";
                        }

                        ?>
                    </tbody>
                </table>
            </div>
            <div class="col-12 col-md-6">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th class="bg-danger lead text-center text-light" scope="col" colspan="4">
                                <p class='m-0 p-0'>Clientes Desativados</p>
                            </th>
                        </tr>
                        <tr class='bg-secondary text-center fw-bold'>
                            <td class="text-light">Nome do Plano</td>
                            <td class="text-light">Valor</td>
                            <td class="text-light">Clientes</td>
                            <td class="text-light">Total</td>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $sql_planos2 = mysqli_query($link, "SELECT nome, valor FROM sis_plano ORDER BY valor, nome");
                        if (!$sql_planos2) {
                            echo "Invalid query<br/>";
                            echo mysqli_error($link);
                        } else {

                            while ($row = mysqli_fetch_array($sql_planos2)) {
                                $nome_plano2 = $row['nome'];
                                $valor_plano2 = $row['valor'];

                                $sql_clientes_plano2 = mysqli_query($link, "SELECT plano FROM sis_cliente WHERE plano LIKE '$nome_plano2' AND cli_ativado LIKE 'n'");
                                while ($row2 = mysqli_fetch_array($sql_clientes_plano2)) {
                                }
                                $num_clientes_plano2 = mysqli_num_rows($sql_clientes_plano2);

                                $soma_valores2 = $valor_plano2 * $num_clientes_plano2;

                                if ($busca_inteligente_instalado) {
                                    $url_busca_desativado = "../busca_inteligente/?busca=$nome_plano2%2Bdesativado'";
                                } else {
                                    $url_busca_desativado = "../../clientes$ext_mk?tipo=desativados&busca=$nome_plano2&campo=plano&IR=IR' target='_blank'";
                                }

                                /*echo "
                            <tr class='linha_planos2'>
                            <td><b><a href='$url_busca_desativado title='VER CLIENTES: $nome_plano2''>$nome_plano2</a></b></td>
                            <td>R$ $valor_plano2</td>
                            <td>$num_clientes_plano2</td>
                            <td>R$ $soma_valores2</td>
                            </tr>";*/

                                echo "
                            <tr class=''>
                            <th class='fw-bold' scope='row'><a href='$url_busca_desativado title='VER CLIENTES: $nome_plano2''>$nome_plano2</a></th>
                            <td>R$ $valor_plano2</td>
                            <td>$num_clientes_plano2</td>
                            <td>R$ $soma_valores2</td>
                          </tr>";
                            }
                        }


                        ?>
                    </tbody>
                </table>
            </div>
        </div>


    <?php
        mysqli_close($link);

        //FIM PERMISSAO
    } ?>

    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

</body>

</html>