<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versão antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
}
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="estilos/font-awesome.css" rel="stylesheet" type="text/css" />

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />

    <script src="scripts/jquery.js"></script>
    <script src="scripts/mk-auth.js"></script>
</head>

<body>
    <?php

        //Paginaçço
        $registros_por_pagina = isset($_GET['num_registros']) == '' ? '50' : $_GET['num_registros'];
        $pagina = isset($_GET['pagina']) == '' ? $pc = "1" : $pc = $_GET['pagina'];

        $inicio = $pagina - 1;
        $inicio = $inicio * $registros_por_pagina;

        $data_atual = date('Y-m-01');
        $lastDayYear = date('Y-12-31');

        //$data_open = date('Y-m-d', strtotime('-5 days', strtotime($data_atual)));

        $data_inicial = isset($_GET['data_inicial']) == '' ? $data_atual : $_GET['data_inicial'];
        //echo $data_inicial;
        //echo "</br>";
        $data_final = isset($_GET['data_final']) == '' ? $lastDayYear : $_GET['data_final'];
        // $tempo_conn = isset($_GET['tempo_conn']) == '' ? 15 : $_GET['tempo_conn'];
        // $qtd_quedas = isset($_GET['qtd_quedas']) == '' ? 20 : $_GET['qtd_quedas'];

        $busca = isset($_GET['busca']) == '' ? $busca : $_GET['busca'];

        // $local = isset($_GET['local']) == '' ? '%' : $_GET['local'];


    ?>

        <?php include('topo.php'); ?>

        <ul class="nav nav-tabs justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">Relatório de Acesso na Central</a>
            </li>
            <li>
                <img src="img/icon_print.png" class="icon no_print" title="Imprimir" onClick="window.print()" />
            </li>
        </ul>

        <?php

        $query = "SELECT ativ.*, c.nome
    FROM sis_ativ ativ
    LEFT JOIN sis_cliente c ON ativ.login = c.login
    WHERE 
    ativ.registro LIKE 'acessou a central do assinante' AND
    ativ.data BETWEEN '$data_inicial' AND '$data_final 23:59:59' 
    ";


        $result = mysqli_query($conn, $query);

        //$result_limit = mysqli_query($link, "$query LIMIT $inicio,$registros_por_pagina");

        /*$query_conn = mysql_query("SELECT username, nome FROM radacct AS c LEFT JOIN sis_cliente AS cli ON username = cli.login");*/

        if (!$result) {
            echo mysqli_error($link);
        }

        ?>

        <form action="" method="get">
            <div class="row g-1">
                <div class="col-6 col-sm-3">
                    <div class="form-floating">
                        <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?php echo $data_inicial; ?>" placeholder="data">
                        <label for="data_inicial">Data Inicial:</label>
                    </div>
                </div>
                <div class="col-6 col-sm-3">    
                    <div class="form-floating">
                        <input type="date" class="form-control" id="data_final" name="data_final" value="<?php echo $data_final; ?>" placeholder="data">
                        <label for="data_final">Data Final:</label>
                    </div>
                </div>
                <div class="col-10 col-sm-5">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="busca" name="busca" value="<?php echo $busca; ?>" placeholder="data">
                        <label for="busca">Buscar Login:</label>
                    </div>
                </div>
                <div class="col-2 col-sm-1">
                    <div class="d-grid h-100">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">Buscar</button>
                    </div>
                </div>
            </div>
        </form>

        <table class="table table-sm table-striped table-hover small">
            <tr class="table-dark fw-bold">
                <td>DATA</td>
                <td>NOME [LOGIN]</td>
                <td>MENSAGEM</td>
            </tr>

        <?php

        while ($row = mysqli_fetch_assoc($result)) {

            // debug($row);
        
        ?>
    <tr>
    <td><?=$row['data'];?></td>
        <td><?="{$row['nome']} <b>[{$row['login']}]</b>";?></td>
        <td><?=$row['registro'];?></td>
    </tr>

<?php
        }

        echo "<p class='m-0'><b>Resultados Encontrados:</b> ".mysqli_num_rows($result)."</p>";

        mysqli_close($conn);
    

        ?>
        </table>

        <?php include('baixo.php'); ?>

        <script src="menu.js.php"></script>

</body>

</html>