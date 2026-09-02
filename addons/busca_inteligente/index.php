<?php
include('nav/header.php');
require_once __DIR__ . '/../shared/layout_mode.php';
require_once __DIR__ . '/../shared/client_update_audit.php';
mka_client_audit_ensure_table(isset($link) ? $link : null);

if (mka_suite_get_layout_mode(isset($link) ? $link : null) === 'legado') {
    header('Location: ../busca_inteligente-legado/');
    exit;
}
?>

<body class="">

    <?php include('../../topo.php'); ?>
    <?php mka_suite_render_top_spacing_style($link); ?>

    <style>
        .smart-toolbar { display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:8px; margin:10px 15px 18px; padding:10px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.06); }
        .smart-toolbar a { display:inline-flex; align-items:center; gap:7px; padding:9px 12px; border-radius:11px; color:#36506c; text-decoration:none; font-size:13px; font-weight:700; transition:background .18s ease,color .18s ease,transform .18s ease; }
        .smart-toolbar a:hover { background:#edf5ff; color:#1268db; transform:translateY(-1px); }
        .smart-toolbar a.is-active { background:#1268db; color:#fff; }
        .smart-toolbar i { font-size:16px; }
        .smart-search-form { margin:0 15px 8px; padding:12px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.04); }
        .smart-search-form .form-control,
        .smart-search-form .form-select { border-color:#cbd8e8; border-radius:10px; }
        .smart-search-form .smart-search-button { border:0; border-radius:11px; background:#1268db; color:#fff; font-weight:700; box-shadow:none; }
        .smart-search-form .smart-search-button:hover { background:#0f5fc8; }
        .client-score { display:inline-flex; align-items:center; gap:5px; min-width:70px; padding:3px 9px 3px 7px; border:1px solid #d8e2ed; border-radius:999px; background:rgba(255,255,255,.82); color:#24364a; font:inherit; font-size:11px; font-weight:700; line-height:1.35; vertical-align:middle; box-shadow:0 1px 2px rgba(15,23,42,.04); cursor:pointer; }
        .client-score:hover { border-color:#9bb5d1; background:#fff; box-shadow:0 3px 9px rgba(15,23,42,.09); }
        .client-score::before { content:""; width:6px; height:6px; flex:0 0 6px; border-radius:50%; background:#94a3b8; }
        .client-score.is-critical::before { background:#e5484d; }
        .client-score.is-low::before { background:#f06a35; }
        .client-score.is-attention::before { background:#e9a400; }
        .client-score.is-regular::before { background:#d1a400; }
        .client-score.is-good::before { background:#20a66a; }
        .client-score.is-excellent::before { background:#07834f; }
        .client-score-trophy { color:#c69214; font-size:10px; }
        .score-modal { position:fixed; inset:0; z-index:1090; display:flex; align-items:center; justify-content:center; padding:18px; background:rgba(15,23,42,.36); backdrop-filter:blur(3px); }
        .score-modal[hidden] { display:none; }
        .score-modal-card { width:min(460px,100%); overflow:hidden; border:1px solid #d8e3ef; border-radius:18px; background:#fff; box-shadow:0 24px 70px rgba(15,23,42,.23); }
        .score-modal-head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; border-bottom:1px solid #e7edf4; }
        .score-modal-head h2 { margin:0; color:#20364f; font-size:16px; font-weight:750; }
        .score-modal-close { border:0; border-radius:8px; background:#f1f5f9; color:#52667c; font-size:20px; line-height:28px; width:32px; height:32px; }
        .score-modal-body { padding:20px; }
        .score-modal-value { display:flex; align-items:baseline; justify-content:center; gap:7px; margin-bottom:18px; color:#20364f; }
        .score-modal-value strong { font-size:34px; line-height:1; }
        .score-history-label { margin-bottom:9px; color:#687b90; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        .score-history-dots { display:flex; flex-wrap:wrap; gap:7px; min-height:14px; padding:14px; border:1px solid #e3eaf2; border-radius:12px; background:#f8fafc; }
        .score-history-dot { width:11px; height:11px; border-radius:50%; box-shadow:0 0 0 2px #fff; }
        .score-history-dot.is-late { background:#e5484d; }.score-history-dot.is-ontime { background:#94a3b8; }.score-history-dot.is-early { background:#20c963; }
        .score-history-empty { color:#8494a7; font-size:12px; }
        .score-history-legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:12px; color:#607286; font-size:11px; }
        .score-history-legend span { display:inline-flex; align-items:center; gap:5px; }.score-history-legend i { width:8px; height:8px; border-radius:50%; }
        .trust-unlock-modal { position:fixed; inset:0; z-index:1095; display:flex; align-items:center; justify-content:center; padding:18px; background:rgba(15,23,42,.42); backdrop-filter:blur(4px); }
        .trust-unlock-modal[hidden] { display:none; }
        .trust-unlock-card { width:min(520px,100%); overflow:hidden; border:1px solid #d8e3ef; border-radius:18px; background:#fff; box-shadow:0 24px 70px rgba(15,23,42,.26); }
        .trust-unlock-head { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #e7edf4; }
        .trust-unlock-head strong { color:#20364f; font-size:15px; }
        .trust-unlock-close { width:32px; height:32px; border:0; border-radius:8px; background:#f1f5f9; color:#52667c; font-size:20px; line-height:28px; }
        .trust-unlock-frame { display:block; width:100%; height:560px; border:0; background:#fff; }
        .smart-state-toast-stack { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:1085; width:min(820px,calc(100vw - 28px)); display:flex; flex-direction:column; gap:10px; pointer-events:none; }
        .smart-state-toast { position:relative; display:flex; align-items:center; gap:10px; padding:10px 44px 10px 12px; border:1px solid #dce7f3; border-left:5px solid #2563eb; border-radius:999px; background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,249,255,.98)); box-shadow:0 16px 38px rgba(26,41,64,.18); color:#243041; pointer-events:auto; animation:smartStateToastIn .25s ease both; }
        .smart-state-toast-icon { display:flex; align-items:center; justify-content:center; width:34px; height:34px; flex:0 0 34px; border-radius:50%; background:linear-gradient(135deg,#3b8df5,#1769e8); color:#fff; font-size:15px; }
        .smart-state-toast-content { min-width:0; flex:1; display:flex; align-items:center; justify-content:center; gap:12px; }.smart-state-toast-label { color:#718198; font-size:9px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }.smart-state-toast-title { margin:0; color:#22324d; font-size:13px; font-weight:750; white-space:nowrap; }.smart-state-toast-description { margin:0; color:#728298; font-size:10px; white-space:nowrap; }
        .smart-state-toast-meta { display:flex; flex-wrap:nowrap; gap:8px; color:#61708d; font-size:10px; white-space:nowrap; }.smart-state-toast-meta span { display:inline-flex; align-items:center; gap:4px; }
        .smart-state-connection { display:inline-flex; align-items:center; gap:6px; margin:0; padding:5px 9px; border-radius:999px; font-size:9px; font-weight:750; text-transform:uppercase; white-space:nowrap; }.smart-state-connection.is-online { background:#e8f8f0;color:#18794a; }.smart-state-connection.is-offline { background:#eef2f6;color:#475569; }.smart-state-connection.is-disconnected { background:#fff3d6;color:#946000; }
        .smart-state-toast-close { position:absolute; top:7px; right:8px; width:27px; height:27px; border:0; border-radius:8px; background:transparent; color:#8290a4; font-size:18px; }.smart-state-toast-close:hover { background:#edf2f7;color:#334155; }
        @keyframes smartStateToastIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:none; } }
        @media(max-width:760px){.smart-state-toast-stack{bottom:12px;width:calc(100vw - 20px)}.smart-state-toast{border-radius:18px;align-items:flex-start}.smart-state-toast-content{align-items:flex-start;flex-wrap:wrap;justify-content:flex-start;gap:6px 10px}.smart-state-toast-description{white-space:normal;width:100%}}
        @media (max-width:575.98px) { .smart-toolbar span { display:none; } .smart-toolbar { margin-inline:8px; } .smart-search-form { margin-inline:8px; } }
    </style>

    <nav class="smart-toolbar no_print mka-suite-content-start" aria-label="Navegação da Busca Inteligente">
        <a href="#" onclick="window.history.back(); return false;"><i class="bi bi-arrow-left-circle-fill"></i><span>Voltar</span></a>
        <a href="index.php" class="is-active"><i class="bi bi-house-door-fill"></i><span><?= htmlspecialchars($Manifest->{'name'} . ' - V ' . $Manifest->{'version'}, ENT_QUOTES, 'UTF-8'); ?></span></a>
        <a href="chamados_abertos.php"><i class="bi bi-headset"></i><span>Chamados</span></a>
        <a href="score.php"><i class="bi bi-bar-chart-fill"></i><span>Score</span></a>
        <a href="relcontratos.php"><i class="bi bi-file-earmark-text-fill"></i><span>Contratos</span></a>
        <a href="cfg.php"><i class="bi bi-gear-fill"></i><span>Configurações</span></a>
        <a href="#" onclick="window.print(); return false;"><i class="bi bi-printer-fill"></i><span>Imprimir</span></a>
    </nav>

    <div class="score-modal no_print" id="clientScoreModal" hidden role="dialog" aria-modal="true" aria-labelledby="clientScoreModalTitle">
        <div class="score-modal-card">
            <div class="score-modal-head"><h2 id="clientScoreModalTitle">Detalhes do Score</h2><button type="button" class="score-modal-close" aria-label="Fechar">&times;</button></div>
            <div class="score-modal-body">
                <div class="score-modal-value"><strong id="clientScoreModalValue">0</strong></div>
                <div class="score-history-label">Histórico dos últimos pagamentos</div>
                <div class="score-history-dots" id="clientScoreHistory"></div>
                <div class="score-history-legend"><span><i style="background:#e5484d"></i>Em atraso</span><span><i style="background:#94a3b8"></i>No vencimento</span><span><i style="background:#20c963"></i>Antecipado</span></div>
            </div>
        </div>
    </div>
    <div class="trust-unlock-modal no_print" id="trustUnlockModal" hidden role="dialog" aria-modal="true" aria-labelledby="trustUnlockModalTitle">
        <div class="trust-unlock-card">
            <div class="trust-unlock-head"><strong id="trustUnlockModalTitle">Desbloqueio de Confiança</strong><button type="button" class="trust-unlock-close" aria-label="Fechar">&times;</button></div>
            <iframe class="trust-unlock-frame" id="trustUnlockFrame" title="Desbloqueio de Confiança"></iframe>
        </div>
    </div>
    <script>
    (function () {
        var modal = document.getElementById('clientScoreModal');
        var value = document.getElementById('clientScoreModalValue');
        var history = document.getElementById('clientScoreHistory');
        function addDots(total, cssClass, title) {
            for (var i = 0; i < total; i++) { var dot = document.createElement('span'); dot.className = 'score-history-dot ' + cssClass; dot.title = title; history.appendChild(dot); }
        }
        function closeModal() { modal.hidden = true; document.body.style.overflow = ''; }
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('.client-score');
            if (trigger) {
                event.preventDefault(); history.innerHTML = ''; value.textContent = trigger.getAttribute('data-score') || '0';
                addDots(parseInt(trigger.getAttribute('data-late') || '0', 10), 'is-late', 'Título pago em atraso');
                addDots(parseInt(trigger.getAttribute('data-ontime') || '0', 10), 'is-ontime', 'Título pago no vencimento');
                addDots(parseInt(trigger.getAttribute('data-early') || '0', 10), 'is-early', 'Título pago antecipadamente');
                if (!history.children.length) history.innerHTML = '<span class="score-history-empty">Ainda não há pagamentos no histórico.</span>';
                modal.hidden = false; document.body.style.overflow = 'hidden'; return;
            }
            if (event.target === modal || event.target.closest('.score-modal-close')) closeModal();
        });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
    }());
    </script>
    <script>
    (function () {
        var modal=document.getElementById('trustUnlockModal'), frame=document.getElementById('trustUnlockFrame');
        function closeModal(){modal.hidden=true;frame.removeAttribute('src');document.body.style.overflow='';}
        window.mkaOpenTrustUnlock=function(login){frame.src='teste_obs.php?embed=1&login='+encodeURIComponent(login||'');modal.hidden=false;document.body.style.overflow='hidden';return false;};
        modal.querySelector('.trust-unlock-close').addEventListener('click',closeModal);
        modal.addEventListener('click',function(event){if(event.target===modal)closeModal();});
        window.addEventListener('message',function(event){
            if(!event.data||typeof event.data!=='object')return;
            if(event.data.type==='mka-trust-unlock-close')closeModal();
            if(event.data.type==='mka-trust-unlock-success'){closeModal();window.setTimeout(function(){window.location.reload();},350);}
        });
        document.addEventListener('keydown',function(event){if(event.key==='Escape'&&!modal.hidden)closeModal();});
    }());
    </script>

    <div class="smart-state-toast-stack no_print" id="smartStateToastStack" aria-live="polite"></div>
    <script>
    (function () {
        var stack = document.getElementById('smartStateToastStack');
        function clean(value) { var node=document.createElement('div'); node.textContent=value == null ? '' : String(value); return node.innerHTML; }
        function showStateToast(data) {
            var storageKey='smart-search-state-toast-'+data.id;
            if (!data.id || sessionStorage.getItem(storageKey)) return;
            sessionStorage.setItem(storageKey,'1');
            var state=data.connection_state || 'offline';
            var icon=state==='online'?'bi-wifi':(state==='disconnected'?'bi-wifi-off':'bi-circle-fill');
            var item=document.createElement('div'); item.className='smart-state-toast';
            item.innerHTML='<button type="button" class="smart-state-toast-close" aria-label="Fechar">&times;</button>'+
                '<span class="smart-state-toast-icon"><i class="'+clean(data.icon || 'bi bi-info-circle-fill')+'"></i></span><div class="smart-state-toast-content">'+
                '<span class="smart-state-toast-label">'+clean(data.label)+'</span><p class="smart-state-toast-title">'+clean(data.name)+'</p>'+
                (data.description?'<p class="smart-state-toast-description">'+clean(data.description)+'</p>':'')+
                '<div class="smart-state-toast-meta"><span><i class="bi bi-person-badge"></i>'+clean(data.login)+'</span><span><i class="bi bi-clock"></i>'+clean(data.formatted_time)+'</span></div>'+
                '<div class="smart-state-connection is-'+clean(state)+'"><i class="bi '+icon+'"></i>'+clean(data.connection_label)+'</div></div>';
            stack.prepend(item);
            item.querySelector('.smart-state-toast-close').onclick=function(){item.remove();};
            window.setTimeout(function(){if(item.parentNode)item.remove();},9000);
        }
        function fetchStateEvents(){
            fetch('../dashboard/session_events.php',{credentials:'same-origin',cache:'no-store'}).then(function(response){return response.json();}).then(function(payload){
                if(!payload || payload.enabled!==true || !Array.isArray(payload.events))return;
                payload.events.slice().reverse().forEach(function(eventData){if(eventData.type==='client-state')showStateToast(eventData);});
            }).catch(function(){});
        }
        function hideLegacyNotice(root){
            if(!root || root.nodeType!==1)return; var selector='.notification,.toast,.toastify,.snackbar,.alert-info,[id*="snackbar"]'; var items=[];
            if(root.matches&&root.matches(selector))items.push(root); if(root.querySelectorAll)items=items.concat(Array.prototype.slice.call(root.querySelectorAll(selector)));
            items.forEach(function(item){var message=(item.textContent||'').replace(/\s+/g,' ').trim();if(/login\s+\S+\s+(?:des)?bloqueado\s+por/i.test(message))item.style.display='none';});
        }
        hideLegacyNotice(document.body); new MutationObserver(function(records){records.forEach(function(record){Array.prototype.forEach.call(record.addedNodes||[],hideLegacyNotice);});}).observe(document.body,{childList:true,subtree:true});
        fetchStateEvents(); window.setInterval(fetchStateEvents,10000);
    }());
    </script>

   
    <?php

    $create_table_busca_inteligente_cfg = mysqli_query($link, "CREATE TABLE IF NOT EXISTS busca_inteligente_cfg (
            id int NOT NULL AUTO_INCREMENT,
            num_conexoes INT NOT NULL,
            tempo_queda INT NOT NULL,
            porta_acesso INT NOT NULL,
            porta_acesso2 INT,
            porta_acesso3 INT,
            link_whats VARCHAR(255) DEFAULT 'https://api.whatsapp.com/send?phone=55',
            links_ext VARCHAR(10) DEFAULT 'php',
            check_online VARCHAR(10) DEFAULT 'mkauth',
            PRIMARY KEY (id)
        )");


    $query = mysqli_query($link, "SHOW COLUMNS FROM busca_inteligente_cfg WHERE field = 'porta_acesso2'");

    if (mysqli_num_rows($query) == 0) {
        $query_alterar_table = mysqli_query($link, "ALTER TABLE busca_inteligente_cfg 
            ADD porta_acesso2 INT, 
            ADD porta_acesso3 INT, 
            ADD link_whats VARCHAR(255) DEFAULT 'https://api.whatsapp.com/send?phone=55',
            ADD links_ext VARCHAR(10) DEFAULT 'php',
            ADD check_online VARCHAR(10) DEFAULT 'mkauth' 
            AFTER porta_acesso");

        if (!$query_alterar_table) {
            echo mysqli_error($link);
        }
    }

    $query2 = mysqli_query($link, "SHOW COLUMNS FROM busca_inteligente_cfg WHERE field = 'link_whats'");

    if (mysqli_num_rows($query2) == 0) {
        $query_alterar_table = mysqli_query($link, "ALTER TABLE busca_inteligente_cfg 
            ADD porta_acesso3 INT, 
            ADD link_whats VARCHAR(255) DEFAULT 'https://api.whatsapp.com/send?phone=55',
            ADD links_ext VARCHAR(10) DEFAULT 'php',
            ADD check_online VARCHAR(10) DEFAULT 'mkauth' 
            AFTER porta_acesso2");

        if (!$query_alterar_table) {
            echo mysqli_error($link);
        }
    }

    $query3 = mysqli_query($link, "SHOW COLUMNS FROM busca_inteligente_cfg WHERE field = 'check_online'");

    if (mysqli_num_rows($query3) == 0) {
        $query_alterar_table = mysqli_query($link, "ALTER TABLE busca_inteligente_cfg 
            ADD check_online VARCHAR(10) DEFAULT 'mkauth' 
            AFTER links_ext");

        if (!$query_alterar_table) {
            echo mysqli_error($link);
        }
    }

    $query4 = mysqli_query($link, "SHOW COLUMNS FROM busca_inteligente_cfg WHERE field = 'porta_acesso3'");

    if (mysqli_num_rows($query4) == 0) {
        $query_alterar_table = mysqli_query($link, "ALTER TABLE busca_inteligente_cfg 
            ADD porta_acesso3 INT
            AFTER porta_acesso2");

        if (!$query_alterar_table) {
            echo mysqli_error($link);
        }
    }

    $verifica_tabela_vazia = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg");
    if (mysqli_num_rows($verifica_tabela_vazia) == 0) {
        $ins_table_busca_inteligente_cfg = mysqli_query($link, "INSERT INTO busca_inteligente_cfg VALUES ('1', '50', '60', '80', '', '', 'https://api.whatsapp.com/send?phone=55', 'php', 'mkauth')");
        if (!$ins_table_busca_inteligente_cfg) {
            echo mysqli_error($link);
        }
    }

    $query5 = mysqli_query($link, "SHOW COLUMNS FROM busca_inteligente_cfg WHERE field = 'contabilizar_bloq_offline'");

    if (mysqli_num_rows($query5) == 0) {
        $query_alterar_table = mysqli_query($link, "ALTER TABLE busca_inteligente_cfg 
            ADD contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's'
            AFTER check_online");

        if (!$query_alterar_table) {
            echo mysqli_error($link);
        }
    }

    $cond_offline = "";
    $suite_live_search = mka_suite_get_live_search($link);
    // Carrega as configuracoes do banco
    $ler_busca_inteligente_cfg = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg");
    while ($cfg = mysqli_fetch_array($ler_busca_inteligente_cfg)) {
        $porta_acesso = $cfg['porta_acesso'];
        $porta_acesso2 = $cfg['porta_acesso2'];
        $porta_acesso3 = $cfg['porta_acesso3'];
        $link_whats = $cfg['link_whats'];
        $links_ext = $cfg['links_ext'];
        $check_online = $cfg['check_online'];
        $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'];
        if ($contabilizar_bloq_offline == "n") {
            $cond_offline = " AND c.bloqueado = 'nao' ";
        }
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
    if (mysqli_num_rows($verifica_tabela_vazia) == 0) {
        $ins_table_score_cliente_cfg = mysqli_query($link, "INSERT INTO score_cliente_cfg 
            VALUES ('1', 
                    '100', '11', 
                    '10', '-5','-10','-15','-20','-25','-30','-50',
                    '-5','5','10')");

        if (!$ins_table_score_cliente_cfg) {
            echo mysqli_error($link);
        }
    }

    $query_atual_cfg = mysqli_query($link, "SELECT * FROM score_cliente_cfg ORDER BY id DESC LIMIT 1");
    while ($cfg = mysqli_fetch_array($query_atual_cfg)) {

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

    $max_score = $score_base + ($ano_fidelizacao * 5) + $tit_venc_0 + ($tit_antes_venc * 12);




    $busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
    $organizar = isset($_GET['organizar']) == '' ? 'c.nome' : $_GET['organizar'];

    $filtro = isset($_GET['filtro']) == '' ? '' : $_GET['filtro'];

    $lista_organizar = array(
        "c.data_ins DESC" => "Inclusão Recente",
        "c.last_update DESC" => "Últimos Alterados",
        "c.tit_vencidos DESC" => "Títulos Vencidos",
        "c.data_bloq" => "Data Bloqueado",
        "c.nome" => "nome A-Z",
        "c.nome DESC" => "nome Z-A",
        "c.endereco"  => "endereco A-Z",
        "c.endereco DESC"  => "endereco Z-A",
        "c.bairro" => "bairro",
        "c.cidade"  => "cidade",
        "c.plano" => "plano",
        "c.venc" => "vencimento",
    );

    $lista_filtro = array(
        "" => "Nenhum",
        "pessoa = 'fisica' AND" => "Pessoa Física",
        "pessoa = 'juridica' AND" => "Pessoa Jurídica",
    );

    $busca = trim($busca);
    $palavras_buscas = str_replace(" ", "%", $busca);

    //$username_on = "";

    $partes = explode("+", $busca);

    //sizeof($partes);
    if (isset($partes[0]) && isset($partes[1]) && isset($partes[2])) {
        $string_nome    = str_replace(" ", "%", $partes[0]);
        $string_end     = str_replace(" ", "%", $partes[1]);
        $string_add     = str_replace(" ", "%", $partes[2]);
    } else if (isset($partes[0]) && isset($partes[1])) {
        $string_nome    = str_replace(" ", "%", $partes[0]);
        $string_end     = str_replace(" ", "%", $partes[1]);
    } else if (isset($partes[0])) {
        $string_nome    = str_replace(" ", "%", $partes[0]);
    }


    // Relação de grupos do usuário logado

    $sql_usuario_grupos = mysqli_query($link, "SELECT cli_grupos FROM sis_acesso WHERE login LIKE '$usuario_logado' AND cli_grupos NOT LIKE 'full_clientes%'");

    if (!$sql_usuario_grupos) {
        echo mysqli_error($link);
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
            if($value == "ped_fil"){
                continue;
            }
            if($value == "full_clientes"){
                $grupos = "(c.grupo LIKE '%' OR c.grupo = ";
                break;
            }
            $grupos .= "'$value' OR c.grupo = ";

        }

        if($grupos != "(c.grupo = "){
            $grupos = substr($grupos, 0, -14);
            $grupos .= ") AND ";
        }else{
            $grupos ="";
        }

// echo "<br>GRUPOS ". $grupos;
        
    }

    //Paginaçço
    $registros_por_pagina = isset($_GET['num_registros']) == '' ? '25' : $_GET['num_registros'];
    $pagina = isset($_GET['pagina']) == '' ? $pc = "1" : $pc = $_GET['pagina'];

    $inicio = $pc - 1;
    $inicio = $inicio * $registros_por_pagina;
    ?>

    <datalist id="sugestoes">
        <option value="on">
        <option value="off">
        <option value="adicionais">
        <option value="bloqueado">
        <option value="atrasado">
        <option value="observacao">
        <option value="desativado">
        <option value="sem conexoes">
        <option value="sem carne">
        <option value="sem titulo">
        <option value="sem telefone">
        <option value="venc+">
        <option value="conta+">
        <option value="parcelas abertas+">
            <?php
            $query_sugestoes = mysqli_query($link, "SELECT DISTINCT nome FROM sis_cliente WHERE cli_ativado LIKE 's' ORDER BY nome");
            while ($s = mysqli_fetch_array($query_sugestoes)) {
                $s_nome = $s['nome'];
                echo "<option value='$s_nome'>";
            }

            ?>
    </datalist>

    <form action="" method="get" class="no_print smart-search-form">
        <div class="row g-1">
            <div class="col-8 col-sm-6">
                <div class="form-floating">
                    <input type="search" class="form-control" id="busca" name="busca" placeholder="Busque por nome, login, Endereço, plano, CPF... ou nome + Endereço ou bloqueado ou offline ou desativado ou observacao" value="<?php echo $busca; ?>" list="sugestoes" />
                    <label for="busca"> Digite o que procura:</label>
                </div>
            </div>
            <div class="col-4 col-sm-2">
                <div class="form-floating">
                    <select name="organizar" id="organizar" class="form-select">
                        <?php
                        foreach ($lista_organizar as $key => $value) {
                            $selected = ($organizar == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                        ?>
                    </select>
                    <label for="organizar"> Organizar por:</label>
                </div>
            </div>
            <div class="col-4 col-sm-2">
                <div class="form-floating">
                    <select name="filtro" id="filtro" class="form-select">
                        <?php
                        foreach ($lista_filtro as $key => $value) {
                            $selected = ($filtro == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                        ?>
                    </select>
                    <label for="filtro"> Filtro:</label>
                </div>
            </div>
            <div class="col-4 col-sm-1">
                <div class="form-floating">
                    <select name="num_registros" id="num_registros" class="form-select">
                        <?php
                        for ($i = 25; $i <= 500; $i += 25) {
                            if ($i > 100) {
                                $i += 25;
                            }
                            $selected = ($i == $registros_por_pagina) ? "selected=\"selected\"" : null;
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                    <label for="num_registros"> Items:</label>
                </div>
            </div>
            <div class="col-4 col-sm-1">
                <div class="form-floating d-grid h-100">
                    <input type="hidden" name="pagina" value="1" />
                    <button type="submit" name="submit" value="Buscar" class="btn btn-lg text-center smart-search-button"><i class="bi bi-search"></i> Buscar</button>
                </div>
            </div>


        </div>

    </form>

    <?php

    $query_base = "SELECT 
            c.nome, 
            c.cpf_cnpj, 
            c.login, 
            c.senha, 
            c.endereco, 
            c.numero, 
            c.bairro, 
            c.complemento,
            c.cidade,
            c.estado, 
            c.plano,
            c.fone,
            c.nome_res,  
            c.celular, 
            c.celular2, 
            c.tit_vencidos, 
            c.bloqueado, 
            c.data_bloq, 
            c.observacao, 
            c.rem_obs, 
            c.cli_ativado, 
            c.data_desativacao, 
            c.comodato, 
            c.equipamento,
            c.data_ins,
            c.last_update,
            cua.usuario AS last_update_user,
            cua.detalhes AS last_update_details,
            c.cep,
            c.uuid_cliente,
            c.switch,
            c.coordenadas,
            c.email,
            c.grupo,
            c.parc_abertas,
            c.venc,
            c.tit_abertos,
            c.codigo,
            c.caixa_herm,
            c.porta_splitter,
            c.cadastro
            ";

    $audit_join = "
        LEFT JOIN dashboard_am_client_update_audit cua
        ON c.uuid_cliente = cua.uuid_cliente
        AND c.last_update <> cua.previous_last_update
        AND c.last_update BETWEEN DATE_SUB(cua.captured_at, INTERVAL 5 SECOND) AND DATE_ADD(cua.captured_at, INTERVAL 5 MINUTE)
    ";

    $query_default = "
            $query_base     
            FROM sis_cliente c

        LEFT JOIN sis_adicional c2
        ON c.login = c2.login
        $audit_join
        WHERE $filtro $grupos";

    // echo $query_default ."<br>";
    
    $group = "GROUP BY c.login";

    echo '<div id="mka-live-results">';

    if (startsWith($partes[0], 'adiciona')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND c2.login = c.login
            $group ORDER BY $organizar ";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        /*if(!$result || !$result_limit){
                echo mysqli_error()."<br>";
            }*/
        include('exibir_resultados.php');
    } else if (strtolower($partes[0]) == 'on' && strtolower($partes[1]) != '') {
        // echo "estou aqui! no onn";
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.ramal LIKE '$partes[1]' AND 
            EXISTS (SELECT 1 FROM radacct WHERE username = c.login AND acctstoptime IS NULL)
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[0]) == 'off' && strtolower($partes[1]) != '') {
        //include('clientes_offline.php');
        // echo "estou aqui! no offf";
        $query_ok = "$query_default
            NOT EXISTS
            (SELECT LOWER(TRIM(username)) FROM radacct WHERE username = c.login AND acctstoptime IS NULL LIMIT 1)
            AND c.cli_ativado LIKE 's' AND c.ramal LIKE '$partes[1]' 
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[0]) == 'ramal' && strtolower($partes[1]) != '') {
        //include('clientes_offline.php');
        // echo "estou aqui! no offf";
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND c.ramal LIKE '$partes[1]' 
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'conta') && $partes[1] != '') {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.conta = '$partes[1]'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'corte') && $partes[1] != '') {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.dias_corte = '$partes[1]'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'sem con')) {
        $query_ok = "$query_default
            NOT EXISTS
            (SELECT LOWER(TRIM(username)) FROM radacct WHERE username = c.login)
            AND c.cli_ativado LIKE 's'  
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'sem tit')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.tit_abertos LIKE '0' AND 
            c.isento LIKE 'nao' AND 
            c.tipo_cob LIKE 'titulo'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'sem carn')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.parc_abertas LIKE '0' AND 
            c.isento LIKE 'nao' AND 
            c.tipo_cob LIKE 'carne'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'sem telefone')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            (c.fone IS NULL AND c.celular IS NULL AND c.celular2 IS NULL)
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");

        if (!$result) {
            echo mysqli_error($link);
        }
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'bloq')) {

        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.bloqueado LIKE 'sim'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if ($partes[0] != '' && startsWith($partes[1], 'bloq')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND c.bloqueado LIKE 'sim' AND 
            (c.nome LIKE '%$string_nome%' OR
            c.endereco LIKE '%$string_nome%' OR
            c.cidade LIKE '%$string_nome%' OR
            c.bairro LIKE '%$string_nome%'OR
			c.plano LIKE '%$string_nome%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[2], 'bloq')) {
        $query_ok = "$query_default
            c.nome LIKE '%$string_nome%' AND 
            c.cli_ativado LIKE 's' AND 
            c.bloqueado LIKE 'sim' AND
            (c.endereco LIKE '%$string_end%' OR
            c.cidade LIKE '%$string_end%' OR
            c.bairro LIKE '%$string_end%'OR
			c.plano LIKE '%$string_end%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], "obs")) {

        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.observacao LIKE 'sim'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'atrasado sem obs')) {
        $query_ok = "    $query_base
        , COUNT(l.id)
        FROM sis_cliente c
            LEFT JOIN sis_adicional c2
            ON c.login = c2.login
            LEFT JOIN sis_lanc l
            ON l.login = c.login
            $audit_join
        WHERE 
        c.cli_ativado LIKE 's' AND
        c.observacao LIKE 'nao' AND 
        l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now'
        $cond_offline 
        $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'atrasado')) {
        $query_ok = "$query_base
        , COUNT(l.id)
        FROM sis_cliente c
            LEFT JOIN sis_adicional c2
            ON c.login = c2.login
            LEFT JOIN sis_lanc l
            ON l.login = c.login
            $audit_join
        WHERE 
        c.cli_ativado LIKE 's' AND
        l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now'
        $cond_offline 
        $group ORDER BY $organizar";
            
        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        // if(!$result || !$result_limit){
        //     echo mysqli_error($link);
        // }
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'parcelas abertas') && $partes[1] != '') {

        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.parc_abertas = '$partes[1]'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[0] == "desativado")) {

        $query_ok = "$query_default
            c.cli_ativado LIKE 'n'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if ($partes[0] != '' && strtolower($partes[1] == 'desativado')) {
        $query_ok = "$query_default
            c.cli_ativado LIKE 'n' AND
            (c.nome LIKE '%$string_nome%' OR
            c.endereco LIKE '%$string_nome%' OR
            c.cidade LIKE '%$string_nome%' OR
            c.bairro LIKE '%$string_nome%'OR
			c.plano LIKE '%$string_nome%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[2] == 'desativado')) {
        $query_ok = "$query_default
            c.nome LIKE '%$string_nome%' AND 
            c.cli_ativado LIKE 'n' AND
            (c.endereco LIKE '%$string_end%' OR
            c.cidade LIKE '%$string_end%' OR
            c.bairro LIKE '%$string_end%'OR
			c.plano LIKE '%$string_end%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (startsWith($partes[0], 'venc') && $partes[1] != '') {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            c.venc LIKE '%$string_end%'
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if ($partes[1] != '') {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            (c.nome LIKE '%$string_nome%' OR 
            c.endereco LIKE '%$string_nome%') AND 
            (c.endereco LIKE '%$string_end%' OR
            c.cidade LIKE '%$string_end%' OR
            c.bairro LIKE '%$string_end%' OR
			c.plano LIKE '%$string_end%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[0]) == 'on' || strtolower($partes[0]) == 'online') {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND 
            EXISTS (SELECT 1 FROM radacct WHERE username = c.login AND acctstoptime IS NULL)
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else if (strtolower($partes[0]) == 'off' || strtolower($partes[0]) == 'offline') {
        //include('clientes_offline.php');
        $query_ok = "$query_default
            NOT EXISTS
            (SELECT LOWER(TRIM(username)) FROM radacct WHERE username = c.login AND acctstoptime IS NULL LIMIT 1)
            AND c.cli_ativado LIKE 's' $cond_offline  
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");
        include('exibir_resultados.php');
    } else {
        $query_ok = "$query_default
            c.cli_ativado LIKE 's' AND (
            c.nome LIKE '%$palavras_buscas%' OR
            c.login LIKE '%$palavras_buscas%' OR
            c.endereco LIKE '%$palavras_buscas%' OR
            c.complemento LIKE '%$palavras_buscas%' OR
            c.email LIKE '%$busca%' OR
            c.grupo LIKE '%$busca%' OR
            c.codigo LIKE '%$busca%' OR
            c.cidade LIKE '%$busca%' OR
            c.bairro LIKE '%$busca%' OR
            c.plano LIKE '%$busca%' OR
            c.plano15 LIKE '%$busca%' OR
            c.planodown LIKE '%$busca%' OR
            c.rg LIKE '%$busca%' OR
            c.nome_res LIKE '%$busca%' OR
            c.cpf_cnpj LIKE '%$busca%' OR
            c.fone LIKE '%$busca%' OR
            c.celular LIKE '%$busca%' OR
            c.celular2 LIKE '%$busca%' OR
            c.cep LIKE '%$busca%' OR
            c.ip LIKE '%$busca%' OR
            c.mac LIKE '%$busca%' OR
            c.contrato LIKE '%$busca%' OR
            c.porta_olt LIKE '%$busca%' OR
            c.caixa_herm LIKE '%$busca%' OR
            c.porta_splitter LIKE '%$busca%' OR
            c.onu_ont LIKE '%$busca%' OR
            c.switch LIKE '%$busca%' OR
            c.equipamento LIKE '%$busca%' OR
            c.mac_serial LIKE '%$busca%' OR
            c.armario_olt LIKE '%$busca%' OR
            c2.username LIKE '%$busca%')
            $group ORDER BY $organizar";

        $result = mysqli_query($link, "$query_ok");
        $result_limit = mysqli_query($link, "$query_ok LIMIT $inicio,$registros_por_pagina");

        // if(!$result || !$result_limit){
        //     echo mysqli_error($link);
        // }
        include('exibir_resultados.php');
    }

    echo '</div>';

    mysqli_close($link);

    ?>



    <?php include('../../baixo.php'); ?>

    <?php if ($suite_live_search === 's') { ?>
        <script>
            (function () {
                var input = document.getElementById('busca');
                var form = input ? input.closest('form') : null;
                var results = document.getElementById('mka-live-results');
                if (!input || !form || !results || typeof window.fetch !== 'function') return;

                var timer = null;
                var controller = null;

                function refreshResults() {
                    var params = new URLSearchParams(new FormData(form));
                    params.set('pagina', '1');

                    if (controller) controller.abort();
                    controller = typeof AbortController === 'function' ? new AbortController() : null;

                    fetch(window.location.pathname + '?' + params.toString(), {
                        credentials: 'same-origin',
                        signal: controller ? controller.signal : undefined,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Falha ao atualizar a busca');
                            return response.text();
                        })
                        .then(function (html) {
                            var page = new DOMParser().parseFromString(html, 'text/html');
                            var updated = page.getElementById('mka-live-results');
                            if (updated) results.innerHTML = updated.innerHTML;
                        })
                        .catch(function (error) {
                            if (error && error.name !== 'AbortError') console.error(error);
                        });
                }

                input.addEventListener('input', function () {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(refreshResults, 320);
                });
            }());
        </script>
    <?php } ?>

    <script src="../../menu.js.<?= $links_ext; ?>"></script>

<?php include('../../rodape.php'); ?>
</body>

</html>
