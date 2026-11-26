# Orientação para sincronização dos ramais

O alerta da Dashboard usa **Sincronização de Ramal precisa de atenção** e informa que o diagnóstico trata do acesso do MK-AUTH à API do ramal. O resultado positivo confirma acesso à API; não afirma que um teste de autenticação sozinho sincronizou cadastros ou verificou as conexões dos assinantes.

Na lista de ramais do alerta, no resultado do teste em modal e em Configurações, os diagnósticos com falha têm um botão **Como resolver**. Ao passar o mouse, aparece uma dica. Ao clicar ou ativar por teclado, abre um modal com o nome/IP do ramal e instruções conforme a mensagem:

- Usuário/senha: conferir o cadastro em Provedor → Ramais, a conta em System → Users, as permissões de API e as origens autorizadas.
- Porta: conferir IP → Services → api, porta 8728 utilizada pelo teste e liberação do acesso do servidor MK-AUTH.
- Rota/VPN: conferir IP de gerenciamento, rota e acesso do servidor ao ramal. Ping bloqueado exige conferir a API separadamente.
- Outros erros: revisão do cadastro e acesso à API, com orientação para encaminhar o diagnóstico ao responsável técnico.

O tutorial explica a diferença entre a conta API, a senha PPPoE dos assinantes e a chave compartilhada do RADIUS. Não exibe credenciais, executa comandos, envia alterações ou inicia testes automaticamente. A ação Repetir teste existente continua sendo acionada pelo operador depois da correção.

O helper compartilhado é carregado pela Dashboard e pelas Configurações. Todas as informações do diagnóstico são escapadas antes de inserir HTML; o corpo do modal usa texto. O modal funciona no celular, admite fechamento por Escape e retorna o foco ao botão de origem. As preferências de exibição existentes continuam usando a mesma chave de configuração.

Validação em Chrome/Chromium:

```sh
node scripts/test-ramal-sync-help.cjs
```

Use `CHROME_BIN` para selecionar o executável e, opcionalmente, `MKA_RAMAL_SCREENSHOT_DIR` para salvar capturas. O teste usa dados fictícios e verifica dica, contextos, desktop/celular, escape HTML, Escape/foco e ausência de requisições de rede pelo tutorial. Não utiliza roteadores ou credenciais reais.
