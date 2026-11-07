# Faturas: compatibilidade entre versões do MK-AUTH

A Busca detecta as telas nativas por ação, considerando separadamente os arquivos php e hhvm existentes. Receber e Alterar atuais usam UUID; nas versões antigas, a tela titulo usa id numérico e tipo=baixar ou tipo=alttitulo. Não se deduz a rota financeira pela extensão de cliente_det.

Quando uma instalação antiga é atualizada e as telas atuais passam a existir, elas têm preferência automaticamente. Permissões, formulários de recebimento, tokens e processamento financeiro continuam sob o MK-AUTH nativo.

O cálculo utiliza as funções nativas conhecidas disponíveis (`is_callable`) para base, multa, mora, dias e conversão monetária. A família anterior à mudança para PHP 8 foi adicionada mantendo as assinaturas nativas. Não há fórmula financeira genérica substituindo o sistema.

A lista inclui os lançamentos financeiros ativos de sis_mlanc: soma os tipos diferentes de desconto, inclusive outros, e subtrai descontos, seguindo as consultas do cálculo nativo. Os registros financeiros não são regravados por essa consulta.

Verificações realizadas em PHP 7.3 e PHP 8.0: rotas antigas, atuais e com extensões mistas; preferência automática após atualização; cálculo nativo com dias, taxas, acréscimos, outros e descontos; taxas zero; vencimento futuro; exclusão lógica; isolamento por cliente/grupo. Os testes financeiros utilizam tabelas temporárias.

```sh
php scripts/test-client-invoice-routes.php addons/shared/client_invoices.php
php scripts/test-client-invoice-interest.php addons/shared/client_invoices.php
php scripts/test-client-invoices.php addons/shared/client_invoices.php
```

Reversão: restaurar os dois arquivos alterados (`addons/shared/client_invoices.php` e `addons/busca_inteligente/client_invoices_api.php`) do backup privado feito antes da aplicação e invalidar o cache PHP desses arquivos. Não é necessária reversão de banco.
