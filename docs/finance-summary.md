# Resumo financeiro na dashboard

Disponível nos layouts novo e Multiempresas, abaixo dos indicadores. A interface
consulta o endpoint autenticado a cada 30 segundos enquanto a página está visível.
Não altera títulos, recebimentos, lançamentos ou o fuso do sistema.

- Dia atual: meia-noite até a próxima meia-noite.
- Semana atual: domingo até sábado, usando o próximo domingo como limite exclusivo.
- O calendário é ancorado em CURRENT_DATE do banco. O intervalo aparece na interface.
- Dois botões, Dia e Semana, fazem a troca diretamente. A última escolha fica em
  localStorage, separada por usuário e origem do navegador. Não armazena valores
  financeiros; armazenamento bloqueado usa Dia como padrão sem impedir a troca.
- A receber: quantidade e soma de `sis_lanc.valor` dos títulos `aberto` ou `vencido`, não excluídos,
  com vencimento no intervalo. Não calcula encargos de recebimento. Cadastros
  desativados continuam com seus títulos financeiros considerados.
- Caixa: entradas, saídas e diferença do intervalo em `sis_caixa`, sem saldo anterior.
  O card abre `/admin/addons/rel_caixa/`.

O endpoint verifica sessão, cadastro do operador e permissões a cada consulta.
A receber exige `perm_relFin` ou `perm_relFat`, com restrição aos grupos do operador.
Caixa exige `perm_caixa` e acesso geral a clientes, pois seus movimentos não têm
vínculo com grupos de clientes. O saldo exige também `perm_saldoCaixa`.

Configurações > Financeiro e gráficos > Exibir resumo financeiro (dia / semana):
salva `sis_opcao.nome=suite_finance_summary_enabled`, valor `sim` ou `nao`.
Ausência da opção mantém a exibição habilitada. O salvamento exige `perm_config`,
sessão e token CSRF. Desabilitado, o bloco não é renderizado e a API recusa a consulta.

Falhas de atualização são indicadas explicitamente; valores anteriores não viram
zero. Consultas usam limites sobre as colunas indexadas `datavenc` e `data`.

Validação: `node scripts/test-finance-today.cjs` e
`php scripts/test-finance-period.php addons/dashboard`.
Na Consultoria foram comparados os totais diário/semanal com consultas diretas,
validados HTTP autenticado, rejeição anônima/CSRF e ocultação/restauração pela
configuração. Nenhum lançamento financeiro foi alterado nos testes.

Reversão: restaurar index.php, multi.php e cfg.php do backup anterior à implantação
e remover os novos finance_today.* e finance_period.php. A opção em sis_opcao pode
permanecer sem efeito se o recurso for removido.
