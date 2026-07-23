<?php include('config.php'); ?>

<!DOCTYPE html>
<?php


if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versão antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
   
}

// Consultar as notificações não lidas
$query_ler_notificacoes = mysqli_query($conn, "SELECT COUNT(*) as total FROM dashboard_am_sis_notificacoes WHERE id_usuario = '$usuario_logado'");

if ($query_ler_notificacoes) {
    $num_notificacoes = mysqli_num_rows($query_ler_notificacoes);
    $result = mysqli_fetch_assoc($query_ler_notificacoes);
    $notificacoes_count = $result['total']; // Total de notificações para o usuário
} else {
    echo mysqli_error($conn);
}
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">

    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css">
    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="css/estilo.css" rel="stylesheet" type="text/css" />



</head>

<body>
    <?php if (!defined('ADMIN2URL')) define('ADMIN2URL', '/admin/'); ?>
    <?php include('../../topo.php'); ?>
    <?php include('mkauth_dashboard_top.php'); ?>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>


    <div class='container-fluid'>
        <?php

        $query_cria_tabelas = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_cfg (
            id int NOT NULL AUTO_INCREMENT,
            exb_ticket_medio VARCHAR(1) NOT NULL DEFAULT 's',
            exb_saldo_conta VARCHAR(1) NOT NULL DEFAULT 's',
            exb_clientes_ramal VARCHAR(1) NOT NULL DEFAULT 'n',
            exb_balanco_faturamento VARCHAR(1) NOT NULL DEFAULT 's',
            exb_balanco_clientes VARCHAR(1) NOT NULL DEFAULT 's',
            exb_balanco_chamados VARCHAR(1) NOT NULL DEFAULT 's',
            exb_busca_inteligente VARCHAR(1) NOT NULL DEFAULT 's',
            contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's',
            exb_graficos_em_baixo VARCHAR(1) NOT NULL DEFAULT 'n',
            tbl_logs_sistema VARCHAR(1) NOT NULL DEFAULT 's',
            tbl_chamados_abertos VARCHAR(1) NOT NULL DEFAULT 's',
            tbl_contas_pagar VARCHAR(1) NOT NULL DEFAULT 's',
            qtd_meses_graficos INT NOT NULL DEFAULT 3,
            limite_ticket INT NOT NULL DEFAULT 1000,
            link TEXT,
            texto TEXT,
            PRIMARY KEY (id)
        )");

        $query_check_notify = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_notificacoes WHERE field = 'id_remetente'");

        if (mysqli_num_rows($query_check_notify) == 0) {
            $drop_table = mysqli_query($conn, "DROP TABLE dashboard_am_sis_notificacoes");
        }

        $query_cria_tabelas2 = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_notificacoes (
            id int NOT NULL AUTO_INCREMENT,
            id_usuario INT NOT NULL,
            id_remetente INT NOT NULL,
            data DATETIME NOT NULL,
            mensagem TEXT NOT NULL,
            ativo VARCHAR(1) NOT NULL DEFAULT 's',
            PRIMARY KEY (id)
        )");

        $query = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'link'");

        if (mysqli_num_rows($query) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD link TEXT,
        ADD texto TEXT
        AFTER limite_ticket");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query2 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'contabilizar_bloq_offline'");

        if (mysqli_num_rows($query2) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's',
            ADD exb_graficos_em_baixo VARCHAR(1) NOT NULL DEFAULT 'n'
            AFTER exb_busca_inteligente");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query3 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'tot_acesso_rapido'");

        if (mysqli_num_rows($query3) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD tot_acesso_rapido INT NOT NULL DEFAULT '15'
            AFTER texto");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query4 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'exb_clientes_ramal'");

        if (mysqli_num_rows($query4) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD exb_clientes_ramal VARCHAR(1) NOT NULL DEFAULT 'n'
            AFTER exb_saldo_conta");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query_atual_cfg = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_cfg");
        if (mysqli_num_rows($query_atual_cfg) == 0) {
            $query_cfg_inicial = mysqli_query($conn, "INSERT INTO dashboard_am_sis_cfg (id) VALUES (1)");
        }


        // Ler Configuracoes
        while ($cfg = mysqli_fetch_array($query_atual_cfg)) {
            $exb_ticket_medio = $cfg['exb_ticket_medio'];
            $exb_saldo_conta = $cfg['exb_saldo_conta'];
            $exb_clientes_ramal = $cfg['exb_clientes_ramal'];
            $exb_balanco_faturamento = $cfg['exb_balanco_faturamento'];
            $exb_balanco_clientes = $cfg['exb_balanco_clientes'];
            $exb_balanco_chamados = $cfg['exb_balanco_chamados'];
            $exb_busca_inteligente = $cfg['exb_busca_inteligente'];
            $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'];
            $exb_graficos_em_baixo = $cfg['exb_graficos_em_baixo'];
            $tbl_logs_sistema = $cfg['tbl_logs_sistema'];
            $tbl_chamados_abertos = $cfg['tbl_chamados_abertos'];
            $tbl_contas_pagar = $cfg['tbl_contas_pagar'];
            $qtd_meses_graficos = $cfg['qtd_meses_graficos'];
            $limite_ticket = $cfg['limite_ticket'];
            $link = $cfg['link'];
            $texto = $cfg['texto'];
        }


        // Relação de grupos do usuário logado

        $sql_usuario_grupos = mysqli_query($conn, "SELECT cli_grupos FROM sis_acesso WHERE login LIKE '$usuario_logado' AND cli_grupos NOT LIKE 'full_clientes%'");

        if (!$sql_usuario_grupos) {
            echo mysqli_error($conn);
        }

        while ($row = mysqli_fetch_array($sql_usuario_grupos)) {
            $grupos_permitidos = $row['cli_grupos'];
        }

        if ($grupos_permitidos == "") {
            $grupos = "c.grupo LIKE '%' AND";
        } else {
            $grupos_permitidos = explode(",", $grupos_permitidos);

            $grupos = "(c.grupo = ";
            foreach ($grupos_permitidos as $key => $value) {
                if ($value == "ped_fil") {
                    continue;
                }
                if ($value == "full_clientes") {
                    $grupos = "(c.grupo LIKE '%' OR c.grupo = ";
                    break;
                }
                $grupos .= "'$value' OR c.grupo = ";
            }

            if ($grupos != "(c.grupo = ") {
                $grupos = substr($grupos, 0, -14);
                $grupos .= ") AND ";
            } else {
                $grupos = "";
            }
        }

        ?>


        <?php
        $per_meses = $qtd_meses_graficos;
        $string_per = "$per_meses meses";

        if ($exb_busca_inteligente == 's') {
        ?>

            <datalist id="sugestoes">
                <option value="on">
                <option value="off">
                <option value="adicionais">
                <option value="bloqueado">
                <option value="atrasado">
                <option value="observacao">
                <option value="desativado">
                <option value="sem carne">
                <option value="sem titulo">
                <option value="venc+">
                <option value="conta+">
                <option value="parcelas abertas+">
                    <?php
                    $query_sugestoes = mysqli_query($conn, "SELECT DISTINCT nome FROM sis_cliente ORDER BY nome");
                    while ($s = mysqli_fetch_array($query_sugestoes)) {
                        $s_nome = $s['nome'];
                        echo "<option value='$s_nome'>";
                    }

                    ?>
            </datalist>

            <div class="row">
                <div class="col-auto">
                    <a href="dash_notificacoes.php" onclick="window.open(this.href, this.target, 'width=650, height=700, scrollbars=yes'); return false;"><img src="img/icon_notificacao.png" alt="Notificações" class=""></a>
                </div>
                <div class="col">
                    <form action="/admin/addons/busca_inteligente/index.php" method="get" id="" class="form-inline">
                        <div class="row g-1">

                            <div class="col form-floating mb-3">
                                <input type="search" name="busca" class="form-control" id="floatingInput" placeholder="Busca Inteligente" value="<?php echo $busca; ?>" list="sugestoes">
                                <label for="floatingInput">(Busca Inteligente) Digite o que procura:</label>
                            </div>
                            <div class="col-auto mb-3">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">Buscar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        <?php
        }

        // Card Clientes
        // echo $grupos;
        $query_clientes_online = mysqli_query($conn, "SELECT r.username FROM radacct r FORCE INDEX (acctstoptime) LEFT JOIN sis_cliente c ON c.login = r.username WHERE $grupos r.acctstoptime IS NULL ");
        while ($row3 = mysqli_fetch_array($query_clientes_online)) {
            $username_on[trim(strtolower($row3['username']))] = trim(strtolower($row3['username']));
        }

        $query_clientes_ativos = mysqli_query($conn, "SELECT c.login, c.bloqueado, c.tit_vencidos, c.observacao FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's'");
        $query_clientes_adicionais = mysqli_query($conn, "SELECT cli_add.login as login_add FROM sis_adicional cli_add LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE $grupos c.cli_ativado LIKE 's'");

        $cli_ = 0;
        $cli_bloq = 0;
        $cli_on = 0;
        $cli_bloq_online = 0;
        $cli_obs = 0;
        while ($row = mysqli_fetch_array($query_clientes_ativos)) {
            if ($row['bloqueado'] == 'sim') {
                $cli_bloq++;
            }
            if ($row['observacao'] == 'sim') {
                $cli_obs++;
            }
            /*if ($row['tit_vencidos'] > 0) {
                $cli_atraso++;
            }*/
            if ($username_on[trim(strtolower($row['login']))]) {
                $cli_on++;
                if ($row['bloqueado'] == 'sim') {
                    $cli_bloq_online++;
                }
            }
            $cli_++;
        }

        $cli_atraso = 0;

$qTitulos = mysqli_query($conn, "SELECT l.login FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now' AND c.cli_ativado = 's' GROUP BY l.login");
while ($row = mysqli_fetch_assoc($qTitulos)) {
    $cli_atraso++;
}
// echo $cli_atraso."<br>";
        $c_add = 0;
        while ($row = mysqli_fetch_array($query_clientes_adicionais)) {
            if ($username_on[trim(strtolower($row['login_add']))]) {
                $cli_on++;
            }
            $c_add++;
        }

        $tot_clientes = $cli_ + $c_add;

        $tot_clientes_livres = $cli_ - $cli_bloq;

        if ($contabilizar_bloq_offline == 's') {
            $cli_offline = $tot_clientes - $cli_on;
        } else {
            $cli_offline = $tot_clientes - $cli_on - $cli_bloq + $cli_bloq_online;
            $cli_atraso -= $cli_bloq;
        }

        $cli_sem_carne = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.parc_abertas LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'carne'");
        $tot_sem_carne = mysqli_num_rows($cli_sem_carne);

        $cli_sem_titulo = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.tit_abertos LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'titulo'");
        $tot_sem_titulo = mysqli_num_rows($cli_sem_titulo);

        // Porcentagem dos Clientes
        $perc_clientes_livres = number_format($tot_clientes_livres / $cli_ * 100, 2);
        $perc_cliente_sem_adicionais = number_format($cli_ / $tot_clientes * 100, 2);
        $perc_clientes_adicional = number_format($c_add / $tot_clientes * 100, 2);
        $perc_clientes_bloqueado = number_format($cli_bloq / $cli_ * 100, 2);
        $perc_clientes_observacao = number_format($cli_obs / $cli_ * 100, 2);
        $perc_clientes_atrasado = number_format($cli_atraso / $cli_ * 100, 2);
        $perc_clientes_online = number_format($cli_on / $tot_clientes * 100, 2);
        $perc_clientes_offline = number_format($cli_offline / $tot_clientes * 100, 2);
        $perc_clientes_sem_carne = number_format($tot_sem_carne / $tot_clientes * 100, 2);
        $perc_clientes_sem_titulo = number_format($tot_sem_titulo / $tot_clientes * 100, 2);

        ?>

        <?php include('graf_periodo.php'); ?>
        <?php include('cli_periodo.php'); ?>

        <?php include('cli_chamado_per.php'); ?>

        <?php include('cli_planos.php'); ?>

        <div class='row mb-2'>
            <div class='col-12 col-md-12 col-lg-9 mb-2'>
                <!-- Card Clientes -->
                <div class='card border-light'>
                    <div class="card-header text-uppercase">Clientes - <?php echo $tot_clientes; ?></div>
                    <div class="card-body m-0 p-1">
                        <div class='row g-1'>
                            <div class='col'>
                                <div class="card text-bg-primary">
                                    <div class="card-header">Total</div>
                                    <a href="/admin/addons/busca_inteligente/index.php" class="text-light">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_cliente_sem_adicionais%"; ?></p>
                                        </div>
                                    </a>

                                </div>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-light">

                                    <div class="card-header">Adicional</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=adicionais" class="text-dark">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $c_add; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_adicional%"; ?></p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-info">

                                    <div class="card-header">Livres</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=" class="text-dark">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $tot_clientes_livres; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_livres%"; ?></p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-warning">

                                    <div class="card-header">Observação</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=obs" class="text-dark">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_obs; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_observacao%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-danger">

                                    <div class="card-header">Bloqueado</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=bloq" class="text-light">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_bloq; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_bloqueado%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-warning">

                                    <div class="card-header">Atraso</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=atrasado" class="text-dark">

                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_atraso; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_atrasado%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-success">
                                    <div class="card-header">Online</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=on" class="text-light">
                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_on; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_online%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card text-bg-dark">
                                    <div class="card-header">Offline</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=off" class="text-light">
                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $cli_offline; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_offline%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card border-danger">
                                    <div class="card-header">Sem Carne</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=sem carne" class="text-dark">
                                        <div class="card-body text-danger m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $tot_sem_carne; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_sem_carne%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>

                            <div class='col'>
                                <div class="card border-danger">
                                    <div class="card-header">Sem Titulos</div>
                                    <a href="/admin/addons/busca_inteligente/index.php?busca=sem tit" class="text-dark">
                                        <div class="card-body text-danger m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo $tot_sem_titulo; ?></p>
                                            <p class="text-center m-0 p-0"><?php echo "$perc_clientes_sem_titulo%"; ?></p>
                                        </div>
                                </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php

            /*$query_tot_chamados_abertos = mysqli_query($conn, "SELECT s.login FROM sis_suporte s LEFT JOIN sis_cliente c ON s.login = c.login WHERE s.status = 'aberto' AND c.cli_ativado = 's'");
            if (!$query_tot_chamados_abertos) {
                echo mysqli_error($link);
            }

            $tot_chamados = mysqli_num_rows($query_tot_chamados_abertos);*/

            ?>
            <div class='col-12 col-md-12 col-lg-3 mb-2'>
                <div class='card border-light'>
                    <div class="card-header text-uppercase">
                        Atendimentos
                    </div>
                    <div class="card-body m-0 p-1">

                        <div class="row g-1">
                            <div class="col">
                                <div class="card text-bg-light">
                                    <div class="card-header">
                                        Chamados
                                    </div>
                                    <div class="card-body m-0 p-0">
									<a href="/admin/suporte_aberto.hhvm" class="text-decoration-none">
                                        <p class="display-6 m-0 p-0 text-center" id="tot_chamados"></p>
                                        <p class="text-center m-0 p-0" id="perc_chamados">0.00</p>
                                    </div>
									 </a>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card text-bg-light">
                                    <div class="card-header">
                                        Instalações
                                    </div>
									<a href="/admin/instalacoes_abertas.hhvm" class="text-decoration-none">
                                        <div class="card-body m-0 p-0">
                                            <p class="display-6 m-0 p-0 text-center" id="tot_instalacoes"></p>
                                            <p class="text-center m-0 p-0" id="perc_instalacoes">0.00</p>
                                        </div>
									 </a>
                                </div>
                            </div>

                            <?php
                            $dd = date('Y-m');
                            $query = "SELECT ativ.login
                            FROM sis_ativ ativ
                            WHERE 
                            ativ.registro LIKE 'acessou a central do assinante' AND
                            ativ.data LIKE '$dd%' 
                            ";
                            $result = mysqli_query($conn, $query);
                            $totAcessos = mysqli_num_rows($result);
                            ?>
                            <div class="col">
                                <div class="card text-bg-light">
                                    <div class="card-header">
                                        Central
                                    </div>
                                    <a href="relAcessoCentral.php">
                                    <div class="card-body m-0 p-0">
                                        <p class="display-6 m-0 p-0 text-center" id=""><?= $totAcessos; ?></p>
                                        <p class="text-center m-0 p-0" id="">.</p>
                                    </div>
        </a>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <?php

            $query_ramal = mysqli_query($conn, "SELECT nasname, shortname FROM nas");
            //$ramal_cliente["%"] = "TODOS";
            while ($lista_ramal = mysqli_fetch_array($query_ramal)) {
                $ramal_cliente[$lista_ramal['nasname']] = $lista_ramal['shortname'];
            }



            if ((permissao('perm_relFin') || permissao('perm_relFat')) && $exb_clientes_ramal == 's') {

            ?>



                <!-- Ramais -->
                <div class='col-12 col-md-12 col-lg-12'>
                    <div class='card border-light mb-2'>
                        <div class="card-header text-uppercase">
                            Clientes Ramais
                        </div>
                        <div class="card-body m-0 p-0">

                            <div class="row g-1">

                                <?php
                                foreach ($ramal_cliente as $key => $value) {
                                    $nomeRamal = $value;

                                    $queryClientes = mysqli_query($conn, "SELECT login FROM sis_cliente WHERE ramal LIKE '$key' AND cli_ativado LIKE 's' ");

                                    $queryAdicionais = mysqli_query($conn, "SELECT cli_add.login as login_add FROM sis_adicional cli_add LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE cli_add.ramal LIKE '$key' AND c.cli_ativado LIKE 's'");

                                    $totGeral = mysqli_num_rows($queryClientes) + mysqli_num_rows($queryAdicionais);

                                    $queryOnline = mysqli_query($conn, "SELECT username FROM radacct r FORCE INDEX (acctstoptime) LEFT JOIN sis_cliente c ON r.username = c.login WHERE c.ramal LIKE '$key' AND c.cli_ativado LIKE 's' AND r.acctstoptime IS NULL");

                                    $queryAdicionaisOnline = mysqli_query($conn, "SELECT r.username FROM radacct r FORCE INDEX (acctstoptime) LEFT JOIN sis_adicional cli_add ON r.username = cli_add.username LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE cli_add.ramal LIKE '$key' AND c.cli_ativado LIKE 's' AND r.acctstoptime IS NULL");


                                    /*if(!$queryClientes || !$queryOnline){
                                    echo mysqli_error($conn);
                                }*/

                                    $totOnline = mysqli_num_rows($queryOnline) + mysqli_num_rows($queryAdicionaisOnline);

                                    $totOffline = $totGeral - $totOnline;

                                ?>
                                    <div class="col">
                                        <div class="card text-bg-light">
                                            <div class="card-header">
                                                <?= $nomeRamal; ?>
                                            </div>
                                            <div class="row g-1">
                                                <div class="col">
                                                    <div class="card-body m-0 p-0">
                                                        <div class="card text-bg-dark">
                                                            <div class="card-header">
                                                                Total
                                                            </div>
                                                            <div class="card-body m-0 p-0">
                                                                <p class="display-6 m-0 p-0 text-center">
                                                                    <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=ramal%2B' . $key; ?>" class="text-light"><?= $totGeral; ?></a>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="card-body m-0 p-0">
                                                        <div class="card text-bg-success">
                                                            <div class="card-header">
                                                                Online
                                                            </div>
                                                            <div class="card-body m-0 p-0">
                                                                <p class="display-6 m-0 p-0 text-center">
                                                                    <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=on%2B' . $key; ?>" class="text-light"><?= $totOnline; ?></a>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="card-body m-0 p-0">
                                                        <div class="card text-bg-danger">
                                                            <div class="card-header">
                                                                Offline
                                                            </div>
                                                            <div class="card-body m-0 p-0">
                                                                <p class="display-6 m-0 p-0 text-center text-light">
                                                                    <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=off%2B' . $key; ?>" class="text-light"><?= $totOffline; ?></a>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            }
            ?>

            <!-- Acesso Rápido -->
            <div class='col-12 col-md-12 col-lg-12'>
                <div class='card border-light mb-2'>
                    <div class="card-header text-uppercase">
                        Acesso Rápido
                    </div>
                    <div class="card-body m-0 p-0">

                        <div class="d-flex flex-wrap justify-content-center">

                            <?php if (permissao('perm_config')) {
                            ?>
                                <div class='p-1'><a href='cfg.php' class='btn btn-primary m-0 px-2'><img src='img/icon_config.png' class='align-middle icon_sm_2 ' title='Configurações Dash Board' /> Configurações</a></div>
                            <?php } ?>

                            <?php

                            $links = explode(',', $link);
                            $textos = explode(',', $texto);

                            $indice = 0;
                            foreach ($links as $k) {
                                if ($k != "") {
                                    if (startsWith($k, strtolower("http"))) {
                                        echo "<div class='p-1'><a href='$k' target='_blank' class='btn btn-success m-0 px-2'>$textos[$indice]</a></div>";
                                    } else {
                                        echo "<div class='p-1'><a href='/admin/$k' class='btn btn-secondary m-0 px-2'>$textos[$indice]</a></div>";
                                    }
                                }
                                $indice++;
                            }

                            ?>
                        </div>

                    </div>
                </div>
            </div>




        </div>

        <?php
        if ($exb_graficos_em_baixo == 'n') {
            include_once('graficos.php');
        }
        ?>

        <div class='row'>
            <!-- Atividades - Logs do Sistema -->
            <?php if ((permissao('perm_logs')) && $tbl_logs_sistema == 's') { ?>

                <div class='col-12 mb-3'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'>Atividades</th>
                                <th class='fw-bold text-light'>Data</th>
                                <th class='fw-bold text-light'>Usuário</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            $query_sis_logs = mysqli_query($conn, "SELECT registro, data, login FROM sis_logs WHERE tipo NOT LIKE 'central' ORDER BY id DESC LIMIT 15");


                            while ($l = mysqli_fetch_array($query_sis_logs)) {
                                $log_registro = $l['registro'];
                                $log_data = $l['data'];
                                $log_login = $l['login'];
                            ?>


                                <tr class='font-field'>
                                    <td class=''><?php echo $log_registro; ?></td>
                                    <td class=''><?php echo $log_data; ?></td>
                                    <td class=''><?php echo $log_login; ?></td>
                                </tr>

                            <?php
                            }
                            ?>

                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='100%'><a href='<?php echo "/admin/logs_sistema$ext_mk"; ?>' target='' class='fw-bold'>VER MAIS...</a></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php } ?>
            <!-- Chamados -->

            <?php if ((permissao('perm_chamados')) && $tbl_chamados_abertos == 's') { ?>

                <div class='col-12 mb-3'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class="fw-bold text-light"></th>
                                <th class='fw-bold text-light'>Chamado</th>
                                <th class='fw-bold text-light'>Abertura</th>
                                <th class='fw-bold text-light'>Nome [Login]</th>
                                <th class='fw-bold text-light'>Assunto</th>
                            </tr>
                        </thead>

                        <tbody>

                            <div class='row text-uppercase font-title'>

                            </div>
                            <?php

                            $query_sis_suporte = mysqli_query($conn, "SELECT s.chamado, s.abertura, s.login, s.assunto, c.nome FROM sis_suporte s LEFT JOIN sis_cliente c ON s.login = c.login WHERE $grupos s.visita <= '$nova_data_1' AND s.status LIKE 'aberto' AND c.cli_ativado LIKE 's' ORDER BY s.abertura");

                            $tot_chamados = mysqli_num_rows($query_sis_suporte);

                            if (permissao('perm_totais')) {
                                echo "<script>$('#tot_chamados').html(\"$tot_chamados\")</script>";
                            }

                            $ccSup = 0;
                            while ($sup = mysqli_fetch_array($query_sis_suporte)) {

                                $sup_chamado = $sup['chamado'];
                                $sup_abertura = date('d/m/Y H:i:s', strtotime($sup['abertura']));
                                $sup_nome = $sup['nome'];

                                $sup_login = $sup['login'];
                                $sup_assunto = $sup['assunto'];
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/chamado<?php echo $ext_mk; ?>?chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Abrir </a>

                                            <a href="/admin/suporte_info<?php echo $ext_mk; ?>?login=<?php echo $sup_login; ?>&chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>
                                        </strong>
                                    </td>

                                    <td class=''><?php echo $sup_chamado; ?></td>
                                    <td class=''><?php echo $sup_abertura; ?></td>
                                    <td class=''><?php echo "$sup_nome <strong>[$sup_login]</strong>"; ?></td>
                                    <td class=''><?php echo $sup_assunto; ?></td>
                                </tr>

                            <?php
                                $ccSup++;
                                if ($ccSup == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>

            <?php } ?>

            <!-- Instalacoes -->

            <?php if ((permissao('perm_instalacao'))) { ?>

                <div class='col-12 mb-3'>
                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Instalação</th>
                                <th class='fw-bold text-light'>Nome</th>
                                <th class='fw-bold text-light'>Técnico</th>
								<th class='fw-bold text-light'>Data de Cadastro</th> <!-- Nova coluna -->
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            $query_sis_instalacao = mysqli_query($conn, "
							SELECT uuid_solic, disp, nome, login, tecnico, processamento AS data_cadastro 
							FROM sis_solic 
							WHERE status = 'aberto' 
							AND (datainst <= '$nova_data_1' OR visita <= '$nova_data_1' OR datainst IS NULL) 
							ORDER BY datainst
								");
								
                            $tot_instalacoes = mysqli_num_rows($query_sis_instalacao);

                            $tot_atendimento = $tot_chamados + $tot_instalacoes;
                            $perc_chamados = number_format($tot_chamados / $tot_atendimento * 100, 2);
                            $perc_instalacoes = number_format($tot_instalacoes / $tot_atendimento * 100, 2);

                            if (permissao('perm_totais')) {
                                echo "<script>$('#tot_instalacoes').html(\"$tot_instalacoes\")</script>";
                            }
                            echo "<script>$('#perc_instalacoes').html(\"$perc_instalacoes%\")</script>";
                            echo "<script>$('#perc_chamados').html(\"$perc_chamados%\")</script>";

                            $ccInst = 0;

                            while ($inst = mysqli_fetch_array($query_sis_instalacao)) {
                                $inst_uuid = $inst['uuid_solic'];
                                $inst_disponivel = $inst['disp'] == "sim" ? "Disponível" : "Indisponível";
                                $inst_nome = $inst['nome'];
                                $inst_login = $inst['login'];
                                $inst_tecnico = $inst['tecnico'];
								$data_cadastro = !empty($inst['data_cadastro']) ? date("d/m/Y", strtotime($inst['data_cadastro'])) : "N/A"; // Formata a data
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/instalar_alt<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none">Alterar </a>

                                            <a href="/admin/instalacao_info<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>

                                            <a href="/admin/cliente_ins<?php echo $ext_mk; ?>?new_install=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none" target="_blank">Incluir Cliente</a>

                                        </strong>
                                    </td>
                                    <td class=''><?php echo $inst_disponivel; ?></td>
                                    <td class=''><?php echo "$inst_nome <strong>[$inst_login]</strong>"; ?></td>
                                    <td class=''><?php echo $inst_tecnico; ?></td>
									<td class=''><?php echo $data_cadastro; ?></td> <!-- Exibe a data de cadastro -->
                                </tr>

                            <?php
                                $ccInst++;
                                if ($ccInst == 500) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <!-- Contas a pagar -->
            <?php if ((permissao('perm_contaspagar')) && $tbl_contas_pagar == 's') { ?>

                <div class='col-12 col'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Título</th>
                                <th class='fw-bold text-light'>Parcela</th>
                                <th class='fw-bold text-light'>Fornecedor / Funcionário</th>
                                <th class='fw-bold text-light'>Vencimento</th>
                                <th class='fw-bold text-light'>Valor</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php

                            $query_sis_contaspagar = mysqli_query($conn, "SELECT c.id AS id_conta, c.nrdocumento, c.parcatual, c.numparcelas, c.historico, c.vencimento, c.valor, f.razaosoc, c.tipodiv, func.nome , c.uuid_contaspagar
                            FROM sis_contaspagar c 
                            LEFT JOIN sis_fornecedor f ON c.fornecedor = f.id 
                            LEFT JOIN sis_func func ON c.fornecedor = func.id
                            WHERE c.status NOT LIKE 'liquidado' AND c.vencimento <= '$nova_data_5'  ORDER BY c.vencimento");


                            $ccCP = 0;
                            while ($cp = mysqli_fetch_array($query_sis_contaspagar)) {
                                $cp_id = $cp['id_conta'];
                                $cp_uuid_contaspagar = $cp['uuid_contaspagar'];
                                $cp_nrdocumento = $cp['nrdocumento'];
                                $cp_parcatual = $cp['parcatual'];
                                $cp_numparcelas = $cp['numparcelas'];

                                if ($cp['tipodiv'] == "for") {
                                    $cp_fornecedor = $cp['razaosoc'] . " - " . $cp['historico'];
                                } else {
                                    $cp_fornecedor = $cp['nome'] . " - " . $cp['historico'];
                                }

                                $cp_vencimento = date('d/m/Y', strtotime($cp['vencimento']));

                                $cp_valor = number_format($cp['valor'], 2, ',', '.');
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/contaspagar_liquidar<?php echo $ext_mk; ?>?uuid=<?php echo $cp_uuid_contaspagar; ?>" class="btn-link text-decoration-none">Liquidar</a>
                                        </strong>
                                    </td>
                                    <td class=''><?php echo $cp_nrdocumento; ?></td>
                                    <td class=''><?php echo "$cp_parcatual / $cp_numparcelas"; ?></td>
                                    <td class=''><?php echo $cp_fornecedor; ?></td>
                                    <td class=''><?php echo $cp_vencimento; ?></td>
                                    <td class=''>R$ <?php echo $cp_valor; ?></td>
                                </tr>

                            <?php
                                $ccCP++;
                                if ($ccCP == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>

            <?php } ?>
        </div>

        <?php

        if ($exb_graficos_em_baixo == 's') {
            include_once('graficos.php');
        }

        // Close conn in MariaDB
        mysqli_close($conn);

        ?>
        <?php include('../../baixo.php'); ?>

        <!-- menu carregado no topo da dashboard -->

        <script>
            // deleta comprovante
            jQuery(document).on('click', '#link_delcompv', function() {
                var id_compv = jQuery(this).attr("data-compv");
                if (confirm('Realmente deseja excluir este comprovante?')) {
                    mka_link('../../executar_comprovante.hhvm?acao=delcomprovante&uuid=' + id_compv);
                    return false;
                }
            });
            // deleta mensagem
            jQuery(document).on('click', '#link_delmsg', function() {
                var uuid_msg = jQuery(this).attr("data-contato");
                if (confirm('Realmente deseja excluir este contato?')) {
                    mka_link('../../executar_mka.hhvm?acao=del.contato&uuid=' + uuid_msg);
                    return false;
                }
            });
            // deleta chamado
            jQuery(document).on('click', '#link_delchamado', function() {
                var chamado = jQuery(this).attr("data-chamado");
                if (confirm('Realmente deseja excluir este chamado?')) {
                    mka_link('../../executar_suporte.hhvm?acao=delhelp&chamado=' + chamado);
                    return false;
                }
            });
            // abre incluir cliente
            jQuery(document).on('click', '#link_insc', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja incluir esse cliente?')) {
                    mka_link('../../cliente_ins.hhvm?new_install=' + uuid_solic);
                    return false;
                }
            });
            // abre incluir instalacao
            jQuery(document).on('click', '#link_insi', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja transformar em uma nova instalacao?')) {
                    mka_link('../../executar_instalacao.hhvm?acao=install&uuid=' + uuid_solic);
                    return false;
                }
            });
            // abre link excluir
            jQuery(document).on('click', '#link_excluir', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja excluir esta solicitacao?')) {
                    mka_link('../../executar_mka.hhvm?acao=delsolic&uuid=' + uuid_solic);
                    return false;
                }
            });

            function ver_eventos(vregistro) {
                // busca sis_logs
                jQuery.ajax({
                    type: "GET",
                    url: "../../logs_ajax.hhvm",
                    data: "registro=" + vregistro + "&tipo=todos",
                    beforeSend: function() {
                        jQuery('#mostralogs').html('<img src="../../img/mkload.gif" hspace="2" vspace="2">');
                    },
                    success: function(txt) {
                        if (txt != 'ERRO') {
                            jQuery('#mostralogs').html(txt);
                        }
                    },
                    error: function(txt) {
                        alerta_baixo1("Desculpe, houve um problema interno");
                    }
                });
            }
            jQuery(document).ready(function() {
                run_shuffle();
                ver_eventos(0);
            });
        </script>
</body>

</html>
