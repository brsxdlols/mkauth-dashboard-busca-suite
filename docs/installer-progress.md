# Loading compacto da instalacao

Os percentuais durante a execucao usam somente os checkpoints informados pela propria etapa, como 20%, 40%, 65% e 85% do NTP. O percentual nao diminui por causa de uma mensagem repetida ou fora de ordem. Nao existe mais estimativa de porcentagem baseada no tempo decorrido.

Quando um instalador externo, como NFCOM/DICI/Trafego, nao fornece progresso numerico, a linha mostra **em execucao** e o tempo decorrido. Isso informa atividade sem inventar que metade do trabalho foi concluida. Somente a passagem para a proxima etapa, sem erro, ou o retorno bem-sucedido do processo confirma o indicador de conclusao em 100%.

O download sem tamanho total conhecido mostra KiB realmente recebidos e tempo decorrido, sem porcentagem estimada. O indicador 100% aparece quando a transferencia termina com sucesso.

Em terminal interativo, a linha respeita a largura disponivel e nao ocupa a ultima coluna, evitando quebras de linha a cada atualizacao no celular. Os nomes longos sao abreviados apenas nessa linha; o log privado mantem os detalhes completos. As atualizacoes de espera ocorrem no maximo uma vez por segundo, sem imprimir percentuais repetidos varias vezes no mesmo segundo. Sem terminal interativo, a saida usa linhas de eventos e mensagens periodicas, sem movimentacao do cursor. FORCE_COLOR habilita cores, sem transformar um pipe em terminal interativo.

Conclusao verde e erro vermelho quando cores habilitadas. NO_COLOR desativa cores. A saida completa e tempos ficam no log privado BACKUP_ROOT/install-run-IDENTIFICADOR.log, modo 0600. Falhas mantem codigo original e cancelamento encerra apenas o processo filho.
