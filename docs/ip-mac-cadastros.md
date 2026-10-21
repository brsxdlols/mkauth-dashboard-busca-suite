# Ferramentas de IP e MAC

Acesse **IP / MAC** na Busca para o cadastro selecionado ou **IP / MAC dos cadastros** no LOG RADIUS. O escopo pode ser um login principal ou todos os cadastros principais (`sis_cliente`).

Opções independentes:

- Limpar somente IP, somente MAC, ambos ou nenhum.
- Auto IP: manter atual, sim ou não.
- Auto MAC: manter atual, sim ou não.

**Ver prévia** consulta quantos cadastros serão alterados. A aplicação exige confirmação da prévia. O servidor confere novamente os dados antes da gravação; alterações concorrentes exigem nova prévia. Durante a aplicação, os controles ficam indisponíveis até a resposta.

São operações pontuais. Não existe rotina que desfaça fixações manuais posteriores nem alteração automática dos padrões de novos cadastros. Não há desconexão de sessões como parte dessas operações.

Operações globais exigem `perm_config` e acesso a todos os grupos. Para um cadastro, é necessária permissão `perm_altcliente` ou a permissão global. Há sessão administrativa, CSRF, chave de repetição e validação de escopo. As tabelas próprias `suite_connection_operations` e `suite_connection_operation_items` registram a operação e os valores anteriores de cada cadastro alterado.

O instalador integra os controles ao pacote completo do RADIUS, preservando seu restante, e acrescenta **LOG RADIUS** ao menu Aplicativos existente sem criar outro menu. O leitor em PHP trata arquivos vazios como leitura bem-sucedida e limita a leitura às últimas linhas, sem depender de execução de comandos pelo PHP.

Testes: `test-connection-settings.php`, `test-connection-settings.js`, `test-radius-log-reader.php` e `test-radius-menu.js`. Os testes de gravação usam tabelas temporárias.

No escopo **Um cadastro**, digite pelo menos dois caracteres do nome ou login e selecione um resultado. A busca retorna até 15 cadastros dentro dos grupos permitidos ao operador.

## Acessos simultâneos

Na mesma ferramenta, a seção **Acessos simultâneos** permite escolher **Sim** ou **Não** para os titulares e seus adicionais. Usa o escopo escolhido acima: todos os cadastros ou um titular e seus adicionais. Exige permissão de configuração e acesso a todos os grupos, com prévia e confirmação. A operação altera somente `simultaneo`, guarda valores anteriores por tipo e ID e não configura padrões para futuros cadastros. Não modifica IP/MAC nem executa desconexões diretamente.
