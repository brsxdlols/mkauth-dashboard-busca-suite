# MK Auth Dashboard + Busca Inteligente

Pacote base para instalar e manter dois addons do MK Auth:

- `addons/dashboard`
- `addons/busca_inteligente`
- `admin/index.hhvm` redirecionando para a dashboard

Esta base ja inclui:

- correcao da dashboard para obedecer `cor_menu` do MK Auth
- `mkauth_dashboard_top.php` integrado ao `dashboard/index.php`
- correcao do monitor de trafego da busca inteligente
- aviso de erro quando a API do roteador nao responde no inicio do teste

## Estrutura

```text
admin/index.hhvm
addons/dashboard
addons/busca_inteligente
install.sh
```

## Instalar no servidor

Otimizacao automatica na instalacao: [PERFORMANCE.md](PERFORMANCE.md). O instalador mede consultas Online/Offline com tres amostras, valida equivalencia e aplica somente com ganho comprovado, backup e validacao posterior; erros ou regressao restauram a politica anterior. Nao exige cookies. O botao Executar analise de desempenho na Dashboard e Busca abre o modal para medicao HTTP manual com a sessao real. Nao executa limpeza de accounting nem desconecta adicionais.

Versao atual: bloqueio comum do MK-AUTH. A instalacao remove a antiga protecao persistente de bloqueio manual (trigger e enforcer), com backup previo, preservando os estados dos clientes e o historico. A main inclui os ajustes do Visual Net; a versao anterior esta preservada em backup/versao-ae4afd0.

Ao instalar, e executada uma checagem de online/offline somente de leitura, com limite de 45 segundos. Consulta tamanho estimado, indices e planos SQL; salva online-offline-check.log no backup e avisa se incompleta. Nao executa limpeza de radacct nem cria indices automaticamente. A checagem nao garante solucao de lentidao.

### Instalacao direta via curl

```bash
bash <(curl -fsSL -H 'Cache-Control: no-cache' "https://raw.githubusercontent.com/brsxdlols/mkauth-dashboard-busca-suite/main/install-from-github.sh?cache=$(date +%s)")
```

Opcionalmente, informe outro caminho do MK Auth e outro diretorio de backup:

```bash
bash <(curl -fsSL -H 'Cache-Control: no-cache' "https://raw.githubusercontent.com/brsxdlols/mkauth-dashboard-busca-suite/main/install-from-github.sh?cache=$(date +%s)") /opt/mk-auth/admin /opt/mk-auth/backups/codex-install
```

Esse bootstrap:

- baixa o repositorio completo do GitHub
- instala `admin/index.hhvm`
- instala `addons/dashboard`
- instala `addons/busca_inteligente`
- cria backup antes de substituir
- valida os arquivos PHP/HHVM apos a copia

### Instalacao a partir do repositorio local

Copie o repositorio para o servidor e execute:

```bash
chmod +x install.sh
./install.sh
```

Opcionalmente, informe outro caminho do MK Auth:

```bash
./install.sh /opt/mk-auth/admin
```

O instalador cria backup antes de substituir:

- `admin/index.hhvm`
- `addons/dashboard`
- `addons/busca_inteligente`

## Observacoes

- O addon da busca foi organizado no repositorio como `busca_inteligente`
- O destino esperado no servidor e `/opt/mk-auth/admin/addons/busca_inteligente`
- O topo da dashboard usa a opcao `cor_menu` gravada em `sis_opcao`


## Monitor de clientes sem o Mapa completo

O instalador consulta a última release publicada de `brsxdlols/mkauth-toolkit`,
baixa o pacote `.tar.gz` e valida o SHA-256 correspondente. Somente a base
necessária ao monitor é instalada em `addons/mapa-clientes/monitor/runtime`;
o instalador completo do Toolkit não é executado.

O monitor recebe os ajustes desta suíte e abre pela rota `/admin/addons/mapa-clientes/monitor/`.
Ele pode ser usado pela Busca mesmo quando o Mapa de Clientes completo não existe.
Essa instalação não cria `mapa-clientes/index.php` ou `index.hhvm`: o botão do
Mapa completo continua exibindo o aviso para contatar VPSCLOUD — Bruno Fontes.
Mapas completos, configurações locais e dados do provedor existentes são preservados.

As atualizações feitas no projeto do Mapa entram quando publicadas em uma nova
release do Toolkit. Falha no download, checksum ou compatibilidade preserva o
monitor anterior e produz um aviso. O backup registra a release e o SHA-256 usados.


O atualizador permanente fica em `/opt/mk-auth/scripts/mkauth-suite-monitor/install-map-monitor.py`.
O instalador do projeto Mapa pode invocá-lo como root após instalar uma release
publicada, passando o diretório administrativo e um diretório novo de backup.
Isso sincroniza a base isolada sem liberar o Mapa completo nos demais servidores.


## Dependencias e correcoes de instalacao (15/10/2026)

- `graf_faturamento` e os arquivos de graficos da Dashboard sao incluidos mesmo em MK-AUTH sem esses addons. A exibicao continua dependente das permissoes financeiras e das configuracoes.
- O modal RADIUS usa `radius/suite-live.css`, copiado em toda instalacao. O `index.php`, o CSS e as configuracoes do addon RADIUS independente existente sao preservados.
- O instalador atualiza o painel IPv4/IPv6 pelo bootstrap oficial de `brsxdlols/mkauth-ipv6-addon`, com download separado, validacao de sintaxe e log no backup. Esse painel nao substitui o addon de logs RADIUS. O instalador oficial usa destino fixo; destinos alternativos da suite nao executam essa dependencia.
- O monitor permite que enderecos longos aumentem o card de dados, sem cortar Sessoes abertas e os campos seguintes.
- A pagina nativa `receber.hhvm`/`receber.php` deixa de mostrar o aviso de valor calculado por dias bloqueados. A remocao e visual e restrita a esse aviso; nao altera multa, juros, descontos, valor do titulo, baixa ou estorno.
- A correcao encontrada no Uplink para `tig_brl_pag` foi incluida: reconhece apenas o trigger BRL original, preserva suas condicoes de cliente/data e atualiza a mesma fila `brl_pago` quando um titulo estornado e recebido novamente. Gera backup e rollback, nao altera titulos existentes nem instala BRL onde ele nao existe. Triggers personalizados desconhecidos sao preservados com aviso. O fluxo original e a correcao foram reproduzidos em banco isolado; o operador tambem confirmou o fluxo no Visual Net.

Validacao de empacotamento: `python3 scripts/test-install-dependencies.py` em Linux e `node scripts/test-receive-notice.js`. A migracao BRL e validada em schema isolado com `php scripts/test-payment-trigger.php`.


### Pacote completo Radius Logs

A suite instala/atualiza `brsxdlols/mkauth-radius-logs` pelo bootstrap oficial antes de copiar a integracao do modal. O instalador oficial substitui a pasta `radius/`, cria a integracao nativa e registra o menu; por isso a ordem e obrigatoria. Depois a suite instala seu `live.php`, os endpoints do monitor e `suite-live.css`, preservando o `index.php` e o CSS do pacote completo. Falha no download ou pacote incompleto interrompe a instalacao com log no backup. O painel IPv4/IPv6 e uma dependencia separada e nao substitui o Radius Logs.


### Reconexao do banco durante a instalacao

Os otimizadores SQL e a migracao dos atalhos fazem tentativas limitadas em erros de conexao (2002/2003/2006/2013). Na conexao local padrao, tentam TCP e socket; `MYSQL_HOST` explicitamente configurado nunca e redirecionado para outro servidor. Erros de senha ou SQL nao sao repetidos. A otimizacao continua condicionada a equivalencia e ganho; banco indisponivel nao e registrado como analise concluida. Testes: `php scripts/test-installer-database.php` e `python3 scripts/test-monitor-database.py`.


### Formato nativo e destino de chamados

Configuracoes novas da Busca iniciam com `links_ext=hhvm`; valores ja salvos sao preservados, inclusive PHP escolhido manualmente. Em Configuracoes da Busca, `Abrir Ver chamados em` seleciona Sistema nativo (comportamento anterior) ou Chamados da Busca. A segunda opcao abre `/admin/addons/busca_inteligente/chamados_abertos.php?busca=<nome>` no mesmo servidor, com o nome codificado no filtro. A preferencia e compartilhada pelos layouts novo e legado e nao altera os botoes gerais da navegacao.


### Central de chamados e registro de atendimentos

A pagina nova de chamados usa tipografia maior e cards clicaveis: Todos abertos, Atrasados, Proximas 24h, Sem agendamento e Sem tecnico. Os cards filtram os chamados ja retornados pelos filtros da pagina e pelos grupos do operador; nao alteram nenhum chamado. Qualquer visita vencida fica vermelha, as proximas 24 horas amarelas e visitas futuras azuis. Datas nativas zeradas sao tratadas como sem agendamento.

O resumo mostra a prioridade cadastrada no MK-AUTH e a data/hora de abertura. O calendario e a visualizacao inicial e apresenta duas semanas de visitas, com navegacao por periodos e filtros. Cada visita mostra cliente, assunto, prioridade e abertura. Arrastar abre uma confirmacao para manter ou alterar o horario; cancelar nao grava. As opcoes de 08h a 18h, de hora em hora, sinalizam as visitas visiveis agendadas na mesma faixa. Outro horario permite informar um horario combinado, inclusive fora do expediente, mediante confirmacao do aviso. Sobreposicoes continuam permitidas apos confirmar a orientacao de organizar equipes diferentes; a verificacao e refeita no servidor antes de gravar. O arraste grava somente em `sis_suporte.visita`. Clicar abre um editor de assunto, prioridade e data/hora, com acesso aos detalhes. Deixar a data vazia remove o agendamento. Contadores e lista refletem o resultado sem recarregar.

A API de agendamento exige sessao administrativa, `perm_chamados`, grupo de cliente permitido e CSRF. Detecta alteracoes concorrentes, recusa chamados fechados e registra antes/depois com operador em `suite_ticket_schedule_changes`. O arraste altera somente a visita; o editor altera somente visita, assunto e prioridade, preservando abertura, tecnico e demais campos nativos. `php scripts/test-ticket-schedule.php` valida essas regras em schema isolado.

Registrar atendimento abre um modal com selecao de cliente permitido, assunto e relato. Ao concluir, o operador escolhe Resolvido, Finalizar sem OS ou Continuar para ordem de servico. O historico e salvo em `suite_support_attendance`, com operador, data, resultado e chave que impede duplicacao por reenvio. A API exige a sessao nativa, `perm_chamados`, acesso ao grupo do cliente e token CSRF para gravacao.

A confirmacao oferece Voltar aos chamados, Editar registro e Reabrir atendimento. O historico permite retornar aos registros anteriores. O autor ou operador com `perm_config` pode editar/reabrir dentro dos grupos permitidos; editar preserva o resultado e reabrir permite concluir novamente no mesmo registro. Alteracoes guardam os dados anteriores e posteriores em `suite_support_attendance_changes`, com controle de versao para impedir sobrescritas concorrentes. A migracao preserva atendimentos existentes.

Continuar para OS registra o encaminhamento e oferece o formulario nativo de chamado com cliente e relato preenchidos. A OS so e criada quando o operador revisa e grava o formulario nativo; o preenchimento nao envia o formulario automaticamente. Os registros de atendimento nao encerram chamados existentes. O instalador cria a tabela aditivamente e instala o carregador do preenchimento com backup de `scripts/mk-auth.js`.

Abrir chamado e a continuacao para OS abrem o formulario nativo em um modal na mesma pagina. A autenticacao, os campos e a gravacao continuam sendo do MK-AUTH. Apos o envio do formulario, fechar o modal atualiza a pagina preservando seus filtros e o periodo do calendario; fechar sem enviar nao grava nem atualiza. O carregador de preenchimento do atendimento tambem funciona dentro desse modal.

O formulario usa o layout da Central, organizado em Cliente e atendimento, Agendamento e equipe e Detalhes do chamado. O cabecalho do provedor, navegacao e paineis fixos nativos ficam ocultos dentro do modal. Os campos existentes sao reorganizados sem recriar o formulario: IDs, campos ocultos, valores, validacao, busca de cliente e destino de envio nativos sao preservados. A folha `ticket_native_form.css` aplica a tipografia e os cards responsivos somente nesse documento do modal.

Os paineis auxiliares `quickview` e modais nativos continuam disponiveis sobre o formulario: os botoes + de assunto, prioridade e demais itens mantem os manipuladores originais e permitem inserir/selecionar valores sem sair da pagina. Somente a navegacao e os paineis fixos que cercavam o formulario ficam ocultos.

A lista de clientes usa o componente autoComplete nativo e seus menus `autocomplete-sugestoes`, preservando a selecao de nome, UUID, login e ramal. Os menus nativos e jQuery UI ficam disponiveis mesmo quando o restante da pagina interna esta oculto.

### Historico completo de atendimentos

A guia Atendimentos (`busca_inteligente/atendimentos.php`) abre nos registros criados hoje, pela data de abertura, para todos os atendentes dos clientes permitidos ao usuario. Permite filtrar datas, usuario, cliente/login e resultado; Todo o periodo remove a restricao de datas. Meus atendimentos seleciona o usuario logado. Cada registro oferece Todos os atendimentos deste cliente, que usa o UUID e inclui todos os periodos. A consulta tem paginacao de 25 registros e respeita os grupos de clientes tambem nos totais e na lista de usuarios.

O bloco do modal e identificado como ate 20 registros mais recentes, de todos os dias e atendentes, com link para a guia completa. Ver/editar registro usa o mesmo modal, com as permissoes existentes de autor/administrador para edicao e reabertura. O historico atualiza apos salvar, mantendo os filtros. `php scripts/test-attendance-history.php` valida datas, filtros, grupos e paginacao em schema isolado.

### Lembretes de visitas

Teste pelo Linux: `php /root/test-visit-reminder.php bruno chamado` ou `php /root/test-visit-reminder.php bruno instalacao`. O comando cria somente um aviso de demonstracao para o usuario informado; nao cria atendimento, instalacao ou OS. Instalacoes usam destaque azul; ordens de servico usam amarelo.

O instalador inclui avisos globais no canto superior direito do MK-AUTH para visitas de chamados e instalacoes, a partir dos 10 minutos anteriores. A consulta ocorre a cada 30 segundos na pagina ativa. O aviso identifica cliente, tipo e horario, permanece depois do horario e ao navegar/recarregar, e so e dispensado pelo usuario no botao Fechar. A dispensa e individual e persistida em `suite_visit_reminders`, sem alterar chamados, instalacoes ou dados financeiros. Reagendamentos geram outro aviso para o novo horario; visitas alteradas/canceladas e dados fora das permissoes atuais deixam de ser apresentados. Sao respeitados os grupos de clientes e a propriedade/permissoes nativas de instalacoes. `php scripts/test-visit-reminders.php` valida limites de horario, persistencia, dispensa e escopos em schema descartavel.

### Central de instalacoes (calendario)

A guia `busca_inteligente/instalacoes.php` usa calendario como abertura, lista, pesquisa por nome/login/endereco, situacao e tecnico. Os cards mostram todas em aberto, visitas atrasadas (inclusive semanas anteriores), proximas 24h, sem agendamento e sem tecnico. Criar e editar abrem os formularios reais do MK-AUTH em modal com cards, preservando campos, validacoes, botoes de itens e gravacao nativa.

As consultas usam `perm_instalacao` e o escopo nativo de `perm_instTodosUsuarios`: sem essa permissao, somente registros do operador e de `full_users`. Excluem `tipo=assinatura` e `status=concluido`. Reagendamento requer `perm_instAlterar`, CSRF, versao atual e auditoria propria; altera somente `visita`. Horarios de hora em hora das 08h as 18h, horarios alternativos com confirmacao e sobreposicoes permitidas mediante aviso de equipes diferentes. Contagens de disponibilidade incluem somente instalacoes visiveis ao operador.

O card Instalacoes da Dashboard usa o mesmo total e abre a nova Central por padrao. Configuracoes permite alternar para o nativo, independentemente da preferencia de Chamados. O instalador inclui pagina, API e arquivos compartilhados automaticamente. `php scripts/test-installations.php` valida escopo, sobreposicao, versao, auditoria e horarios em banco descartavel.

### Destino de Chamados na Dashboard (preferencia)

O card Chamados da Dashboard e os atalhos locais `suporte_aberto.hhvm/php` usam a Central de chamados por padrao. Em Configuracoes da Dashboard, Abrir Chamados da Dashboard em permite selecionar Central de chamados (novo) ou Sistema nativo do MK-AUTH. A preferencia `suite_dashboard_support_destination` e criada aditivamente, persiste nas atualizacoes e respeita a extensao PHP/HHVM ao abrir o nativo. E independente da opcao da Busca para o icone de chamados de cada cliente.

Validacao: `php scripts/test-support-attendance.php` usa schema isolado para testar prazos, grupos e reenvios. Interface validada em 1360px e 400px, incluindo filtros, registro resolvido e continuacao para OS, com API simulada sem dados reais.

### Historico integrado de suporte

A Busca oferece Registrar atendimento e Abrir ordem de servico em cada cliente, com formularios em modal. Atendimento sem OS permanece somente no registro do addon; a continuacao preenche a OS nativa com o relato e o marcador do atendimento. O historico reconhece a OS somente depois da gravacao real no MK-AUTH e evita oferecer outra criacao para um atendimento ja vinculado.

A guia Atendimentos consulta registros do addon e OS nativas em conjunto, com filtros por tipo, dia/periodo, usuario, cliente e resultado. Nos detalhes nativos do cliente, a aba Suporte mostra o mesmo historico integrado, com os botoes de registro e OS. As acoes nativas anteriores continuam acessiveis em um bloco recolhivel. Leituras nao copiam ou recriam registros: as duas fontes sao consultadas com o escopo de grupos do usuario. `php scripts/test-client-support.php` valida vinculo, escopos, filtros e ausencia de duplicacoes em schema descartavel.


### Pacote completo de suporte

Inclui botoes de atendimento e OS na coluna Contato da Busca, historico integrado no Suporte do cliente e atalhos para a lista de chamados. Atendimentos abre somente atendimentos do dia. O fluxo oferece Resolvido, Manter em atendimento e Continuar para ordem de servico, levando o relato para a OS na mesma janela. Em uma OS existente, Incluir registro adiciona somente um relato.

Chamados e Instalacoes compartilham a agenda semanal e miniagenda, com horarios livres em verde, destaque de hoje e configuracoes independentes de dias de trabalho e almoco. A engrenagem abre as opcoes em modais por camadas; etiquetas e cores podem ser adicionadas ou removidas em Chamados. Os avisos de visita tambem aparecem na Busca e Dashboard.

### Viabilidade sem cliente cadastrado

O botao Viabilidade, ao lado de Abrir chamado, abre um formulario proprio para coletar nome, contato, endereco, bairro, cidade, complemento e observacao, todos opcionais. A OS e criada no suporte nativo, sem criar um cliente ficticio; os dados ficam no relato e na tabela propria suite_ticket_viability. Chamados sem cliente ficam visiveis na lista, agenda e avisos do operador que os abriu. Reagendamento e novos relatos mantem esse mesmo escopo. O instalador cria a tabela aditivamente.

A Dashboard inclui Acessar portal do sistema ao lado da pesquisa e Configuracoes no canto direito. Atalhos abre um editor separado de links. Novos chamados iniciam com a data de hoje em America/Sao_Paulo, prioridade Normal e selecao explicita de tecnico ou Nao informar; a miniagenda mostra a semana e destaca hoje.


O instalador inclui o relatório de clientes offline (`rel_cli_offline`), com filtros de tempo, configurações de destaque e acesso no menu **Clientes → Relatório de clientes offline**. Reinstalações preservam as configurações salvas e a conexão personalizada do addon, sem duplicar o atalho.


### Correção isolada para Dashboard personalizada

`python3 scripts/patch-dashboard-calculations.py /opt/mk-auth/admin/addons/dashboard --dry-run` valida uma versão legada conhecida sem instalar a suíte. Retire `--dry-run` para aplicar somente `graf_periodo.php` e `cli_periodo.php`, com backup e preservação de permissões. O script recusa arquivos com cálculos personalizados ou versões desconhecidas; não altera `index.php`, configurações, estilos, atalhos ou addons de busca. Execute a partir deste pacote, para disponibilizar os dois arquivos de cálculo em `addons/dashboard/`. A otimização da consulta nativa de atividades é separada e exige sua própria validação em `scripts/optimize-native-activity-install.php`.

O instalador completo já entrega os cálculos agregados de clientes por mês e financeiro em `addons/dashboard/cli_periodo.php` e `graf_periodo.php`, e valida a sintaxe dos dois arquivos. A correção não depende da quantidade de clientes: evita repetir as consultas mensais ao histórico. Dashboards personalizadas devem usar o patch isolado acima, após validação, para preservar suas páginas.

O mapa dos clientes selecionados no relatório offline fica em `addons/rel_cli_offline/mapa.php`, com bibliotecas locais, mapa/satélite e tempo desconectado no popup. O instalador copia a rota e seus recursos junto com o addon; não modifica `clientes_map.osm.hhvm` do MK-AUTH. As imagens de mapa continuam sendo carregadas dos serviços OpenStreetMap e Esri.

O mapa possui busca por nome/login, lista lateral com os selecionados inclusive sem coordenadas, detalhes em duas colunas, tempo desconectado com atualização visual a cada 30 segundos e preferência de camada salva no navegador. “Mostrar selecionados no mapa” abre na mesma aba; “Ver no mapa” usa o modal individual. O botão “Voltar ao MK-AUTH” retorna ao relatório. O status vem das sessões abertas no RADIUS no carregamento; o contador de tempo não faz uma nova consulta ao RADIUS a cada 30 segundos. Clientes que reconectaram continuam na seleção e aparecem como online.
