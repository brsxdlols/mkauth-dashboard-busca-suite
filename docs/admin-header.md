# Cabeçalho administrativo compartilhado

`admin_header.js` e `admin_header.css` aplicam o mesmo cabeçalho às páginas
administrativas que já usam o topo nativo `#systopo`: dashboard, clientes,
cadastros e demais telas. O instalador acrescenta um carregador próprio a
`scripts/mk-auth.js` e `addons/addon.js`, preservando os demais conteúdos. Os
carregadores são idempotentes e o componente não é montado duas vezes.

Os quatro blocos de informação são alinhados pelo topo, com o afastamento
aprovado em relação ao menu. Conexões de usuários e Minhas sessões mantêm ícones,
linhas e atalhos independentes. A atualização nativa de sessões do operador não
sobrescreve os contadores. O relógio compartilhado existente continua exibindo a
hora do servidor; sua coluna recebe o mesmo alinhamento automaticamente.

A dashboard inclui o mesmo componente diretamente e fornece uma amostra inicial
dos contadores. Não mantém outra implementação desses comportamentos. Páginas
sem cabeçalho nativo, como popups e impressões, não recebem um cabeçalho novo.
A instalação preserva cadastros e não encerra sessões. O componente registra metadados da própria sessão em acessos autenticados.

Verificações sem alterar dados operacionais:

- `node scripts/test-dashboard-session-counts.cjs`
- `python3 scripts/test-admin-header-loader.py` (Linux; árvore temporária)
- `MKA_NATIVE_CSS=/caminho/mk-auth.css node scripts/test-admin-header-ui.cjs`

`CHROME_BIN` permite escolher o Chrome/Chromium para o teste de navegador. Ele
usa uma tela sintética e respostas simuladas, sem acessar nem encerrar sessões.

## Logo e retorno ao início

A logo do provedor no cabeçalho e a logo da barra de navegação apontam para `/admin/addons/dashboard/`. O link do provedor é criado sem duplicação, com nome acessível e foco visível. A integração permanece nos dois carregadores globais, sem substituir páginas nativas.

No desktop, o bloco `.level-left` da logo recebe o mesmo `padding-top: 1rem` das colunas de texto. Antes, apenas as colunas diretas `.level-item` recebiam o espaço, deixando a logo próxima demais da barra. A regra mantém o comportamento de espaçamento nativo no celular. O cache dos carregadores e da Dashboard foi atualizado para `20261126-2`.


A entrada `/admin/` e os logos abrem `/admin/addons/dashboard/`. Uma regra gerenciada no inicio do `.htaccess` trata somente GET/HEAD da raiz e de index.php/index.hhvm, preservando login, demais paginas e requisicoes POST. Isso evita que um index.php de outra dashboard tenha prioridade no Apache. A regra e reaplicada nas atualizacoes com backup e sem duplicacao.

MK-Auth 24 usa o decoder nativo `MKAC_SESSAO::unserialize` e o diretorio de sessoes configurado no PHP. Quando nao existe a lista nativa `sessoes.hhvm/php`, o cabecalho usa a lista autenticada do addon. Campos de navegador/IP ausentes aguardam um novo acesso. No modo compativel, o addon oferece encerramento validado de sessoes com confirmacao, sem substituir um controlador nativo.

As tabelas da dashboard e da visao multiempresa recebem identificadores de campos no celular: Atividades, Contas a pagar, Chamados e Instalacoes. Os registros ficam empilhados ate 767px, incluindo estados vazios; o desktop preserva a tabela em colunas.

## Conexoes e compatibilidade de sessoes

O cabecalho mostra **Conexoes de usuarios** (todas as sessoes web) e **Minhas sessoes** (somente as do operador atual). Cinco sessoes de duas pessoas aparecem como cinco conexoes.

A escolha considera recursos nativos efetivamente validados, nao apenas o numero da versao: a lista nativa so e usada quando o wrapper e o controlador de logout existem e os hashes do manifesto conferem. Uma atualizacao nativa que substitua esses arquivos volta ao modo compativel ate a revalidacao do instalador. A mesma rotina e executada em instalacoes e atualizacoes.

No modo compativel, o acesso autenticado ao cabecalho registra apenas o navegador e o IP de origem da propria sessao. Nao usa IP encaminhado por cabecalho sem validacao do servidor. Dados historicos ausentes continuam indicados como aguardando um novo acesso; nao sao preenchidos com dados de outro dispositivo.

Deslogar exige confirmacao no navegador, POST com CSRF, token opaco com validade de cinco minutos e nova validacao do proprietario da sessao. O operador pode encerrar suas outras sessoes; para outro usuario, exige a permissao nativa de configuracao. A sessao atual usa o comando Sair. Identificadores de sessao nao sao enviados ao navegador. Servidores com armazenamento diferente de arquivos preservam a acao em vez de tentar uma remocao incompativel.
