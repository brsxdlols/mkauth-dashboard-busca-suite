# Bloqueio e desbloqueio em massa na Busca

No rodape, os botoes acompanham o estado dos clientes selecionados, em qualquer
busca: bloqueados permitem **Desbloquear selecionados**, livres permitem
**Bloquear selecionados**. Uma selecao mista mostra os dois botoes; cada acao
processa somente os clientes no estado correspondente. Sem selecao, os botoes
refletem os resultados da pagina e ficam desabilitados. O controle exige
a permissao nativa `perm_bloqueio`; nao aparece na guia Desativados.

**Selecionar todos desta pagina** marca somente os clientes carregados na pagina
atual. O modal mostra a selecao, exige motivo e confirmacao e informa:

- Desbloquear nao quita titulos e nao concede prazo de confianca. Pendencias
  financeiras podem provocar outro bloqueio pelas regras nativas.
- Bloquear nao cria uma regra permanente. Se o financeiro permitir, a rotina
  nativa podera liberar novamente o cliente.

Cada cliente usa a mesma sequencia da acao individual: verifica sessao/grupo e
estado no endpoint autenticado de auditoria, registra o motivo com CSRF, chama
`executar_bloqueio.php` ou `.hhvm` e confirma o estado no banco pelo endpoint
existente. Nao ha UPDATE direto de bloqueio no addon nem novas regras de corte.
Quem ja esta no estado solicitado e ignorado. O processamento e sequencial.

Falhas interrompem os proximos clientes sem repetir a alteracao. O operador ve
quantos foram confirmados e deve atualizar a lista antes de decidir repetir.
Concluindo normalmente, a mesma URL e recarregada, preservando guia e filtros.

Validacao: `node scripts/test-client-bulk-block.cjs`, testes do modal individual
e PHP lint dos arquivos alterados. Nunca bloquear/desbloquear clientes reais
para testar a interface.

Reversao: restaurar `exibir_resultados.php` e `nav/header.php` do backup e remover
os dois arquivos `client_bulk_block.js` e `.css` novos. O fluxo nativo e as regras
de financeiro permanecem intactos.
