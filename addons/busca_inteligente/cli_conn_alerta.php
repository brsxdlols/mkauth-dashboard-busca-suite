<?php include('nav/header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>
    <?php require_once __DIR__ . '/../shared/layout_mode.php'; mka_suite_render_top_spacing_style($link); ?>

    <style>
        .alert-toolbar{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;margin:10px 15px 18px;padding:10px;border:1px solid #dbe5f0;border-radius:16px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.06)}.alert-toolbar a{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:11px;color:#36506c;text-decoration:none;font-size:13px;font-weight:700;transition:.18s}.alert-toolbar a:hover{background:#edf5ff;color:#1268db}.alert-toolbar a.is-active{background:#1268db;color:#fff}.alert-toolbar i{font-size:16px}
        .alert-toolbar .nav-item{display:flex;margin:0;padding:0;list-style:none}.alert-toolbar .nav-item::marker{content:""}
        .alert-search{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr)) minmax(210px,1.3fr) auto;gap:9px;align-items:end;margin:0 15px 14px;padding:14px;border:1px solid #dbe5f0;border-radius:16px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.04)}.alert-field{display:flex;flex-direction:column;gap:5px}.alert-field label{font-size:12px;font-weight:700;color:#334155}.alert-field input{width:100%;height:42px;padding:0 11px;border:1px solid #cbd8e6;border-radius:10px;box-sizing:border-box}.alert-search button{height:42px;padding:0 22px;border:0;border-radius:11px;background:#1268db;color:#fff;font-weight:700}
        .alert-count{margin:0 15px 10px;font-weight:700;color:#334155}.alert-table-wrap{margin:0 15px 18px;border:1px solid #dbe5f0;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.05)}.alert-table{margin:0!important}.alert-table thead th{padding:13px 12px;background:#27313d;color:#fff;border:0}.alert-table td{padding:11px 12px;vertical-align:middle}.alert-table a{color:#193755;text-decoration:none;font-weight:700}.alert-table a:hover{color:#1268db;text-decoration:underline}
        @media(max-width:1100px){.alert-search{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.alert-search{grid-template-columns:1fr 1fr}.alert-table-wrap{overflow-x:auto}}@media(max-width:520px){.alert-toolbar span{display:none}.alert-search{grid-template-columns:1fr;margin-inline:8px}.alert-count,.alert-table-wrap{margin-inline:8px}}
    </style>
    <nav class="alert-toolbar no_print mka-suite-content-start" aria-label="Navegação de alertas">
        <li class="nav-item">
            <a href="#" onClick="window.history.back();return false;"><i class="fa-solid fa-circle-chevron-left"></i><span>Voltar</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php"><i class="fa-solid fa-house"></i><span><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></span></a>
        </li>
        <li class="nav-item">
            <a href="cli_conn_alerta.php" class="is-active"><i class="fa-solid fa-circle-exclamation"></i><span>Alertas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php"><i class="fa-solid fa-headset"></i><span>Chamados</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="score.php"><i class="fa-solid fa-ranking-star"></i><span>Score</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="relcontratos.php"><i class="fa-solid fa-file-signature"></i><span>Contratos</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php"><i class="fa-solid fa-gear"></i><span>Configurações</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" onClick="window.print();return false;"><i class="fa-solid fa-print"></i><span>Imprimir</span>
            </a>
        </li>

    </nav>
<?php

    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    function secondsToTime($seconds) 
    { 
        $dtF = new \DateTime('@0'); 
        $dtT = new \DateTime("@$seconds"); 
        return $dtF->diff($dtT)->format('%ad - %H:%I:%S'); 
    } 

    function difDatas($data_inicio, $data_fim){
        $d1 = new DateTime($data_inicio);
        $d2 = new DateTime($data_fim);

        // Resgata diferen�a entre as datas
        //$dateInterval = ;
        return $d1->diff($d2)->format('%a dias %H horas e %I minutos');
        //echo "</br>";
    }

    $data_atual = date('Y-m-d');
    
    $data_open = date('Y-m-d', strtotime('-5 days', strtotime($data_atual)));

    $data_inicial = isset($_GET['data_inicial']) == '' ? $data_open : $_GET['data_inicial'];
    //echo $data_inicial;
    //echo "</br>";
    $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];
    $tempo_conn = isset($_GET['tempo_conn']) == '' ? 15 : $_GET['tempo_conn'];
    $qtd_quedas = isset($_GET['qtd_quedas']) == '' ? 20 : $_GET['qtd_quedas'];

    $busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';
    $busca_sql = mysqli_real_escape_string($link, $busca);

    /*$query_conn_online = mysqli_query($link, "SELECT * FROM radacct WHERE (username LIKE '$login_cliente' 
    OR username IN (SELECT username FROM sis_adicional WHERE login = '$login_cliente')) AND acctstoptime IS NULL ORDER BY username");
        if (!$query_conn_online){
            echo mysqli_error();
        }*/

    $query_conn = mysqli_query($link, "SELECT username, acctstarttime, acctsessiontime  FROM radacct 
    WHERE username IN (SELECT login FROM sis_cliente WHERE cli_ativado = 's' AND bloqueado = 'nao') 
    AND acctstarttime BETWEEN '$data_inicial' AND '$data_final 23:59:59' 
    AND acctsessiontime < $tempo_conn * 60
    AND username LIKE '%$busca_sql%'
    ORDER BY username");

    if (!$query_conn){
        echo mysqli_error($link);
    }

?>


<form action="" method="get" class="alert-search no_print">
        <div class="alert-field"><label>Data inicial</label><input type="date" name="data_inicial" value="<?php echo htmlspecialchars($data_inicial, ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="alert-field"><label>Data final</label><input type="date" name="data_final" value="<?php echo htmlspecialchars($data_final, ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="alert-field"><label>Conexão máxima (min)</label><input type="number" name="tempo_conn" min="1" max="240" value="<?php echo (int)$tempo_conn; ?>"></div>
        <div class="alert-field"><label>Mínimo de quedas</label><input type="number" name="qtd_quedas" min="1" max="1000" value="<?php echo (int)$qtd_quedas; ?>"></div>
        <div class="alert-field"><label>Buscar login</label><input type="search" name="busca" placeholder="Pesquise pelo login" value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>"></div>
        <button type="submit" name="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </form>



<?php

    $alerta = 0;
    
    while ($row = mysqli_fetch_array($query_conn)) {
        $quedas_cli_[$row['username']] += 1;
    }

    arsort($quedas_cli_); // Ordenando do Maior p/ Menor

    //$tot_clientes = count($quedas_cli_);

?>


<div class="alert-count">Total de clientes com desconexões: <span id="alert-total">0</span></div>
<div class="alert-table-wrap"><table class="table table-sm table-hover table-striped small alert-table">
    <thead><tr><th>Cliente</th><th>Endereço</th><th>Login</th><th>Quantidade de quedas</th></tr></thead><tbody>

<?php
    foreach ($quedas_cli_ as $key => $value) {
        if ($value >= $qtd_quedas){
            $query_cli = mysqli_query($link, "SELECT nome, endereco, bairro, complemento, cidade, estado FROM sis_cliente WHERE login = '$key'");
            while($row = mysqli_fetch_array($query_cli)){
                $nome_cli = $row['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_cli = html_entity_decode(htmlentities($nome_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $end_cli = $row['endereco'];

                //Converter UTF-8 do Banco de Dados
                //$end_cli = html_entity_decode(htmlentities($end_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $bairro_cli = $row['bairro'];

                //Converter UTF-8 do Banco de Dados
                //$bairro_cli = html_entity_decode(htmlentities($bairro_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $complemento_cli = $row['complemento'];

                //Converter UTF-8 do Banco de Dados
                //$complemento_cli = html_entity_decode(htmlentities($complemento_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $cidade_uf_cli = $row['cidade'].' / '.$row['estado'];    

                //Converter UTF-8 do Banco de Dados
                //$cidade_uf_cli = html_entity_decode(htmlentities($cidade_uf_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');
            }
            echo "
            <tr class=''>
                <td class='link'><a href='index.php?busca=$nome_cli' title='Ver Cliente'>$nome_cli</a></td>
                <td>
                $end_cli $complemento_cli - $bairro_cli - $cidade_uf_cli
                </td>
                <td class='link'><a href='det_conn.php?login=$key' title='Ver Conexões'>$key</a></td>
                <td>$value</td>
            </tr>";

            $tot_clientes += 1;
        }
    }
    mysqli_close($link);

    echo "<script>document.getElementById('alert-total').textContent='" . (int)$tot_clientes . "';</script>";

?>



</tbody></table></div>


<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
    </body>
</html>
