# Prévia e variáveis dos contratos

Na Busca Inteligente, o ícone de documento em cada cliente abre **Prévia do contrato do cadastro**. O mesmo ícone no rodapé usa os clientes selecionados (até 100). A prévia consulta o modelo atualmente vinculado ao cliente e seus dados atuais. Ela não abre, gera, substitui ou reenvia documentos assinados; os acessos existentes aos arquivos assinados permanecem separados.

O endpoint `contract_preview.php` exige a sessão administrativa, a permissão de consulta de clientes e os grupos autorizados ao usuário. UUIDs são resolvidos no servidor; login e nome enviados pelo navegador não definem o cliente. A resposta não é armazenada em cache, não executa scripts do modelo e não envia formulários.

`addons/shared/native_contract_renderer.php` é distribuído também, com conteúdo idêntico, em `brsxdlols/mkauth-addon-contratos/addons/contratos/functions/native_contract_renderer.php`. Ao alterar o renderizador, publique e teste ambas as cópias. Nenhum addon depende da instalação do outro.

O renderizador chama o mesmo método de `MKAC_VARS('cliente', login)` usado pelo gerador nativo. `%produtos%` e `%servicos%` recebem as tabelas de `sis_prodcliente`/`sis_produto` e `sis_sercontratos`, como no fluxo nativo. `%equipamentos%`, quando utilizado, mantém a integração com o módulo de equipamentos instalado. As consultas são preparadas e somente de leitura. A conexão e sua codificação são restauradas depois da montagem.

Versões cujo método nativo de expansão seja incompatível apresentam erro explícito, sem tentar executar outros métodos ofuscados. Variáveis desconhecidas também impedem a montagem incompleta. Corrija o modelo ou atualize a compatibilidade antes de assinar. O gerador nativo completo não é executado: a seleção de clientes do MK-Auth, a impressão automática, o estoque e os PDFs não são alterados.

Validação: `php scripts/test-native-contract.php addons/shared/native_contract_renderer.php` testa variáveis nativas, tabelas repetidas e vazias, quantidades, serviços, escape HTML, parâmetros preparados, erro de variável desconhecida e restauração da conexão/codificação, sem banco de produção.
