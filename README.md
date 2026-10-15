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
