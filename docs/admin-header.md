# Cabeçalho administrativo compartilhado

`admin_header.js` e `admin_header.css` aplicam o mesmo cabeçalho às páginas
administrativas que já usam o topo nativo `#systopo`: dashboard, clientes,
cadastros e demais telas. O instalador acrescenta um carregador próprio a
`scripts/mk-auth.js` e `addons/addon.js`, preservando os demais conteúdos. Os
carregadores são idempotentes e o componente não é montado duas vezes.

Os quatro blocos de informação são alinhados pelo topo, com o afastamento
aprovado em relação ao menu. Usuários conectados e Minhas sessões mantêm ícones,
linhas e atalhos independentes. A atualização nativa de sessões do operador não
sobrescreve os contadores. O relógio compartilhado existente continua exibindo a
hora do servidor; sua coluna recebe o mesmo alinhamento automaticamente.

A dashboard inclui o mesmo componente diretamente e fornece uma amostra inicial
dos contadores. Não mantém outra implementação desses comportamentos. Páginas
sem cabeçalho nativo, como popups e impressões, não recebem um cabeçalho novo.
Nenhum arquivo de cliente, usuário ou sessão é alterado por essa integração.

Verificações sem alterar dados operacionais:

- `node scripts/test-dashboard-session-counts.cjs`
- `python3 scripts/test-admin-header-loader.py` (Linux; árvore temporária)
- `MKA_NATIVE_CSS=/caminho/mk-auth.css node scripts/test-admin-header-ui.cjs`

`CHROME_BIN` permite escolher o Chrome/Chromium para o teste de navegador. Ele
usa uma tela sintética e respostas simuladas, sem acessar nem encerrar sessões.

## Logo e retorno ao início

A logo do provedor no cabeçalho e a logo da barra de navegação apontam para `/admin/`, respeitando o início configurado no servidor. O link do provedor é criado sem duplicação, com nome acessível e foco visível. A integração permanece nos dois carregadores globais, sem substituir páginas nativas.

No desktop, o bloco `.level-left` da logo recebe o mesmo `padding-top: 1rem` das colunas de texto. Antes, apenas as colunas diretas `.level-item` recebiam o espaço, deixando a logo próxima demais da barra. A regra mantém o comportamento de espaçamento nativo no celular. O cache dos carregadores e da Dashboard foi atualizado para `20261125-2`.


A entrada `/admin/` e os logos abrem `/admin/addons/dashboard/`. Uma regra gerenciada no inicio do `.htaccess` trata somente GET/HEAD da raiz e de index.php/index.hhvm, preservando login, demais paginas e requisicoes POST. Isso evita que um index.php de outra dashboard tenha prioridade no Apache. A regra e reaplicada nas atualizacoes com backup e sem duplicacao.

MK-Auth 24 usa o decoder nativo `MKAC_SESSAO::unserialize` e o diretorio de sessoes configurado no PHP. Quando nao existe a lista nativa `sessoes.hhvm/php`, o cabecalho usa a lista autenticada do addon. Campos de navegador/IP ausentes sao indicados como nao registrados; a lista nao cria uma acao de logout que essa versao nao fornece.

As tabelas da dashboard e da visao multiempresa recebem identificadores de campos no celular: Atividades, Contas a pagar, Chamados e Instalacoes. Os registros ficam empilhados ate 767px, incluindo estados vazios; o desktop preserva a tabela em colunas.
