<?php include('nav/header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>
    <?php require_once __DIR__ . '/../shared/layout_mode.php'; mka_suite_render_top_spacing_style($link); ?>

    <style>
        .ticket-toolbar { display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:8px; margin:10px 15px 18px; padding:10px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.06); }
        .ticket-toolbar a { display:inline-flex; align-items:center; gap:7px; padding:9px 12px; border-radius:11px; color:#36506c; text-decoration:none; font-size:13px; font-weight:700; transition:background .18s ease,color .18s ease,transform .18s ease; }
        .ticket-toolbar a:hover { background:#edf5ff; color:#1268db; transform:translateY(-1px); }
        .ticket-toolbar a.is-active { background:#1268db; color:#fff; }
        .ticket-toolbar i { font-size:16px; }
        .ticket-search { display:grid; grid-template-columns:minmax(260px,2fr) minmax(170px,1fr) minmax(170px,1fr) minmax(170px,.8fr) auto; gap:10px; margin:0 15px 12px; padding:14px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.04); }
        .ticket-search label { margin:0; color:#334155; font-size:12px; font-weight:700; }
        .ticket-search input,.ticket-search select { display:block; width:100%; height:42px; margin-top:6px; padding:8px 11px; border:1px solid #cbd8e8; border-radius:10px; background:#fff; box-sizing:border-box; }
        .ticket-search button { align-self:end; height:42px; padding:0 20px; border:0; border-radius:11px; background:#1268db; color:#fff; font-weight:700; cursor:pointer; }
        .ticket-count { margin:0 15px 10px; color:#334155; font-weight:700; }
        .ticket-list { display:grid; gap:10px; margin:0 15px 18px; }
        .ticket-item { border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.05); overflow:hidden; }
        .ticket-item summary { display:grid; grid-template-columns:minmax(190px,1.1fr) minmax(240px,1.6fr) minmax(150px,.8fr) minmax(145px,.75fr) minmax(145px,.75fr) 26px; gap:14px; align-items:center; padding:15px 18px; cursor:pointer; list-style:none; }
        .ticket-item summary::-webkit-details-marker { display:none; }
        .ticket-item summary:hover { background:#f5f9ff; }
        .ticket-summary-cell { min-width:0; }
        .ticket-summary-cell small { display:block; margin-bottom:4px; color:#718096; font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
        .ticket-summary-cell strong,.ticket-summary-cell span { display:block; overflow:hidden; color:#23364d; text-overflow:ellipsis; white-space:nowrap; }
        .ticket-client { color:#1268db !important; text-decoration:none; }
        .ticket-sla { display:inline-flex!important; width:max-content; max-width:100%; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:800; }
        .ticket-sla.is-green { background:#e7f7ee; color:#16794a; }.ticket-sla.is-orange { background:#fff1d6; color:#a85c00; }.ticket-sla.is-red { background:#fde7ea; color:#b42336; }.ticket-sla.is-neutral { background:#edf2f7; color:#64748b; }
        .ticket-chevron { color:#1268db; transition:transform .2s ease; }
        .ticket-item[open] .ticket-chevron { transform:rotate(180deg); }
        .ticket-details { padding:0 18px 18px; border-top:1px solid #e6edf5; }
        .ticket-detail-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding:14px 0; }
        .ticket-detail { padding:12px; border:1px solid #e1e9f3; border-radius:12px; background:#f8fafc; }
        .ticket-detail small { display:block; margin-bottom:5px; color:#718096; font-size:10px; font-weight:800; text-transform:uppercase; }
        .ticket-reports { padding:14px; border-radius:12px; background:#f4f6f9; }
        .ticket-report { margin:8px 0 0; padding-top:8px; border-top:1px solid #dce3ec; line-height:1.45; }
        .ticket-signature { margin-top:10px; padding:12px 14px; border-radius:12px; background:#eef9fb; }
        .ticket-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:12px; }
        .ticket-close { display:inline-flex; align-items:center; gap:7px; padding:10px 14px; border-radius:11px; background:#dc3545; color:#fff; text-decoration:none; font-weight:700; }
        @media(max-width:1100px) { .ticket-search { grid-template-columns:repeat(2,minmax(0,1fr)); } .ticket-search button { width:100%; } .ticket-item summary { grid-template-columns:1fr 1fr 1fr 26px; } .ticket-summary-date { display:none; } .ticket-detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:600px) { .ticket-toolbar span { display:none; } .ticket-search { grid-template-columns:1fr; margin-inline:8px; } .ticket-list,.ticket-count { margin-inline:8px; } .ticket-item summary { grid-template-columns:1fr 26px; } .ticket-summary-address,.ticket-summary-tech { display:none; } .ticket-detail-grid { grid-template-columns:1fr; } }
    </style>

    <nav class="ticket-toolbar no_print mka-suite-content-start" aria-label="Navegação de chamados">
        <a href="#" onclick="window.history.back(); return false;"><i class="bi bi-arrow-left-circle-fill"></i><span>Voltar</span></a>
        <a href="index.php"><i class="bi bi-house-door-fill"></i><span><?= htmlspecialchars($Manifest->{'name'} . ' - V ' . $Manifest->{'version'}, ENT_QUOTES, 'UTF-8'); ?></span></a>
        <a href="cli_conn_alerta.php"><i class="bi bi-exclamation-circle-fill"></i><span>Alertas</span></a>
        <a href="chamados_abertos.php" class="is-active"><i class="bi bi-headset"></i><span>Chamados</span></a>
        <a href="score.php"><i class="bi bi-bar-chart-fill"></i><span>Score</span></a>
        <a href="relcontratos.php"><i class="bi bi-file-earmark-text-fill"></i><span>Contratos</span></a>
        <a href="cfg.php"><i class="bi bi-gear-fill"></i><span>Configurações</span></a>
        <a href="#" onclick="window.print(); return false;"><i class="bi bi-printer-fill"></i><span>Imprimir</span></a>
    </nav>

    <?php

    $busca = isset($_GET['busca']) ? $_GET['busca'] : '';

    $busca=trim($busca);		
    $busca = str_replace(" ","%", $busca);	

    $organizar = isset($_GET['organizar']) ? $_GET['organizar'] : 'endereco';

    $lista_organizar = array(
            "endereco"  => "endereco A-Z",
            "endereco DESC"  => "endereco Z-A",
            "nome" => "nome A-Z",
            "nome DESC" => "nome Z-A"
    );
    if (!isset($lista_organizar[$organizar])) {
        $organizar = 'endereco';
    }

    
    $query_motivo_chamado = mysqli_query($link, "SELECT DISTINCT assunto FROM sis_suporte ORDER BY assunto");
    $motivo_chamado["%"] = "TODOS";
    while ($row2 = mysqli_fetch_array($query_motivo_chamado)){
        $motivo_chamado[$row2['assunto']] = $row2['assunto'];
    }

    $assunto_get = isset($_GET['assunto']) ? $_GET['assunto'] : '';
    if ($assunto_get == "TODOS" || $assunto_get == ""){
        $assunto = "%";
    }else{
        $assunto = $assunto_get;
    }

    $query_tecnico_chamado = mysqli_query($link, "SELECT id, nome FROM sis_func ORDER BY nome");
    $tecnico_chamado["%"] = "TODOS";
    while ($row3 = mysqli_fetch_array($query_tecnico_chamado)){
        $tecnico_chamado[$row3['id']] = $row3['nome'];
    }

    $tecnico_get = isset($_GET['tecnico']) ? $_GET['tecnico'] : '';
    if ($tecnico_get == "TODOS" || $tecnico_get == ""){
        $tecnico = "%";
    }else{
        $tecnico = $tecnico_get;
    }

    ?>

    <form action="" method="get" id="form_pesquisa" class="no_print ticket-search">
            <label>Pesquisar
                <input type="search" name="busca" placeholder="Busque por nome ou endereço" value="<?php echo htmlspecialchars(str_replace('%', ' ', $busca), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label>Assunto
                <select name="assunto">
                    <?php
                        foreach ($motivo_chamado as $key => $value) {
                            $selected = ($assunto == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                    }
                    ?>
                </select>
            </label>
            <label>Técnico
                <select name="tecnico">
                    <?php
                        foreach ($tecnico_chamado as $key => $value) {
                            $selected = ($tecnico == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                    }
                    ?>
                </select>
            </label>
            <label>Organizar por
            <select name="organizar">
            <?php
                foreach ($lista_organizar as $key => $value) {
                    $selected = ($organizar == $key) ? "selected=\"selected\"" : null;
                    echo "<option value=\"$key\" $selected >$value</option>";
            }
            ?>
            </select></label>
            <button type="submit" name="submit" value="Buscar"><i class="bi bi-search"></i> Buscar</button>
    </form>


    <?php

    /*$key = "-i /root/.ssh/id_rsa";
    $ssh = shell_exec("sudo ssh -l mkauth@10.10.150.1 -p 22");
    if($ssh){
        echo "foi!";
    }else{
        echo "error!";
    }*/
    //echo $comando;  'ip firewall address-list add address=10.10.150.3 list=test3
  // \"ipv6 firewall address-list add list=BloqV6 comment=$usuario address=[/ipv6 pool used get [find info=$usuario] prefix]\" 
?>


    <?php
        //$lista_chamados = mysqli_query($link, "SELECT * FROM sis_suporte WHERE status = 'aberto' AND login IN (SELECT login FROM sis_cliente WHERE cli_ativado = 's' ORDER BY endereco)");


        
        // Carrega as configuracoes do banco
        $ler_busca_inteligente_cfg = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg");
        while($cfg = mysqli_fetch_array($ler_busca_inteligente_cfg)){
            $links_ext = $cfg['links_ext'];
        }

        function mascara($mascara,$string){
            $del_chars = array (' ', '(', ')', '-');
            $string = str_replace($del_chars,"",$string);
            for($i=0;$i<strlen($string);$i++){
                $mascara[strpos($mascara,"#")] = $string[$i];
            }
            return $mascara;
        }

        $busca_sql = mysqli_real_escape_string($link, $busca);
        $assunto_sql = mysqli_real_escape_string($link, $assunto);
        $tecnico_sql = mysqli_real_escape_string($link, $tecnico);
        $lista_chamados = mysqli_query($link, "
            SELECT c.endereco, c.numero, c.bairro, c.cidade, c.complemento, c.estado,
                   c.plano, c.login, c.senha, c.celular, c.celular2,
                   sup.nome, sup.assunto, sup.abertura, sup.chamado, sup.visita, sup.tecnico,
                   func.nome AS tecnico_nome
            FROM sis_cliente c
            LEFT JOIN sis_suporte sup ON c.login = sup.login
            LEFT JOIN sis_func func ON func.id = sup.tecnico
            WHERE c.cli_ativado = 's'
              AND (c.nome LIKE '%$busca_sql%'
                   OR c.endereco LIKE '%$busca_sql%'
                   OR c.bairro LIKE '%$busca_sql%'
                   OR c.cidade LIKE '%$busca_sql%')
              AND sup.status = 'aberto'
              AND sup.assunto LIKE '$assunto_sql'
              AND (sup.tecnico LIKE '$tecnico_sql' OR sup.tecnico IS NULL)
            ORDER BY $organizar
        ");

        $tot_chamados_abertos = $lista_chamados ? mysqli_num_rows($lista_chamados) : 0;
        ?>
        <div class="ticket-count no_print">Total de chamados: <?= $tot_chamados_abertos; ?></div>
        <div class="ticket-list">
        <?php
        if (!$lista_chamados) {
            echo '<div class="alert alert-danger">Não foi possível carregar os chamados.</div>';
        }

        while ($lista_chamados && ($row_cli = mysqli_fetch_assoc($lista_chamados))) {
            $login = (string) $row_cli['login'];
            $pass_cli = (string) $row_cli['senha'];
            $plano_cli = (string) $row_cli['plano'];
            $end_cli = trim((string) $row_cli['endereco'] . ' nº ' . (string) $row_cli['numero'] . ' ' . (string) $row_cli['complemento']);
            $local_cli = trim((string) $row_cli['bairro'] . ' - ' . (string) $row_cli['cidade'] . ' / ' . (string) $row_cli['estado'], ' -/');
            $fone_cli = trim((string) $row_cli['celular']);
            $fone2_cli = trim((string) $row_cli['celular2']);
            $assunto_ticket = (string) $row_cli['assunto'];
            $chamado = (string) $row_cli['chamado'];
            $nome = (string) $row_cli['nome'];
            $tecnico_nome = trim((string) $row_cli['tecnico_nome']);
            if ($tecnico_nome === '') {
                $tecnico_nome = empty($row_cli['tecnico']) ? 'Não definido' : 'Técnico #' . $row_cli['tecnico'];
            }
            $abertura = !empty($row_cli['abertura']) ? date('d/m/Y H:i', strtotime($row_cli['abertura'])) : '--';
            $visita = !empty($row_cli['visita']) ? date('d/m/Y H:i', strtotime($row_cli['visita'])) : 'Não agendada';
            $sla_class = 'is-neutral';
            $sla_text = 'Sem agendamento';
            if (!empty($row_cli['visita'])) {
                $visita_ts = strtotime($row_cli['visita']);
                $agora_ts = time();
                $diferenca = $visita_ts - $agora_ts;
                $horas = (int) floor(abs($diferenca) / 3600);
                $dias = (int) floor($horas / 24);
                $horas_restantes = $horas % 24;
                $tempo_sla = ($dias > 0 ? $dias . 'd ' : '') . $horas_restantes . 'h';
                if ($diferenca < -86400) {
                    $sla_class = 'is-red';
                    $sla_text = 'Atrasado há ' . $tempo_sla;
                } elseif ($diferenca < 0) {
                    $sla_class = 'is-orange';
                    $sla_text = 'Atrasado há ' . $tempo_sla;
                } elseif ($diferenca <= 86400) {
                    $sla_class = 'is-orange';
                    $sla_text = 'Visita em ' . $tempo_sla;
                } else {
                    $sla_class = 'is-green';
                    $sla_text = 'Em dia · ' . $tempo_sla;
                }
            }
            $chamado_sql = mysqli_real_escape_string($link, $chamado);
            $query_texto_chamado = mysqli_query($link, "SELECT msg, msg_data FROM sis_msg WHERE chamado = '$chamado_sql' ORDER BY msg_data");
            ?>
            <details class="ticket-item">
                <summary>
                    <div class="ticket-summary-cell">
                        <small>Cliente</small>
                        <a class="ticket-client" href="index.php?busca=<?= urlencode($nome); ?>" onclick="event.stopPropagation();"><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                    <div class="ticket-summary-cell ticket-summary-address">
                        <small>Chamado / assunto</small>
                        <strong>#<?= htmlspecialchars($chamado, ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($assunto_ticket, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="ticket-summary-cell ticket-summary-tech">
                        <small>Técnico</small>
                        <span><?= htmlspecialchars($tecnico_nome, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="ticket-summary-cell ticket-summary-date">
                        <small>Visita</small>
                        <span><?= htmlspecialchars($visita, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="ticket-summary-cell ticket-summary-sla"><small>SLA da visita</small><span class="ticket-sla <?= $sla_class; ?>"><?= htmlspecialchars($sla_text, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <i class="bi bi-chevron-down ticket-chevron" aria-hidden="true"></i>
                </summary>
                <div class="ticket-details">
                    <div class="ticket-detail-grid">
                        <div class="ticket-detail"><small>Endereço</small><span><?= htmlspecialchars($end_cli, ENT_QUOTES, 'UTF-8'); ?><br><?= htmlspecialchars($local_cli, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="ticket-detail"><small>Registro / visita</small><span><?= htmlspecialchars($abertura, ENT_QUOTES, 'UTF-8'); ?><br><?= htmlspecialchars($visita, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="ticket-detail"><small>Login / senha</small><span><?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars($pass_cli, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="ticket-detail"><small>Contato / plano</small><span><?= htmlspecialchars(trim($fone_cli . ' ' . $fone2_cli), ENT_QUOTES, 'UTF-8'); ?><br><?= htmlspecialchars($plano_cli, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <div class="ticket-reports">
                        <strong>Relatos</strong>
                        <?php if ($query_texto_chamado && mysqli_num_rows($query_texto_chamado) > 0) { ?>
                            <?php while ($row_msg = mysqli_fetch_assoc($query_texto_chamado)) {
                                $data_msg = !empty($row_msg['msg_data']) ? date('d/m/Y H:i', strtotime($row_msg['msg_data'])) : '--';
                            ?>
                                <div class="ticket-report"><strong><?= htmlspecialchars($data_msg, ENT_QUOTES, 'UTF-8'); ?></strong> — <?= nl2br(htmlspecialchars((string) $row_msg['msg'], ENT_QUOTES, 'UTF-8')); ?></div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="ticket-report">Nenhum relato registrado.</div>
                        <?php } ?>
                    </div>
                    <div class="ticket-signature"><strong>Assinatura:</strong> ______________________________________________</div>
                    <div class="ticket-actions no_print">
                        <a class="ticket-close" href="../../suporte_fechar.<?= htmlspecialchars($links_ext, ENT_QUOTES, 'UTF-8'); ?>?chamado=<?= urlencode($chamado); ?>" target="_blank"><i class="bi bi-check-circle-fill"></i> Fechar chamado #<?= htmlspecialchars($chamado, ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </details>
        <?php } ?>
        </div>
        <?php
        mysqli_close($link);
        ?>



<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
    </body>
</html>
