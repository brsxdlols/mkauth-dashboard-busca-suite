# CPF/CNPJ opcional no MK-AUTH

Implementado e confirmado pelo usuario na NOSSA NET em 26/10/2026, no MK-AUTH 26.03 / TUX 6.6.

Este pacote contem os modulos do patch e o guia para incorporar a funcionalidade a outro addon. Nao e um instalador automatico. Nao inclui credenciais, sessoes, dados de clientes, bancos, backups nem arquivos nativos do MK-AUTH.

## Comportamento

Em Opcoes > Recursos > SETUP, abaixo de Validar CPF/CNPJ, foi adicionada a opcao **Permitir cliente sem CPF/CNPJ**:

- Sim: permite cadastrar e alterar com documento vazio, incluindo somente espacos.
- Nao: exige preenchimento no formulario e no salvamento HTTP.
- Documentos preenchidos seguem a opcao nativa Validar CPF/CNPJ. Os demais campos obrigatorios continuam exigidos.
- O patch nao gera CPF ficticio e nao preenche documentos automaticamente.
- Na NOSSA NET a opcao ficou Sim. Quando a chave nao existe, a regra e Nao.

## Arquivos

- policy.php: leitura e persistencia da chave, guarda no salvamento, CSRF e configuracao da interface.
- policy.js: controle Sim/Nao e adaptacao pontual do validador nativo.
- initialize.sql: inicializa a chave sem sobrescrever um valor ja escolhido.
- tests/test_validator.mjs: verifica a integracao com o validador JavaScript nativo local.
- INTEGRACAO.md: os pontos de integracao e as particularidades da origem.
- COPIAR_OUTRO_CHAT.md: texto para levar ao chat do outro addon.

A chave usa a estrutura nome/valor de sis_opcao: nome `nn_permitir_sem_cpf`, valor `sim` ou `nao`. Nao e uma coluna nova. A leitura direta usa a conexao nativa `$LOADMYSQL`, evitando cache desatualizado da opcao propria.

O salvamento da chave verifica sessao, permissao `perm_config`, token CSRF vinculado a sessao e valores permitidos. O token e gerado com HMAC e a configuracao nativa KEY2PROVEDOR. Nenhum segredo deve ser colocado no JavaScript ou neste repositorio.

## Validacao realizada

Na copia temporaria do banco:

- Cadastro novo e alteracao sem CPF/CNPJ foram gravados com a opcao habilitada.
- Com a opcao desligada, o salvamento vazio retornou HTTP 422 e nao gravou a alteracao.
- A chave foi salva e relida como Sim e como Nao pelo fluxo HTTP de Recursos.
- Token CSRF invalido retornou HTTP 403 e preservou a chave.

No frontend foram verificados: vazio permitido, vazio bloqueado, CPF preenchido invalido recusado, CPF valido aceito e outro campo obrigatorio vazio recusado.

Na base ativa, as telas de Recursos, cadastro e alteracao responderam corretamente com a opcao habilitada. Os originais preservados ficaram protegidos contra acesso HTTP direto. O usuario confirmou o funcionamento em producao.

## Uso em outro addon

Leia INTEGRACAO.md e adapte ao codigo e ao instalador existentes no destino. As chamadas de permissao do codigo de referencia dependem da versao do MK-AUTH; precisam ser verificadas no destino. Os testes da origem nao provam compatibilidade automatica com outra versao.

Nao executar os scripts de migracao ou os SQLs financeiros da raiz deste repositorio para instalar esta funcionalidade.

Atualizacoes nativas podem substituir os pontos de entrada. Incluir a verificacao e reaplicacao controlada no instalador do addon, preservando outros patches e sem desativar atualizacoes do MK-AUTH.

## Teste JavaScript

```sh
node patches/cpf-opcional/tests/test_validator.mjs /opt/mk-auth/admin/scripts/mk-auth.js
```

O teste le o validador instalado localmente. O arquivo nativo nao e distribuido.
