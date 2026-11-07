<?php
// Include only from the authenticated dashboard, not as a standalone page.
if (!isset($conn) || !function_exists('permissao')) { http_response_code(403); exit; }
require_once __DIR__.'/finance_period.php';
if (!mka_finance_summary_enabled($conn)) return;
if (permissao('perm_relFin') || permissao('perm_relFat') || permissao('perm_caixa')) { ?>
<link rel="stylesheet" href="/admin/addons/dashboard/finance_today.css?v=20261106-6">
<section class="mka-finance-today" data-finance-today aria-label="Resumo financeiro">
 <header><div><span class="mka-finance-eyebrow" data-finance-eyebrow>FINANCEIRO · SEMANA</span><h2 data-finance-title>Acompanhe a semana</h2></div><div class="mka-finance-controls"><div class="mka-finance-period-buttons" role="group" aria-label="Período financeiro"><button type="button" data-finance-period="day" aria-pressed="false">Dia</button><button type="button" data-finance-period="week" aria-pressed="true" title="Domingo a sábado">Semana</button><button type="button" data-finance-period="fortnight" aria-pressed="false" title="Hoje e os próximos 14 dias">15 dias</button><button type="button" data-finance-period="month" aria-pressed="false" title="Mês atual completo">Mês</button></div><span data-finance-updated role="status">Carregando valores…</span></div></header>
 <div class="mka-finance-grid">
  <a class="mka-finance-card is-due" data-finance-due aria-label="Consultar títulos a receber do período" hidden><div class="mka-finance-heading"><span class="mka-finance-icon" aria-hidden="true">↙</span><h3 data-finance-due-title>A receber nesta semana</h3></div><strong data-finance-amount>—</strong><p data-finance-quantity></p><small data-finance-due-note>Vencimento de domingo a sábado · títulos em aberto e vencidos, sem encargos calculados.</small></a>
  <a class="mka-finance-card is-cash" data-finance-cash href="/admin/addons/rel_caixa/" aria-label="Abrir relatório de caixa" hidden><div class="mka-finance-heading"><span class="mka-finance-icon" aria-hidden="true">▣</span><h3 data-finance-cash-title>Caixa da semana</h3></div><div data-finance-balance-wrap><strong data-finance-balance>—</strong><p data-finance-balance-note>Saldo da semana · entradas menos saídas</p></div><div class="mka-finance-movements"><div><span>↓ Entradas</span><b data-finance-incoming>—</b></div><div><span>↑ Saídas</span><b data-finance-outgoing>—</b></div></div><small>Movimentações registradas no caixa do sistema. Clique para abrir o caixa.</small></a>
 </div>
 <footer>Atualização automática a cada 30 segundos. <button type="button" data-finance-refresh>Atualizar agora</button></footer>
</section>
<script src="/admin/addons/dashboard/finance_today.js?v=20261106-6" defer></script>
<?php } ?>
