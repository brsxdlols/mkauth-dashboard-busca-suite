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
