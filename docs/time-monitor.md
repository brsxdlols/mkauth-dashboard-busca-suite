# Diagnóstico de horário e relógio administrativo

O carregador compartilhado adiciona um relógio do servidor no cabeçalho nativo UUE_Data, sem substituir o horário dos acessos. Atualiza os segundos no navegador e consulta o servidor a cada 15 segundos. Usa o fuso do PHP da instalação e funciona com PHP 7/8.

A rotina root consulta três referências NTP e exige duas concordantes (5 segundos). A diferença acima de 60 segundos gera alerta de integração RADIUS. Ausência de resposta é diagnóstico inconclusivo, não prova de firewall. Serviço parado e relatório desatualizado também são apresentados. A preferência independente suite_time_alert_enabled (s/n em sis_opcao, padrão Sim) controla apenas o popup de horário. Pode ser alterada em Configurações do Dashboard → Horário e sincronização NTP; salva automaticamente com perm_config e CSRF. Ocultar não desativa o monitor, o relógio nem a consulta/correção por essa seção. O relógio também abre os detalhes manualmente.

O diagnóstico não altera relógio, configuração, firewall, sessões nem credenciais. A correção exige sessão administrativa, perm_config, CSRF, confirmação e pedido POST. O PHP apenas enfileira uma ação fixa; a rotina root processa a fila no minuto seguinte, com trava contra concorrência. Não há sudo nem parâmetros de comando fornecidos pelo navegador.

A correção usa o serviço existente chrony/chronyd, ntpsec/ntp ou timesyncd. Não instala um sincronizador concorrente. Recusa mais de um serviço ativo. Cria backup e altera somente as configurações de fuso reconhecidas em configure.php e nos php.ini existentes, conforme a cidade/UF do provedor; preserva as demais opções e restaura os arquivos se a alteração falhar. Ajusta /etc/localtime e /etc/timezone. Em PHP com regra antiga de horário de verão, usa o UTC fixo correspondente. Localidades ambíguas são recusadas. Restabelece o serviço NTP após ajuste pontual. A verificação web compara o offset do PHP e do Linux com o esperado antes de informar sucesso. Mostra primeiro os horários locais e depois UTC para auditoria. Sem serviço instalado ou falha de comunicação, informa a pendência. Ajustar a hora pode afetar os novos registros e agendamentos. Os registros históricos não são alterados. Em ambientes sem init script reconhecido, requer intervenção administrativa.

Estado privado: /opt/mk-auth/dados/suite-time-monitor. Worker: /usr/local/lib/mkauth-suite/time-monitor.py. Cron: /etc/cron.d/mkauth-time-monitor. Diagnóstico inicial somente leitura durante a instalação.

Validação: scripts/test-time-api-compat.py usa tabelas MariaDB temporárias e uma fila em diretório temporário, sem alterar configurações persistentes nem executar reparos reais. Oito cenários executados em PHP 7.1 e PHP 8.0. A opção PHP_TEST_BIN permite escolher o interpretador. Os testes do worker ficam em scripts/test-time-monitor.py.

## Operações com retorno visível

Atualizar diagnóstico solicita uma operação check: a rotina administrativa consulta novamente as referências e o estado do serviço, sem alterar configurações. Um modal informa pedido recebido, espera pela rotina (normalmente até um minuto), andamento e resultado; falhas são mostradas explicitamente. O horário da última verificação aparece no bloco. O modal pode ser fechado sem cancelar a operação; Ver andamento permite reabri-lo.

A correção usa confirmação no modal, não confirm() do navegador. O progresso representa etapas confirmadas pelo worker (conferência, backup/fusos, sincronização, validação). Quando está correto, o botão é desabilitado. A API e o worker também evitam repetição desnecessária. NTP ativo aparece em verde, inativo em vermelho; horários/fusos corretos em verde e pendências em laranja. A exibição usa cor e texto.
