# Notas financeiras por fatura

Na Busca, abra as faturas e escolha **Nota financeira**. Registre o motivo e a previsão de pagamento. O histórico conserva usuário, data e os registros anteriores. **Concluir aviso** encerra a sinalização pendente, sem apagar o histórico.

A Busca exibe **Aviso financeiro** quando uma fatura em aberto tem uma nota pendente. Faturas pagas ou excluídas não entram nesse contador.

Use **Filtro → Com aviso financeiro** para consultar somente esses cadastros. O filtro acompanha pesquisa, organização e paginação, com os grupos do operador. O modal mostra o aviso atual e os campos **Mensagem** e **Data combinada**; o histórico fica recolhido em **Ver histórico**. A confirmação de um prazo recente só aparece ao solicitar outro prazo.

## Prazo de confiança

Salvar apenas uma nota não altera bloqueios. A opção **Não bloquear por atraso até a previsão de pagamento** aplica os mesmos campos usados pelo desbloqueio de confiança da suíte: `sis_cliente.observacao=sim` e `rem_obs` com a data final. O prazo vale para o cadastro inteiro, não somente para o boleto.

- Exige acesso financeiro e `perm_alttitulos` para registrar notas; conceder prazo exige também `perm_observacao`.
- Respeita a configuração de confiança por data, dias ou período fixo.
- Exige confirmação quando houve concessão recente e registra o prazo no histórico existente de confiança.
- Preserva um prazo vigente maior. Uma observação sem data final exige revisão explícita, evitando convertê-la automaticamente em prazo finito.
- Ao concluir um aviso, a opção de remover o prazo fica marcada por padrão para operadores com permissão de observação. Desmarque para mantê-lo. Se o aviso já estiver concluído, use **Remover prazo**, sem preencher mensagem ou data. A remoção limpa a observação e sua data final, registra o operador no histórico e permite que o bloqueio automático por atraso volte a ocorrer. A operação recusa um prazo alterado desde a consulta. Salvar nova nota mantém o prazo, exceto quando se solicita explicitamente conceder outro. O MK-AUTH continua responsável pelo processamento dos bloqueios.

## Instalação e validação

`scripts/install-invoice-notes.php` cria a tabela própria `suite_invoice_financial_notes`, sem alterar valores ou vencimentos de títulos. Gravações usam sessão administrativa, CSRF, grupos do operador, validação de titularidade, transação e chave de repetição da operação.

Testes: `scripts/test-invoice-financial-notes.php` usa somente tabelas temporárias; `scripts/test-client-invoices.js` verifica o modal e o envio explícito da opção de confiança.
