# Conferência de sessões por concentrador

Um login pode ter registros antigos abertos em outro NAS. O reconciliador não deve usar apenas o nome do login para concluir que a conexão atual já está representada no banco.

A atualização confere login normalizado, NAS e identificador de sessão (com ou sem prefixo `0x`). Registros recentes da sessão exata são preservados; uma sessão exata confirmada na API pode ser reaberta/atualizada com backup. Sessões diferentes nunca são reutilizadas como se fossem a atual. Novos registros usam o relógio do banco, sem modificar o fuso do servidor.

A conferência de presença recente usa três minutos, para renovar a confirmação pela API antes do limite de quinze minutos da Dashboard quando faltarem atualizações RADIUS. O intervalo do cron existente é preservado. A instalação atualiza o código e faz backup, mas não executa a reconciliação nem cria uma rotina nova quando não houver uma instalada. Formatos desconhecidos são preservados e indicados na saída.

A rotina mantém a trava de concorrência e as proteções de desconexão por duas observações. O teste `test-reconcile-session-identity.php` usa tabelas temporárias e não acessa roteadores.

## Queda de energia e reinícios

Com a rotina existente e o cron funcionando, o retorno do MK-AUTH ou da API não exige reinstalar o addon. A próxima execução reconsulta o concentrador. Se a sessão da CCR sobreviveu ao reinício do servidor, procura e recupera somente a mesma sessão; se a CCR criou uma sessão nova, o registro anterior não é reutilizado. Na Sim+ o intervalo verificado é de dois minutos.

Uma consulta falha, vazia ou incompleta não deve encerrar clientes em massa. As proteções de ausência exigem observações sucessivas, identidade estável e ausência de atualização recente. A trava de execução é do sistema operacional e é liberada ao terminar o processo ou reiniciar o servidor. Sem acesso ao roteador ou ao banco não há confirmação instantânea; a rotina tenta novamente no ciclo seguinte.

Os testes usam somente registros temporários para simular reinício do servidor, reinício da CCR, sessão antiga de outro NAS, sessão encerrada ainda presente e reconhecimento no ciclo seguinte. Não desligam equipamentos de produção.
