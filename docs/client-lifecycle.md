# Ações individuais de desativação e exclusão

Os ícones aparecem na barra de ações pequenas do cliente, conforme as permissões `perm_ativacao` e `perm_delcliente` do operador. Desativar usa botão preto com símbolo de desligar; excluir usa lixeira vermelha. Clientes já desativados não recebem outro botão de desativação.

Ambas as opções abrem em modal na própria Busca. O atalho envia somente `login[]` do cliente escolhido para a tela nativa de preparação (`cliente_onoff.php/hhvm` ou `cliente_del.php/hhvm`). Não chama diretamente os executores. O modal identifica o cliente e explica a operação; o formulário nativo pede motivo e senha. O motivo vazio é impedido na interface, inclusive no botão nativo. Autenticação, autorização, token, registro do motivo e execução continuam sob o MK-AUTH.

A desativação informa que os títulos, valores e pagamentos existentes permanecem. A exclusão apresenta aviso separado, pois não equivale a desativar nem promete preservar os vínculos removidos pelo fluxo nativo. Nenhum cadastro é alterado ao carregar a Busca ou ao abrir esses formulários.

Validação: `node scripts/test-client-lifecycle.cjs`; sintaxe PHP dos arquivos alterados. O teste simula cancelar, seleção de um único login, motivo vazio e motivo preenchido, sem executar operação em cadastro real.

Para reverter a interface, restaure `exibir_resultados.php` e `nav/header.php` do backup anterior e remova `addons/shared/client_lifecycle.js`. A rotina de reconciliação é independente desses botões. O instalador existente já copia os arquivos compartilhados e da Busca, sem SQL adicional para esses atalhos.

Após ativar, desativar ou excluir um cliente individual, a Busca permanece na mesma URL (guia, filtros e paginação). A ativação executa o fluxo nativo em um iframe para conter o redirecionamento nativo. Uma consulta autenticada, somente de leitura e limitada ao grupo do operador confirma o novo estado em sis_cliente antes de recarregar a lista. Não há repetição automática da alteração: se o resultado não for confirmado em 30 segundos, a interface informa a incerteza.

Testes: `node scripts/test-client-activation-modal.cjs`, `node scripts/test-client-lifecycle.cjs` e `node scripts/test-client-action-status.cjs`.
