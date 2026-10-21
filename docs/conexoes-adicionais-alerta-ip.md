# Conexões adicionais e alerta de IP

O login de um adicional abre um modal com cadastro, LOG RADIUS, últimas conexões, diagnóstico e bloqueio/desbloqueio próprio. A edição e o bloqueio usam a página nativa do adicional, incluindo sua confirmação. O reparo usa exclusivamente as rotinas nativas de atualização e desconexão do adicional verificadas nas versões 25.08 e 26.02; versões sem essas rotinas recebem mensagem de indisponibilidade.

O acompanhamento do reparo consulta a conexão a cada cinco segundos enquanto o diagnóstico está aberto. A sessão anterior não confirma uma reconexão nova. A verificação continua após 90 segundos, inclusive quando a reconexão demora mais, e encerra ao sair do diagnóstico.

Dashboard, Dashboard multi e Busca verificam IPs em segundo plano a cada minuto. O alerta identifica logins distintos com o mesmo IPv4 no mesmo concentrador, usando sessões abertas em `radacct`. Considera titular e adicionais, respeita os grupos do operador, ignora sessões repetidas do mesmo login e não trata endereços iguais em concentradores diferentes como conflito.

O aviso só fecha pelos botões **Fechar** ou **Analisar cliente**. Fechar dispensa aquela ocorrência na página atual; uma ocorrência nova volta a avisar. O alerta diferencia IP coincidente com a fixação do cadastro de IP sem fixação cadastrada. A ausência de fixação não prova qual pool entregou o endereço. Sessões devem ser confirmadas no concentrador antes da correção; o recurso não altera IPs nem derruba conexões automaticamente.

Testes: `test-additional-ip-conflicts.php`, `test-additional-alert-modals.js` e `test-repair-progress.js`.

## Estado dos cards na Busca

A Busca de conexões consulta o estado dos cadastros exibidos a cada cinco segundos, em lote de até 100 logins e respeitando os grupos do operador. Atualiza o boneco, a indicação de IP/Online/Offline e oculta a queda anterior ao reconectar. Não recarrega a página nem troca os resultados ou fecha modais. Pausa consultas em abas ocultas e encerra a consulta ao sair. Uma falha de consulta mantém o estado anterior. A presença online usa sessões abertas em `radacct`, como o Log RADIUS; não consulta diretamente o MikroTik.
