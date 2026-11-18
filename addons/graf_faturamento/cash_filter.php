<?php if(!isset($billingCashUsers))require __DIR__.'/suite_filters.php'; ?>
<label class="gf-cash-filter">Caixa / operador<select name="caixa_usuario"><option value="">Todos os operadores</option><?php foreach($billingCashUsers as $cashUser): ?><option value="<?=htmlspecialchars($cashUser,ENT_QUOTES,'UTF-8')?>" <?=$cashUser===$billingCashUser?'selected':''?>><?=htmlspecialchars($cashUser,ENT_QUOTES,'UTF-8')?></option><?php endforeach; ?></select></label>

<p class="gf-cash-note">O operador filtra as movimentações de caixa. Previsão de títulos e contas a pagar permanecem gerais.</p>
