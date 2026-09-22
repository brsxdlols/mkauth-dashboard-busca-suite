<?php
if (empty($isMultiBusiness) || !isset($row)) return;
$multiStatus = $cli_ativado !== 's' ? 'Desativado' : ($bloqueado === 'sim' ? 'Bloqueado' : ($observacao === 'sim' ? 'Em observação' : 'Livre'));
$multiDetail = '../../cliente_det.'.rawurlencode($links_ext).'?uuid='.rawurlencode($uuid_cliente);
?>
<?php if (empty($multiClientHeadRendered)) { $multiClientHeadRendered = true; ?>
<div class="multi-client-head"><span>Nome Completo</span><span>Endereço</span><span>Contato</span><span>Cadastro e financeiro</span></div>
<?php } ?>
<article class="multi-client-row <?=mka_contract_escape($bgColor)?>">
<section><label class="no_print"><input type="checkbox" class="login_select" name="login[]" value="<?=mka_contract_escape($login_cliente)?>"> Selecionar cliente</label><h3><a href="<?=mka_contract_escape($multiDetail)?>"><?=mka_contract_escape($nome_cliente)?></a></h3>
<p><strong>Situação:</strong> <?=mka_contract_escape($multiStatus)?></p>
<p><strong>CPF/CNPJ:</strong> <?=mka_contract_escape($cpf_cnpj_fmt)?></p>
<p><strong>Cadastro:</strong> <?=mka_contract_escape($data_cad_fmt)?></p>
<p><strong>Última alteração:</strong> <?=mka_contract_escape($last_update_fmt)?></p>
<?php if($last_update_user!==''){?><p><strong>Por:</strong> <?=mka_contract_escape($last_update_user)?></p><?php } ?>
<?=$showScore?></section>
<section><h4>Endereço e contrato</h4><p><?=mka_contract_escape(trim($end_cliente.' '.$numero_casa.' '.$bairro_cliente))?></p><p><?=mka_contract_escape($cidade_cliente.' / '.$uf_cliente)?></p><p><strong>CEP:</strong> <?=mka_contract_escape($cep_cliente)?></p>
<div class="client-address-actions"><?=$contract_inline?>
<?php if(!mka_contract_has_document($link,$uuid_cliente)){?><a class="map-link-btn" href="relcontratos.php?client=<?=rawurlencode($uuid_cliente)?>&amp;attach=1">Anexar contrato</a><?php } ?>
<a class="map-link-btn" href="relcontratos.php?client=<?=rawurlencode($uuid_cliente)?>">Ver contratos</a></div></section>
<section><h4>Contato</h4><p><?=mka_contract_escape($email_fmt)?></p>
<?php foreach(array_unique(array($fones_cliente,$fones_cliente2,$fones_cliente3)) as $phone){ if(trim($phone)==='')continue; ?><p><a href="tel:<?=mka_contract_escape(preg_replace('/[^0-9+]/','',$phone))?>"><?=mka_contract_escape($phone)?></a></p><?php } ?></section>
<section><h4>Cadastro e financeiro</h4><p><strong>Identificação:</strong> <?=mka_contract_escape($login_cliente)?></p><p><strong>Plano / serviço:</strong> <?=mka_contract_escape($plano_cliente)?></p><p><strong>Vencimento:</strong> <?=mka_contract_escape($venc_cliente_fmt)?></p><p><strong>Conta de cobrança:</strong> <?=mka_contract_escape($conta_cliente)?></p><p><strong>Títulos vencidos:</strong> <?=(int)$quantidade_titulos_vencidos?></p><a class="map-link-btn" href="<?=mka_contract_escape($multiDetail)?>">Abrir cadastro e financeiro</a></section>
</article>
