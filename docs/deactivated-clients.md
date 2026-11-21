# Relatório de clientes desativados

O instalador inclui `addons/rel_desativados` e cria o atalho **Clientes → Relatório de desativados**, com ícone. O menu preserva entradas personalizadas e não duplica o destino em reinstalações.

Integração do relatório de Elton Pereira (1.08), mantendo busca por nome/login/CPF-CNPJ, período, paginação, impressão e acompanhamento de equipamento/data de retirada. A tabela `cliente_desativados_ton` existente é reutilizada; registros anteriores são preservados. O endpoint não altera o estado do cliente.

Requer sessão administrativa, permissão `perm_relFat` e respeita os grupos do operador. Gravações de retirada exigem POST, token CSRF e cliente desativado pertencente ao escopo autorizado. Não expõe credenciais próprias de banco e utiliza o bootstrap da suíte.

Validação: PHP lint, menu idempotente, GET autenticado com busca contendo apóstrofo e filtro de datas, bloqueio de gravação anônima/sem CSRF/em cliente ativo, layout 390/1360. Nenhuma retirada real foi registrada durante os testes.
