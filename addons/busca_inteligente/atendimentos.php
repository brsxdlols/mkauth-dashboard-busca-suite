<?php include('nav/header.php');require_once __DIR__.'/../shared/installations.php';
if(!mka_installation_permission($link,(string)$usuario_logado,'perm_chamados')){http_response_code(403);exit('Sem permissão para atendimentos.');}
$attendanceToday=(new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
?>
<body>
<?php include('../../topo.php');require_once __DIR__.'/../shared/layout_mode.php';mka_suite_render_top_spacing_style($link); ?>
<link rel="stylesheet" href="../shared/ticket_board.css?v=20261015-4">
<link rel="stylesheet" href="../shared/support_attendance.css?v=20261015-8">
<link rel="stylesheet" href="../shared/attendance_history.css?v=20261015-8">
<main class="attendance-page" data-today="<?= htmlspecialchars($attendanceToday,ENT_QUOTES,'UTF-8'); ?>">
 <nav class="attendance-tabs" aria-label="Central de chamados"><a href="index.php">Início</a><a href="chamados_abertos.php">Chamados e calendário</a><a href="instalacoes.php">Instalações</a><a href="atendimentos.php" aria-current="page">Atendimentos</a><a href="cfg.php">Configurações</a></nav>
 <header class="attendance-page-heading"><div><h1>Atendimentos</h1><p>Histórico pela data de abertura do atendimento. Consulte os registros do dia ou filtre outros períodos.</p></div><button type="button" data-open-attendance="">Registrar atendimento</button></header>
 <form id="attendance-report-filters" class="attendance-report-filters">
  <label>Data inicial<input type="date" name="from" value="<?= $attendanceToday; ?>" required></label>
  <label>Data final<input type="date" name="to" value="<?= $attendanceToday; ?>" required></label>
  <label>Usuário / atendente<select name="operator"><option value="">Todos os usuários</option></select></label>
  <label>Cliente ou login<input type="search" name="q" maxlength="255" placeholder="Pesquise o nome ou login do cliente"></label>
  <label>Resultado<select name="outcome"><option value="">Todos os resultados</option><option value="open">Em atendimento</option><option value="resolved">Resolvido</option><option value="finished">Finalizado sem OS</option><option value="service">Encaminhado para OS</option></select></label>
  <label class="attendance-all-period"><input type="checkbox" name="all" value="1"> Todo o período</label>
  <div class="attendance-filter-actions"><button type="submit">Filtrar</button><button type="button" data-attendance-today>Hoje</button><button type="button" data-attendance-mine>Meus atendimentos</button><button type="button" data-attendance-clear-client hidden>Remover filtro de cliente</button></div>
 </form>
 <p class="attendance-report-description" id="attendance-report-period"></p>
 <p id="attendance-report-status" role="status">Carregando atendimentos de hoje…</p>
 <div id="attendance-report-list" class="attendance-report-list"></div>
 <nav class="attendance-pagination" aria-label="Páginas de atendimentos"><button type="button" data-attendance-prev disabled>Anterior</button><span id="attendance-report-page"></span><button type="button" data-attendance-next disabled>Próxima</button></nav>
</main>
<script src="../shared/support_attendance.js?v=20261015-8"></script>
<script src="../shared/attendance_history.js?v=20261015-8"></script>
<script src="../shared/ticket_native_modal.js?v=20261015-7"></script>
<?php include('../../baixo.php'); ?>
<script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
</body></html>
