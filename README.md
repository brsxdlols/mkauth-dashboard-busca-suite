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
