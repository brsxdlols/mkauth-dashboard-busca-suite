# Menu mobile: Nova ou Legada

Em **Configurações da Dashboard → Layout e navegação → Versão do menu mobile**, escolha:

- **Nova**: categorias recolhidas; toque para abrir um submenu.
- **Legada**: submenus expandidos ao abrir o menu, como na apresentação tradicional.

O menu mantém as cores e os links permitidos pelo próprio MK-AUTH. A opção vale para as páginas administrativas daquela instalação, incluindo dashboard, busca e telas nativas. O menu do computador continua com seu comportamento nativo. As correções de largura, cabeçalho e botão de abrir o menu permanecem nos dois modos.

O padrão é **Nova**. A preferência só muda ao salvar as configurações com permissão `perm_config` e token de sessão válido; não depende da escolha Nova/Legada/Multiempresas do layout da dashboard. O valor fica em `sis_opcao`, chave `suite_mobile_menu_version`. Reinstalar o pacote preserva essa escolha.

A pré-visualização aplica a escolha no rascunho da sessão, sem alterar a opção salva. O menu consulta a preferência com autenticação e sem cache HTTP em cada página. Se a consulta estiver indisponível, usa a última opção daquele navegador ou Nova. Rascunhos de pré-visualização não sobrescrevem essa lembrança.

O teste `MKA_MENU_TEST=1 php scripts/test-mobile-menu.php` usa uma tabela temporária e verifica os dois valores, o padrão e as recusas por permissão/CSRF/valor inválido; a configuração persistente é comparada antes e depois.

`node scripts/test-mobile-menu.cjs`, com `MKA_MENU_FIXTURE` apontando para HTML privado sem scripts e com CSS nativo embutido, verifica Nova/Legada em cinco larguras, abertura por toque e teclado, rolagem e isolamento da pré-visualização. `MKA_MENU_SOURCE` seleciona uma árvore de fontes em preparação e `CHROME_BIN` permite escolher o navegador. Não publique fixtures com dados da instalação.
