<?php
include('addons.class.php');
session_name('mka');
session_start();

if (!isset($_SESSION['mka_logado']) && !isset($_SESSION['MKA_Logado'])) {
    exit('Acesso negado... <a href="/admin/login.php">Fazer Login</a>');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></title>
    <link href="estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="css/estilo.css" rel="stylesheet" type="text/css" />
    <link href="estilos/bi-icons.css" rel="stylesheet" type="text/css" />
    <script src="scripts/jquery.js"></script>
    <script src="scripts/mk-auth.js"></script>
</head>
<body>

    <?php include('topo.php'); ?>


    <?php include('config.php'); ?>



    <div class="">


        <?php

        $data_ini = date('Y-m');

        $data_atual = date('Y-m-d');
        $data_inicial = isset($_GET['data_inicial']) == '' ? "$data_ini-01" : $_GET['data_inicial'];
        //echo $data_inicial;
        //echo "</br>";
        $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];

        $data_ini_graf = date('d/m/Y', strtotime($data_inicial));
        $data_end_graf = date('d/m/Y', strtotime($data_final));


        $historico_busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
        $historico_busca = trim($historico_busca);
        $palavra_busca = str_replace(" ", "%", $historico_busca);

        $usuario = isset($_GET['usuario']) == "" ? "%" : $_GET['usuario'];



        ?>

        <form action="" method="get">
            <table id="form_graf">
                <tr>
                    <td><b>Data Inicial:</b></td>
                    <td><b>Data Final:</b></td>
                    <td><b>Pesquisar Nome / Login</b></td>
                    <td><b>Usuario:</b></td>
                    <td></td>
                </tr>
                <tr>
                    <td><input type="date" name="data_inicial" value="<?php echo $data_inicial; ?>" /></td>
                    <td><input type="date" name="data_final" value="<?php echo $data_final; ?>" /></td>
                    <td><input type="text" name="busca" class="buscar" value="<?php echo $palavra_busca; ?>" /></td>

                    <td>
                        <select name="usuario">
                            <?php
                            $query_list_usuario = mysqli_query($conn, "SELECT DISTINCT usuario FROM sis_caixa ORDER BY usuario");
                            if (!$query_list_usuario) {
                                echo mysqli_error($link);
                            }
                            $list_usuario['%'] = "Todos";
                            while ($row = mysqli_fetch_array($query_list_usuario)) {
                                $list_usuario[$row['usuario']] = $row['usuario'];
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




        /*if(!$query_log){
            echo mysqli_error($conn);
        }*/


        $query_titulos_excluidos = mysqli_query($conn, "SELECT l.id, l.login, l.valor, l.datadel, c.nome FROM sis_lanc l 
        LEFT JOIN sis_cliente c 
        ON l.login = c.login
        WHERE l.deltitulo NOT LIKE 0 AND l.datadel BETWEEN '$data_inicial' AND '$data_final' ORDER BY l.datadel");

        /*if (!$query_titulos_excluidos) {
            echo mysqli_error($link);
        }*/

        echo "<table class='tabela'>
                    <tr class='linha_titulo'>
                    <td>N° Titulo</td>
                    <td>Cliente</td>
                    <td>Valor</td>
                    <td>Data Exlusão</td>
                    <td>Usuario</td>
                    <td>Log do Sistema</td>
                </tr>
            ";

        $cc = 0;
        while ($row = mysqli_fetch_array($query_titulos_excluidos)) {
            $num_titulo = $row['id'];
            $login = $row['login'];
            $nome = $row['nome'];
            $valor = $row['valor'];

            $valor_formatado = "R$ " . number_format($valor, 2, ',', '.');

            $data_exclusao = $row['datadel'];
            $data_exclusao = date('d/m/Y H:i:s', strtotime($data_exclusao));

            $query_log = mysqli_query($conn, "SELECT login, registro FROM sis_logs WHERE registro LIKE '%deletou%$num_titulo%' AND tipo LIKE 'admin' AND login LIKE '$usuario'");
            if ($row2 = mysqli_fetch_array($query_log)) {
    
                $usuario_sistema = $row2['login'];
    
                $registro = $row2['registro'];
            }else{
                $usuario_sistema = "SEM REGISTRO";
    
                $registro = "SEM REGISTRO";
            }

            echo "
                <tr class='linha_resultados'>
                    <td>$num_titulo</td>
                    <td>$nome <strong>[$login]</strong></td>
                    <td>$valor_formatado</td>
                    <td>$data_exclusao</td>
                    <td>$usuario_sistema</td>
                    <td>$registro</td>
                </tr>
                ";
            $cc++;
        }

        echo "<p>Total de registros encontrados: $cc</p>";
        ?>


        </table>
    </div>

    <?php include('baixo.php'); ?>

    <script src="menu.js.php"></script>

</body>

</html>