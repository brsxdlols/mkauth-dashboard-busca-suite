# Progresso da instalação

O comando principal continua sendo `install-from-github.sh`. A instalação completa
baixa os addons oficiais, cria backups, valida arquivos e compara consultas SQL.
O tempo depende de rede, disco, tamanho da base e dos pacotes oficiais.

- O download da suíte usa diretamente `codeload.github.com`, com progresso quando
  há terminal, tentativas limitadas e limites de conexão/transferência.
- Na execução local, a saída original continua aparecendo. A cada dez segundos
  sem nova saída, o terminal mostra etapa atual e tempo decorrido. Isso informa
  que o processo segue ativo; não é uma porcentagem nem prova de avanço interno.
- O log completo fica em `BACKUP_ROOT/install-run-IDENTIFICADOR.log`, modo 0600.
  Os logs separados dos addons oficiais continuam nos backups correspondentes.
- A conclusão só aparece após otimização, monitor, menus e notas financeiras.
  O resumo mostra o tempo das etapas locais e as cinco fases mais demoradas.
- Falha mantém o código de saída original; interrupção encerra o grupo de
  processos iniciado pelo instalador. Não encerra serviços preexistentes.

Não há instalação paralela dos addons: vários deles alteram os mesmos menus.
Backups, verificações e consultas de desempenho não são ignorados para acelerar.
O novo resumo permite identificar uma etapa realmente lenta antes de otimizá-la.
