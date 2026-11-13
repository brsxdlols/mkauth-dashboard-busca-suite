# Progresso compacto da instalacao

Cada fase ocupa uma linha no terminal. Mostra 0% enquanto executa e 100% ao passar para a fase seguinte; a ultima fase somente conclui apos retorno zero do instalador. Nao ha percentuais intermediarios estimados, pois os instaladores oficiais nao fornecem essa informacao.

Conclusoes aparecem em verde e erros em vermelho quando ha terminal e cores habilitadas. NO_COLOR desativa cores. A saida detalhada e os tempos das fases ficam no log privado BACKUP_ROOT/install-run-IDENTIFICADOR.log (modo 0600). O resumo final informa o tempo total e o caminho do log.

Falhas preservam o codigo original e cancelamento encerra apenas o grupo filho do instalador. Backups e verificacoes continuam ativos; os addons continuam sequenciais.
