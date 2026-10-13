# Versao em ajuste: bloqueio nativo

Base preservada: commit ae4afd02f379ea6f2c092de03af28938229958e5.
Referencia local: backup/manual-block-trust-ae4afd0.
Branch de trabalho: ajustes/bloqueio-nativo. A main permanece na versao publicada.

- Dashboard: removidos o contador e o card de bloqueio manual; os dez cards dividem igualmente a largura disponivel, mantendo os pontos de quebra para telas menores.
- Busca: removidos o card e o filtro especificos de bloqueio manual. Bloquear e Desbloquear usam a acao nativa do MK-AUTH. O modal preserva o motivo e o historico, sem forcar tipobloq.
- Os avisos explicam o desbloqueio financeiro, o prazo de corte e a configuracao em Opcoes > Recursos de sistema. Para liberacao temporaria com debitos, orientam o desbloqueio de confianca.
- Em uma futura instalacao, o instalador remove o trigger tig_guard_dashboard_manual_block e o antigo enforcer periodico. Nao altera clientes, titulos, configuracoes financeiras ou historico. A remocao falha explicitamente se o banco nao puder ser acessado.

Aplicado com autorizacao no Visual Net Fibra em 13/10/2026. Backup anterior: /opt/mk-auth/backups/codex-native-block-20261013_090145. Foram aplicados somente os quatro arquivos de interface alterados e removida a protecao; outras diferencas locais do servidor foram preservadas. Validacao PHP e hashes dos arquivos passaram; o trigger persistente nao existe apos a alteracao. Confirmar servidor, alteracao e backup antes de instalar em outros ambientes. Nao executar o instalador no notebook.

Para novas instalacoes e atualizacoes desta versao, selecionar explicitamente REPO_REF=ajustes/bloqueio-nativo no bootstrap. O bootstrap sem essa variavel continua usando a main preservada, que ainda possui a protecao antiga.

Teste do fluxo do modal: node scripts/test-native-block-modal.js.

Preferencia do RADIUS: Ocultar logins desativados agora e global por instalacao, salva em sis_opcao.radius_hide_disabled via endpoint autenticado e protegido por CSRF. Todos os usuarios, navegadores e o modal/monitor consultam a mesma opcao. O armazenamento local deixa de ser utilizado. A escolha e salva ao marcar/desmarcar; telas ja abertas sincronizam em ate 15 segundos. Aplicado no Visual Net, inicialmente ativo. Backup: /opt/mk-auth/backups/codex-block-forecast-20261013_093841. Teste: node scripts/test-radius-preferences.js.

Atualizacao de 13/10: corrigida a leitura UTF-8 dos textos na implantacao do Visual Net. O modal consulta boletos com status aberto/vencido, sem titulos excluidos, e calcula a previsao a partir do vencimento mais antigo e dos dias de corte do cliente. Mostra a soma e o primeiro dia em que o atraso supera esse prazo. Trata-se de uma estimativa: a rotina nativa possui regras e agendamento proprios. Mostra tambem observacao, isencao, cliente desativado e configuracoes de bloqueio/desbloqueio automaticos. A consulta exige autenticacao e respeita os grupos permitidos.

Backup da correcao no Visual Net: /opt/mk-auth/backups/codex-block-forecast-20261013_091203. Testes: php scripts/test-block-forecast.php e node scripts/test-block-forecast-js.js. Nenhum titulo ou estado de cliente foi alterado por esta consulta.

Validacao: cinco arquivos PHP passaram no lint e os dois scripts de instalacao passaram em bash -n, via entrada padrao sem gravar ou executar a instalacao no servidor. Os testes do modal passaram para bloqueio, desbloqueio e falha da acao nativa. A aparencia ainda precisa de revisao visual: o navegador de captura local nao abriu neste ambiente.
