<?php
// Include only from the authenticated dashboard, not as a standalone page.
if (!isset($conn) || !function_exists('permissao')) { http_response_code(403); exit; }
require_once __DIR__.'/finance_period.php';
if (!mka_finance_summary_enabled($conn)) return;
if (permissao('perm_relFin') || permissao('perm_relFat') || permissao('perm_caixa')) { ?>
<link rel="stylesheet" href="/admin/addons/dashboard/finance_today.css?v=20261106-5">
<section class="mka-finance-today" data-finance-today data-finance-preference-key="mka.finance.period.<?=htmlspecialchars(substr(hash('sha256',(string)($usuario_logado??'')),0,20),ENT_QUOTES,'UTF-8')?>" aria-label="Resumo financeiro de hoje">
 <header><div><span class="mka-finance-eyebrow" data-finance-eyebrow>FINANCEIRO · HOJE</span><h2 data-finance-title>Acompanhe o dia</h2></div><div class="mka-finance-controls"><div class="mka-finance-period-buttons" role="group" aria-label="Período financeiro"><button type="button" data-finance-period="day" aria-pressed="true">Dia</button><button type="button" data-finance-period="week" aria-pressed="false" title="Domingo a sábado">Semana</button></div><span data-finance-updated role="status">Carregando valores…</span></div></header>
 <div class="mka-finance-grid">
  <article class="mka-finance-card is-due" data-finance-due hidden><div class="mka-finance-heading"><span class="mka-finance-icon" aria-hidden="true">↙</span><h3 data-finance-due-title>A receber hoje</h3></div><strong data-finance-amount>—</strong><p data-finance-quantity></p><small data-finance-due-note>Vencimento hoje · títulos em aberto e vencidos, sem encargos calculados.</small></article>
  <a class="mka-finance-card is-cash" data-finance-cash href="/admin/addons/rel_caixa/" aria-label="Abrir relatório de caixa" hidden><div class="mka-finance-heading"><span class="mka-finance-icon" aria-hidden="true">▣</span><h3 data-finance-cash-title>Caixa de hoje</h3></div><div data-finance-balance-wrap><strong data-finance-balance>—</strong><p data-finance-balance-note>Saldo do dia · entradas menos saídas</p></div><div class="mka-finance-movements"><div><span>↓ Entradas</span><b data-finance-incoming>—</b></div><div><span>↑ Saídas</span><b data-finance-outgoing>—</b></div></div><small>Movimentações registradas no caixa do sistema. Clique para abrir o caixa.</small></a>
 </div>
 <footer>Atualização automática a cada 30 segundos. <button type="button" data-finance-refresh>Atualizar agora</button></footer>
</section>
<script src="/admin/addons/dashboard/finance_today.js?v=20261106-5" defer></script>
<?php } ?>
