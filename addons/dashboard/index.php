<?php
// =========================
// INÍCIO – BOOT PHP 8
// =========================
declare(strict_types=1);

// Carrega config (espera $conn, $usuario_logado, $Manifest, etc.)
include 'config.php';

// Sessão (evita "headers already sent" e notices)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Modo estrito do MySQLi ajuda a identificar problemas cedo (com try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Charset (evita problemas de acentuação)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

// Fallbacks para variáveis que o restante do código usa (evita notices de PHP 8)
$usuario_logado = $usuario_logado ?? ($_SESSION['MM_Usuario'] ?? '');
$busca          = $busca          ?? '';
$ext_mk         = $ext_mk         ?? '';
$now            = $now            ?? date('Y-m-d');           // Usado em títulos atrasados
$nova_data_1    = $nova_data_1    ?? date('Y-m-d');           // Usado em chamados/instalações
$nova_data_5    = $nova_data_5    ?? date('Y-m-d', strtotime('+5 days'));
$Manifest       = $Manifest       ?? (object)['name' => 'Sistema', 'version' => '0.0.0'];

// Helper seguro para escapar LIKE/igualdade simples quando não usar prepared statements
$esc = static function (mysqli $c, ?string $s): string {
    return $c->real_escape_string($s ?? '');
};

// =========================
// HEAD & HTML
// =========================
?>
<!DOCTYPE html>
<?php
// Classe no <html> conforme sua lógica
$htmlClass = isset($_SESSION['MM_Usuario']) ? '' : ' class="has-navbar-fixed-top"';
echo '<html lang="pt-BR"' . $htmlClass . '>';
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo htmlspecialchars($Manifest->name . " - V " . $Manifest->version, ENT_QUOTES, 'UTF-8'); ?></title>

    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css">
    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/estilo.css" rel="stylesheet" type="text/css" />

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>
    <script src="js/chart.min.js"></script>

    <!-- Chart.js (necessário para desenhar os gráficos) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        integrity="sha384-Va2oAG3bq8m6y9Wz0k5dQp7G4e2zv5k6rT0NQ4t8z5b0OikmHqK3hQbS5O4f6k5F"
        crossorigin="anonymous"></script>

</head>

<body>
<?php include_once('../../topo.php'); ?>
<?php include_once('mkauth_dashboard_top.php'); ?>

<?php
// =========================
// Notificações (COUNT(*) correto)
// =========================
$notificacoes_count = 0;
try {
    $u = $esc($conn, (string)$usuario_logado);
    $sql = "SELECT COUNT(*) AS total FROM dashboard_am_sis_notificacoes WHERE id_usuario = '$u'";
    $rs  = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($rs);
    $notificacoes_count = (int)($row['total'] ?? 0);
} catch (Throwable $e) {
    // Logue se desejar; evitar echo de erro em produção
    // echo $e->getMessage();
}
?>

<div class='container-fluid'>
<?php
// =========================
// Criação/Ajuste de Tabelas (idempotente)
// =========================
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_cfg (
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
    tot_acesso_rapido INT NOT NULL DEFAULT 15,
    PRIMARY KEY (id)
)");

$hasIdRem = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_notificacoes WHERE field = 'id_remetente'");
if (mysqli_num_rows($hasIdRem) == 0) {
    // Se estrutura antiga, recria (sua lógica original)
    mysqli_query($conn, "DROP TABLE IF EXISTS dashboard_am_sis_notificacoes");
}
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_notificacoes (
    id int NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_remetente INT NOT NULL,
    data DATETIME NOT NULL,
    mensagem TEXT NOT NULL,
    ativo VARCHAR(1) NOT NULL DEFAULT 's',
    PRIMARY KEY (id)
)");

// Garante colunas novas (idempotente)
$needLink = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'link'");
if (mysqli_num_rows($needLink) == 0) {
    mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD link TEXT,
        ADD texto TEXT AFTER limite_ticket");
}
$needBloq = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'contabilizar_bloq_offline'");
if (mysqli_num_rows($needBloq) == 0) {
    mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's',
        ADD exb_graficos_em_baixo VARCHAR(1) NOT NULL DEFAULT 'n' AFTER exb_busca_inteligente");
}
$needAdd = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'tot_acesso_rapido'");
if (mysqli_num_rows($needAdd) == 0) {
    mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD tot_acesso_rapido INT NOT NULL DEFAULT 15 AFTER texto");
}
$needRamal = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'exb_clientes_ramal'");
if (mysqli_num_rows($needRamal) == 0) {
    mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD exb_clientes_ramal VARCHAR(1) NOT NULL DEFAULT 'n' AFTER exb_saldo_conta");
}

// Configuração default (uma linha)
$qCfg = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_cfg");
if (mysqli_num_rows($qCfg) == 0) {
    mysqli_query($conn, "INSERT INTO dashboard_am_sis_cfg (id) VALUES (1)");
    $qCfg = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_cfg");
}

// Ler Configurações
$exb_ticket_medio = $exb_saldo_conta = $exb_clientes_ramal = $exb_balanco_faturamento = $exb_balanco_clientes = $exb_balanco_chamados =
$exb_busca_inteligente = $contabilizar_bloq_offline = $exb_graficos_em_baixo = $tbl_logs_sistema = $tbl_chamados_abertos = $tbl_contas_pagar = 's';
$qtd_meses_graficos = 3; $limite_ticket = 1000; $link=''; $texto='';
while ($cfg = mysqli_fetch_assoc($qCfg)) {
    $exb_ticket_medio        = $cfg['exb_ticket_medio']        ?? 's';
    $exb_saldo_conta         = $cfg['exb_saldo_conta']         ?? 's';
    $exb_clientes_ramal      = $cfg['exb_clientes_ramal']      ?? 'n';
    $exb_balanco_faturamento = $cfg['exb_balanco_faturamento'] ?? 's';
    $exb_balanco_clientes    = $cfg['exb_balanco_clientes']    ?? 's';
    $exb_balanco_chamados    = $cfg['exb_balanco_chamados']    ?? 's';
    $exb_busca_inteligente   = $cfg['exb_busca_inteligente']   ?? 's';
    $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'] ?? 's';
    $exb_graficos_em_baixo   = $cfg['exb_graficos_em_baixo']   ?? 'n';
    $tbl_logs_sistema        = $cfg['tbl_logs_sistema']        ?? 's';
    $tbl_chamados_abertos    = $cfg['tbl_chamados_abertos']    ?? 's';
    $tbl_contas_pagar        = $cfg['tbl_contas_pagar']        ?? 's';
    $qtd_meses_graficos      = (int)($cfg['qtd_meses_graficos'] ?? 3);
    $limite_ticket           = (int)($cfg['limite_ticket'] ?? 1000);
    $link                    = $cfg['link']  ?? '';
    $texto                   = $cfg['texto'] ?? '';
}

// =========================
// Grupos do usuário logado
// =========================
$grupos_permitidos = '';
$q = mysqli_query($conn, "SELECT cli_grupos FROM sis_acesso WHERE login LIKE '" . $esc($conn, $usuario_logado) . "' AND cli_grupos NOT LIKE 'full_clientes%'");
while ($row = mysqli_fetch_assoc($q)) {
    $grupos_permitidos = $row['cli_grupos'];
}

if ($grupos_permitidos === '' || $grupos_permitidos === null) {
    $grupos = "c.grupo LIKE '%' AND";
} else {
    $grupos_permitidos = explode(",", $grupos_permitidos);
    $grupos = "(c.grupo = ";
    foreach ($grupos_permitidos as $value) {
        if ($value === "ped_fil") { continue; }
        if ($value === "full_clientes") {
            $grupos = "(c.grupo LIKE '%' OR c.grupo = ";
            break;
        }
        $grupos .= "'" . $esc($conn, $value) . "' OR c.grupo = ";
    }
    if ($grupos !== "(c.grupo = ") {
        $grupos = substr($grupos, 0, -14);
        $grupos .= ") AND ";
    } else {
        $grupos = "";
    }
}

// =========================
// Busca Inteligente + Datalist
// =========================
$per_meses = $qtd_meses_graficos;
$string_per = "$per_meses meses";

if ($exb_busca_inteligente === 's'): ?>
    <datalist id="sugestoes">
        <option value="on"><option value="off"><option value="adicionais"><option value="bloqueado">
        <option value="atrasado"><option value="observacao"><option value="desativado">
        <option value="sem carne"><option value="sem titulo"><option value="venc+"><option value="conta+">
        <option value="parcelas abertas+">
        <?php
        $query_sugestoes = mysqli_query($conn, "SELECT DISTINCT nome FROM sis_cliente ORDER BY nome");
        while ($s = mysqli_fetch_assoc($query_sugestoes)) {
            $s_nome = $s['nome'];
            echo "<option value='" . htmlspecialchars($s_nome, ENT_QUOTES, 'UTF-8') . "'>";
        }
        ?>
    </datalist>

    <div class="row">
        <div class="col-auto">
            <a href="dash_notificacoes.php" onclick="window.open(this.href, this.target, 'width=650, height=700, scrollbars=yes'); return false;">
                <img src="img/icon_notificacao.png" alt="Notificações">
            </a>
        </div>
        <div class="col">
            <form action="/admin/addons/busca_rapida/index.php" method="get" class="form-inline">
                <div class="row g-1">
                    <div class="col form-floating mb-3">
                        <input type="search" name="busca" class="form-control" id="floatingInput" placeholder="Busca Inteligente"
                               value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>" list="sugestoes">
                        <label for="floatingInput">(Busca Rapida) Digite o que procura:</label>
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
<?php endif; ?>

<?php
// =========================
// Cálculos de clientes
// =========================
$username_on = []; // evita "undefined variable"
$query_clientes_online = mysqli_query($conn,
    "SELECT r.username 
     FROM radacct r 
     LEFT JOIN sis_cliente c ON c.login = r.username 
     WHERE $grupos r.acctstoptime IS NULL");
while ($row3 = mysqli_fetch_assoc($query_clientes_online)) {
    $k = trim(strtolower($row3['username']));
    $username_on[$k] = $k;
}

$query_clientes_ativos = mysqli_query($conn, "SELECT c.login, c.bloqueado, c.tit_vencidos, c.observacao FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's'");
$query_clientes_adicionais = mysqli_query($conn, "SELECT cli_add.login as login_add FROM sis_adicional cli_add LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE $grupos c.cli_ativado LIKE 's'");

$cli_ = $cli_bloq = $cli_on = $cli_bloq_online = $cli_obs = 0;
while ($row = mysqli_fetch_assoc($query_clientes_ativos)) {
    if (($row['bloqueado'] ?? '') === 'sim') { $cli_bloq++; }
    if (($row['observacao'] ?? '') === 'sim') { $cli_obs++; }
    $u = trim(strtolower($row['login'] ?? ''));
    if ($u !== '' && isset($username_on[$u])) {
        $cli_on++;
        if (($row['bloqueado'] ?? '') === 'sim') { $cli_bloq_online++; }
    }
    $cli_++;
}

$cli_atraso = 0;
$qTitulos = mysqli_query($conn, "SELECT l.login FROM sis_lanc l 
    LEFT JOIN sis_cliente c ON l.login = c.login 
    WHERE l.status NOT LIKE 'pago' 
      AND l.deltitulo = 0 
      AND l.datavenc <= '" . $esc($conn, $now) . "' 
      AND c.cli_ativado = 's' 
    GROUP BY l.login");
while ($row = mysqli_fetch_assoc($qTitulos)) { $cli_atraso++; }

$c_add = 0;
while ($row = mysqli_fetch_assoc($query_clientes_adicionais)) {
    $u = trim(strtolower($row['login_add'] ?? ''));
    if ($u !== '' && isset($username_on[$u])) { $cli_on++; }
    $c_add++;
}

$tot_clientes = $cli_ + $c_add;
$tot_clientes_livres = $cli_ - $cli_bloq;

if ($contabilizar_bloq_offline === 's') {
    $cli_offline = $tot_clientes - $cli_on;
} else {
    $cli_offline = $tot_clientes - $cli_on - $cli_bloq + $cli_bloq_online;
    $cli_atraso = max(0, $cli_atraso - $cli_bloq);
}

// Sem carnê / Sem título
$cli_sem_carne   = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.parc_abertas LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'carne'");
$tot_sem_carne   = mysqli_num_rows($cli_sem_carne);
$cli_sem_titulo  = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.tit_abertos LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'titulo'");
$tot_sem_titulo  = mysqli_num_rows($cli_sem_titulo);

// Percentuais com divisões seguras
$nf = static function (float $a, float $b): string { return $b > 0 ? number_format(($a / $b) * 100, 2) : '0.00'; };
$perc_clientes_livres        = $nf((float)$tot_clientes_livres, (float)max(1, $cli_));
$perc_cliente_sem_adicionais = $nf((float)$cli_,               (float)max(1, $tot_clientes));
$perc_clientes_adicional     = $nf((float)$c_add,              (float)max(1, $tot_clientes));
$perc_clientes_bloqueado     = $nf((float)$cli_bloq,           (float)max(1, $cli_));
$perc_clientes_observacao    = $nf((float)$cli_obs,            (float)max(1, $cli_));
$perc_clientes_atrasado      = $nf((float)$cli_atraso,         (float)max(1, $cli_));
$perc_clientes_online        = $nf((float)$cli_on,             (float)max(1, $tot_clientes));
$perc_clientes_offline       = $nf((float)$cli_offline,        (float)max(1, $tot_clientes));
$perc_clientes_sem_carne     = $nf((float)$tot_sem_carne,      (float)max(1, $tot_clientes));
$perc_clientes_sem_titulo    = $nf((float)$tot_sem_titulo,     (float)max(1, $tot_clientes));
?>

<?php include 'graf_periodo.php'; ?>
<?php include 'cli_periodo.php'; ?>
<?php include 'cli_chamado_per.php'; ?>
<?php include 'cli_planos.php'; ?>

<div class='row mb-2'>
    <div class='col-12 col-md-12 col-lg-9 mb-2'>
        <!-- Card Clientes -->
        <div class='card border-light'>
            <div class="card-header text-uppercase">Clientes - <?php echo (int)$tot_clientes; ?></div>
            <div class="card-body m-0 p-1">
                <div class='row g-1'>
                    <!-- Total -->
                    <div class='col'>
                        <div class="card text-bg-primary">
                            <div class="card-header">Total</div>
                            <a href="/admin/addons/busca_rapida/index.php" class="text-light">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_cliente_sem_adicionais . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Adicional -->
                    <div class='col'>
                        <div class="card text-bg-light">
                            <div class="card-header">Adicional</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=adicionais" class="text-dark">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$c_add; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_adicional . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Livres -->
                    <div class='col'>
                        <div class="card text-bg-info">
                            <div class="card-header">Livres</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=" class="text-dark">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$tot_clientes_livres; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_livres . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Observação -->
                    <div class='col'>
                        <div class="card text-bg-warning">
                            <div class="card-header">Observação</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=obs" class="text-dark">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_obs; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_observacao . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Bloqueado -->
                    <div class='col'>
                        <div class="card text-bg-danger">
                            <div class="card-header">Bloqueado</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=bloq" class="text-light">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_bloq; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_bloqueado . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Atraso -->
                    <div class='col'>
                        <div class="card text-bg-warning">
                            <div class="card-header">Atraso</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=atrasado" class="text-dark">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_atraso; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_atrasado . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Online -->
                    <div class='col'>
                        <div class="card text-bg-success">
                            <div class="card-header">Online</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=on" class="text-light">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_on; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_online . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Offline -->
                    <div class='col'>
                        <div class="card text-bg-dark">
                            <div class="card-header">Offline</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=off" class="text-light">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$cli_offline; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_offline . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Sem Carnê -->
                    <div class='col'>
                        <div class="card border-danger">
                            <div class="card-header">Sem Carnê</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=sem carne" class="text-dark">
                                <div class="card-body text-danger m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$tot_sem_carne; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_sem_carne . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <!-- Sem Títulos -->
                    <div class='col'>
                        <div class="card border-danger">
                            <div class="card-header">Sem Títulos</div>
                            <a href="/admin/addons/busca_rapida/index.php?busca=sem tit" class="text-dark">
                                <div class="card-body text-danger m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?php if (permissao('perm_totais')) echo (int)$tot_sem_titulo; ?></p>
                                    <p class="text-center m-0 p-0"><?php echo $perc_clientes_sem_titulo . '%'; ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div><!-- row -->
            </div>
        </div>
    </div>

    <?php
    // ======== Atendimentos (topo direito)
    ?>
    <div class='col-12 col-md-12 col-lg-3 mb-2'>
        <div class='card border-light'>
            <div class="card-header text-uppercase">Atendimentos</div>
            <div class="card-body m-0 p-1">
                <div class="row g-1">
                    <div class="col">
                        <div class="card text-bg-light">
                            <div class="card-header">Chamados</div>
                            <div class="card-body m-0 p-0">
                                <p class="display-6 m-0 p-0 text-center" id="tot_chamados"></p>
                                <p class="text-center m-0 p-0" id="perc_chamados">0.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card text-bg-light">
                            <div class="card-header">Instalações</div>
                            <div class="card-body m-0 p-0">
                                <p class="display-6 m-0 p-0 text-center" id="tot_instalacoes"></p>
                                <p class="text-center m-0 p-0" id="perc_instalacoes">0.00</p>
                            </div>
                        </div>
                    </div>
                    <?php
                    $dd = date('Y-m');
                    $result = mysqli_query($conn, "SELECT ativ.login FROM sis_ativ ativ WHERE ativ.registro LIKE 'acessou a central do assinante' AND ativ.data LIKE '$dd%'");
                    $totAcessos = mysqli_num_rows($result);
                    ?>
                    <div class="col">
                        <div class="card text-bg-light">
                            <div class="card-header">Central</div>
                            <a href="relAcessoCentral.php">
                                <div class="card-body m-0 p-0">
                                    <p class="display-6 m-0 p-0 text-center"><?= (int)$totAcessos; ?></p>
                                    <p class="text-center m-0 p-0">.</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div><!-- row -->
            </div>
        </div>
    </div>
</div><!-- /row topo -->

<?php
// =========================
// Ramais (Clientes por NAS)
// =========================
$query_ramal = mysqli_query($conn, "SELECT nasname, shortname FROM nas");
$ramal_cliente = [];
while ($lista_ramal = mysqli_fetch_assoc($query_ramal)) {
    $ramal_cliente[$lista_ramal['nasname']] = $lista_ramal['shortname'];
}

if ((permissao('perm_relFin') || permissao('perm_relFat')) && $exb_clientes_ramal === 's'): ?>
    <div class='col-12 col-md-12 col-lg-12'>
        <div class='card border-light mb-2'>
            <div class="card-header text-uppercase">Clientes Ramais</div>
            <div class="card-body m-0 p-0">
                <div class="row g-1">
                    <?php foreach ($ramal_cliente as $key => $value):
                        $nomeRamal = $value;
                        $kEsc = $esc($conn, $key);

                        $queryClientes           = mysqli_query($conn, "SELECT login FROM sis_cliente WHERE ramal LIKE '$kEsc' AND cli_ativado LIKE 's'");
                        $queryAdicionais         = mysqli_query($conn, "SELECT cli_add.login as login_add FROM sis_adicional cli_add LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE cli_add.ramal LIKE '$kEsc' AND c.cli_ativado LIKE 's'");
                        $totGeral                = mysqli_num_rows($queryClientes) + mysqli_num_rows($queryAdicionais);
                        $queryOnline             = mysqli_query($conn, "SELECT r.username FROM radacct r LEFT JOIN sis_cliente c ON r.username = c.login WHERE c.ramal LIKE '$kEsc' AND c.cli_ativado LIKE 's' AND r.acctstoptime IS NULL");
                        $queryAdicionaisOnline   = mysqli_query($conn, "SELECT r.username FROM radacct r LEFT JOIN sis_adicional cli_add ON r.username = cli_add.username LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE cli_add.ramal LIKE '$kEsc' AND c.cli_ativado LIKE 's' AND r.acctstoptime IS NULL");
                        $totOnline               = mysqli_num_rows($queryOnline) + mysqli_num_rows($queryAdicionaisOnline);
                        $totOffline              = max(0, $totGeral - $totOnline);
                    ?>
                        <div class="col">
                            <div class="card text-bg-light">
                                <div class="card-header"><?php echo htmlspecialchars($nomeRamal, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="row g-1">
                                    <div class="col">
                                        <div class="card-body m-0 p-0">
                                            <div class="card text-bg-dark">
                                                <div class="card-header">Total</div>
                                                <div class="card-body m-0 p-0">
                                                    <p class="display-6 m-0 p-0 text-center">
                                                        <a href="<?php echo '/admin/addons/busca_rapida/index.php?busca=ramal%2B' . rawurlencode($key); ?>" class="text-light"><?php echo (int)$totGeral; ?></a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card-body m-0 p-0">
                                            <div class="card text-bg-success">
                                                <div class="card-header">Online</div>
                                                <div class="card-body m-0 p-0">
                                                    <p class="display-6 m-0 p-0 text-center">
                                                        <a href="<?php echo '/admin/addons/busca_rapida/index.php?busca=on%2B' . rawurlencode($key); ?>" class="text-light"><?php echo (int)$totOnline; ?></a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card-body m-0 p-0">
                                            <div class="card text-bg-danger">
                                                <div class="card-header">Offline</div>
                                                <div class="card-body m-0 p-0">
                                                    <p class="display-6 m-0 p-0 text-center text-light">
                                                        <a href="<?php echo '/admin/addons/busca_rapida/index.php?busca=off%2B' . rawurlencode($key); ?>" class="text-light"><?php echo (int)$totOffline; ?></a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- row -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div><!-- row -->
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Acesso Rápido -->
<div class='col-12 col-md-12 col-lg-12'>
    <div class='card border-light mb-2'>
        <div class="card-header text-uppercase">Acesso Rápido</div>
        <div class="card-body m-0 p-0">
            <div class="d-flex flex-wrap justify-content-center">
                <?php if (permissao('perm_config')): ?>
                    <div class='p-1'>
                        <a href='cfg.php' class='btn btn-primary m-0 px-2'>
                            <img src='img/icon_config.png' class='align-middle icon_sm_2' title='Configurações Dash Board' />
                            Configurações
                        </a>
                    </div>
                <?php endif; ?>

                <?php
                // Usa str_starts_with (PHP 8) no lugar do startsWith custom
                $links  = array_filter(array_map('trim', explode(',', (string)$link)), static fn($v) => $v !== '');
                $textos = array_map('trim', explode(',', (string)$texto));
                $indice = 0;
                foreach ($links as $k) {
                    $label = $textos[$indice] ?? $k;
                    if (str_starts_with(strtolower($k), 'http')) {
                        echo "<div class='p-1'><a href='" . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . "' target='_blank' class='btn btn-success m-0 px-2'>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a></div>";
                    } else {
                        echo "<div class='p-1'><a href='/admin/" . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . "' class='btn btn-secondary m-0 px-2'>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a></div>";
                    }
                    $indice++;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
if ($exb_graficos_em_baixo === 'n') {
    include_once('graficos.php');
}
?>

<div class='row'>
    <!-- Logs do Sistema -->
    <?php if (permissao('perm_logs') && $tbl_logs_sistema === 's'): ?>
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
                while ($l = mysqli_fetch_assoc($query_sis_logs)) {
                    $log_registro = $l['registro'];
                    $log_data     = $l['data'];
                    $log_login    = $l['login'];
                    ?>
                    <tr class='font-field'>
                        <td><?php echo htmlspecialchars($log_registro, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($log_data, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($log_login, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan='100%'><a href='<?php echo "/admin/logs_sistema$ext_mk"; ?>' class='fw-bold'>VER MAIS...</a></td>
                </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

    <!-- Chamados -->
    <?php if (permissao('perm_chamados') && $tbl_chamados_abertos === 's'): ?>
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
                <?php
                $qSup = mysqli_query($conn, "SELECT s.chamado, s.abertura, s.login, s.assunto, c.nome 
                    FROM sis_suporte s 
                    LEFT JOIN sis_cliente c ON s.login = c.login 
                    WHERE $grupos s.visita <= '" . $esc($conn, $nova_data_1) . "'
                      AND s.status LIKE 'aberto' AND c.cli_ativado LIKE 's' 
                    ORDER BY s.abertura");
                $tot_chamados = mysqli_num_rows($qSup);
                if (permissao('perm_totais')) {
                    echo "<script>$('#tot_chamados').html(" . (int)$tot_chamados . ")</script>";
                }
                $ccSup = 0;
                while ($sup = mysqli_fetch_assoc($qSup)) {
                    $sup_chamado = $sup['chamado'];
                    $sup_abertura = date('d/m/Y H:i:s', strtotime($sup['abertura']));
                    $sup_nome = $sup['nome'];
                    $sup_login = $sup['login'];
                    $sup_assunto = $sup['assunto']; ?>
                    <tr class='font-field'>
                        <td>
                            <strong>
                                <a href="/admin/chamado<?php echo $ext_mk; ?>?chamado=<?php echo urlencode((string)$sup_chamado); ?>"
                                   onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;"
                                   class="btn-link text-decoration-none">Abrir</a>
                                <a href="/admin/suporte_info<?php echo $ext_mk; ?>?login=<?php echo urlencode((string)$sup_login); ?>&chamado=<?php echo urlencode((string)$sup_chamado); ?>"
                                   onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;"
                                   class="btn-link text-decoration-none">Info</a>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars((string)$sup_chamado, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$sup_abertura, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$sup_nome, ENT_QUOTES, 'UTF-8') . " <strong>[" . htmlspecialchars((string)$sup_login, ENT_QUOTES, 'UTF-8') . "]</strong>"; ?></td>
                        <td><?php echo htmlspecialchars((string)$sup_assunto, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php
                    $ccSup++;
                    if ($ccSup >= 15) { break; }
                } ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Instalacoes -->
    <?php if (permissao('perm_instalacao')): ?>
        <div class='col-12 mb-3'>
            <table class='table table-sm'>
                <thead>
                <tr class="bg-primary text-uppercase">
                    <th class='fw-bold text-light'></th>
                    <th class='fw-bold text-light'>Instalação</th>
                    <th class='fw-bold text-light'>Nome</th>
                    <th class='fw-bold text-light'>Técnico</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $qInst = mysqli_query($conn, "SELECT * FROM sis_solic 
                    WHERE status = 'aberto' 
                      AND (datainst <= '" . $esc($conn, $nova_data_1) . "' OR visita <= '" . $esc($conn, $nova_data_1) . "' OR datainst IS NULL) 
                    ORDER BY datainst");
                $tot_instalacoes = mysqli_num_rows($qInst);

                $tot_atendimento = max(1, $tot_chamados + $tot_instalacoes);
                $perc_chamados = number_format(($tot_chamados / $tot_atendimento) * 100, 2);
                $perc_instalacoes = number_format(($tot_instalacoes / $tot_atendimento) * 100, 2);

                if (permissao('perm_totais')) {
                    echo "<script>$('#tot_instalacoes').html(" . (int)$tot_instalacoes . ")</script>";
                }
                echo "<script>$('#perc_instalacoes').html(\"$perc_instalacoes%\")</script>";
                echo "<script>$('#perc_chamados').html(\"$perc_chamados%\")</script>";

                $ccInst = 0;
                while ($inst = mysqli_fetch_assoc($qInst)) {
                    $inst_uuid      = $inst['uuid_solic'];
                    $inst_disponivel= (($inst['disp'] ?? '') === "sim") ? "Disponível" : "Indisponível";
                    $inst_nome      = $inst['nome'];
                    $inst_login     = $inst['login'];
                    $inst_tecnico   = $inst['tecnico']; ?>
                    <tr class='font-field'>
                        <td>
                            <strong>
                                <a href="/admin/instalar_alt<?php echo $ext_mk; ?>?uuid=<?php echo urlencode((string)$inst_uuid); ?>" class="btn-link text-decoration-none">Alterar</a>
                                <a href="/admin/instalacao_info<?php echo $ext_mk; ?>?uuid=<?php echo urlencode((string)$inst_uuid); ?>"
                                   onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;"
                                   class="btn-link text-decoration-none">Info</a>
                                <a href="/admin/cliente_ins<?php echo $ext_mk; ?>?new_install=<?php echo urlencode((string)$inst_uuid); ?>" class="btn-link text-decoration-none" target="_blank">Incluir Cliente</a>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars($inst_disponivel, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$inst_nome, ENT_QUOTES, 'UTF-8') . " <strong>[" . htmlspecialchars((string)$inst_login, ENT_QUOTES, 'UTF-8') . "]</strong>"; ?></td>
                        <td><?php echo htmlspecialchars((string)$inst_tecnico, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php
                    $ccInst++;
                    if ($ccInst >= 15) { break; }
                } ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Contas a pagar -->
    <?php if (permissao('perm_contaspagar') && $tbl_contas_pagar === 's'): ?>
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
                $qCP = mysqli_query($conn, "SELECT c.id AS id_conta, c.nrdocumento, c.parcatual, c.numparcelas, c.historico, c.vencimento, c.valor, 
                                                    f.razaosoc, c.tipodiv, func.nome , c.uuid_contaspagar
                                           FROM sis_contaspagar c 
                                           LEFT JOIN sis_fornecedor f ON c.fornecedor = f.id 
                                           LEFT JOIN sis_func func ON c.fornecedor = func.id
                                           WHERE c.status NOT LIKE 'liquidado' 
                                             AND c.vencimento <= '" . $esc($conn, $nova_data_5) . "'
                                           ORDER BY c.vencimento");
                $ccCP = 0;
                while ($cp = mysqli_fetch_assoc($qCP)) {
                    $cp_uuid_contaspagar = $cp['uuid_contaspagar'];
                    $cp_nrdocumento      = $cp['nrdocumento'];
                    $cp_parcatual        = $cp['parcatual'];
                    $cp_numparcelas      = $cp['numparcelas'];
                    $cp_fornecedor       = ($cp['tipodiv'] === "for")
                        ? ($cp['razaosoc'] . " - " . $cp['historico'])
                        : ($cp['nome']     . " - " . $cp['historico']);
                    $cp_vencimento       = date('d/m/Y', strtotime($cp['vencimento']));
                    $cp_valor            = number_format((float)$cp['valor'], 2, ',', '.'); ?>
                    <tr class='font-field'>
                        <td>
                            <strong>
                                <a href="/admin/contaspagar_liquidar<?php echo $ext_mk; ?>?uuid=<?php echo urlencode((string)$cp_uuid_contaspagar); ?>" class="btn-link text-decoration-none">Liquidar</a>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars((string)$cp_nrdocumento, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$cp_parcatual . " / " . (int)$cp_numparcelas; ?></td>
                        <td><?php echo htmlspecialchars((string)$cp_fornecedor, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$cp_vencimento, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>R$ <?php echo $cp_valor; ?></td>
                    </tr>
                <?php
                    $ccCP++;
                    if ($ccCP >= 15) { break; }
                } ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div><!-- /row -->

<?php
if ($exb_graficos_em_baixo === 's') {
    include_once('graficos.php');
}
?>


<!-- Rodapé -->
<?php include_once('../../baixo.php'); ?>

<!-- Scripts finais -->
<script src="../../menu.js.hhvm"></script>

<script>
// ======= AÇÕES AJAX/MKA =======
jQuery(document).on('click', '#link_delcompv', function() {
    const id_compv = jQuery(this).attr("data-compv");
    if (confirm('Realmente deseja excluir este comprovante?')) {
        mka_link('../../executar_comprovante.hhvm?acao=delcomprovante&uuid=' + encodeURIComponent(id_compv));
        return false;
    }
});
jQuery(document).on('click', '#link_delmsg', function() {
    const uuid_msg = jQuery(this).attr("data-contato");
    if (confirm('Realmente deseja excluir este contato?')) {
        mka_link('../../executar_mka.hhvm?acao=del.contato&uuid=' + encodeURIComponent(uuid_msg));
        return false;
    }
});
jQuery(document).on('click', '#link_delchamado', function() {
    const chamado = jQuery(this).attr("data-chamado");
    if (confirm('Realmente deseja excluir este chamado?')) {
        mka_link('../../executar_suporte.hhvm?acao=delhelp&chamado=' + encodeURIComponent(chamado));
        return false;
    }
});
jQuery(document).on('click', '#link_insc', function() {
    const uuid_solic = jQuery(this).attr("data-solic");
    if (confirm('Realmente deseja incluir esse cliente?')) {
        mka_link('../../cliente_ins.hhvm?new_install=' + encodeURIComponent(uuid_solic));
        return false;
    }
});
jQuery(document).on('click', '#link_insi', function() {
    const uuid_solic = jQuery(this).attr("data-solic");
    if (confirm('Realmente deseja transformar em uma nova instalacao?')) {
        mka_link('../../executar_instalacao.hhvm?acao=install&uuid=' + encodeURIComponent(uuid_solic));
        return false;
    }
});
jQuery(document).on('click', '#link_excluir', function() {
    const uuid_solic = jQuery(this).attr("data-solic");
    if (confirm('Realmente deseja excluir esta solicitacao?')) {
        mka_link('../../executar_mka.hhvm?acao=delsolic&uuid=' + encodeURIComponent(uuid_solic));
        return false;
    }
});

function ver_eventos(vregistro) {
    jQuery.ajax({
        type: "GET",
        url: "../../logs_ajax.hhvm",
        data: { registro: vregistro, tipo: "todos" },
        beforeSend: function() {
            jQuery('#mostralogs').html('<img src="../../img/mkload.gif" hspace="2" vspace="2">');
        },
        success: function(txt) {
            if (txt !== 'ERRO') {
                jQuery('#mostralogs').html(txt);
            }
        },
        error: function() {
            alerta_baixo1("Desculpe, houve um problema interno");
        }
    });
}
jQuery(function() {
    if (typeof run_shuffle === 'function') { run_shuffle(); }
    ver_eventos(0);
});
</script>

<?php
// Fecha conexão (MariaDB)
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}
?>

<?php include('../../rodape.php'); ?>
</body>
</html>
