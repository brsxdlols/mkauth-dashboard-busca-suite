# Addons oficiais no instalador da suíte

O comando de atualização da suíte permanece `install-from-github.sh`.

- **Log RADIUS:** `scripts/install-radius-dependency.sh` chama o instalador oficial de `brsxdlols/mkauth-radius-logs`. Depois aplica os componentes de integração da suíte (modal, estado atual, IP/MAC e menus).
- **IPv6:** `scripts/install-ipv6-dependency.sh` chama o instalador oficial de `brsxdlols/mkauth-ipv6-addon`, para a raiz padrão `/opt/mk-auth/admin`.
- **Caixa:** `scripts/install-caixa-dependency.sh` usa o instalador e a árvore completa do commit oficial `65e625ef8bdd8a1047d85955b8fe2199aaad5199` (3.7.1) de `brsxdlols/mkauth-relatorio-caixa`, com `--no-menu`. A suíte não copia sua versão interna por cima do Caixa instalado.

O Caixa mantém configuração, carregador nativo e modelos existentes. O instalador oficial cria seu próprio backup e disponibiliza `addons/rel_caixa/rollback.sh`. O diretório de backup da suíte contém `caixa-package/install.log` e o commit utilizado.

O instalador procura PHP CLI 7.3 ou superior para executar o deploy do Caixa, sem mudar alternativas nem o PHP do sistema. Sem executável compatível, preserva o Caixa existente e emite aviso. Não instala nem atualiza PHP automaticamente. Isso não altera os requisitos de execução web do addon oficial.

Novas versões do Caixa devem ser validadas e o commit fixado atualizado nesta integração. Instalação e testes do pacote do Caixa não alteram tabelas, títulos ou saldos.

## NFCom, DICI e Relatório de Tráfego

`scripts/install-nfcom-dependency.sh` chama o `install-all.sh` oficial de
`brsxdlols/mkauth-gerador-nfcom`, na branch `main`, que executa os instaladores
oficiais de NFCom e DICI. O pacote também instala `/admin/addons/rel_trafego/`.
Os três atalhos ficam em **Provedor**. O relatório não substitui o monitor de
tráfego em tempo real já integrado à Busca.

Os instaladores oficiais usam o destino fixo `/opt/mk-auth/admin`. Em raízes
alternativas, a integração é ignorada explicitamente para não alterar outra
instalação. O download é salvo e validado antes de executar, com log em
`nfcom-dici-trafego/install.log`, dentro do backup da suíte. Uma falha interrompe
a instalação e informa o log; não é apresentada como sucesso.

Backups próprios: `/opt/mk-auth/backups/gerador_nf_dici/` e
`/opt/mk-auth/backups/dici/`. Para reversão, baixe o `install.sh` do respectivo
repositório e execute com `--rollback CAMINHO_DO_BACKUP`. Não existe reversão
automática conjunta; revise os backups e os menus antes de restaurar.

A instalação copia os addons e o layout NFCom; não emite documentos fiscais,
não gera nem envia declarações e não recebe pagamentos. A configuração e o uso
fiscal continuam sendo feitos no MK-Auth pelo operador autorizado.
