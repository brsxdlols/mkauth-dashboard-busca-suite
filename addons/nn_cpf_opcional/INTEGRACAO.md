# Integracao em outro addon

## Regra central

Ler `sis_opcao` pela conexao do destino, onde nome = nn_permitir_sem_cpf. Somente o valor sim permite documento vazio. Chave ausente ou nao conserva o preenchimento obrigatorio. `initialize.sql` usa INSERT IGNORE para preservar configuracoes existentes.

Documento preenchido continua sujeito a opcao nativa validar_cpf. O patch de referencia preserva essa validacao do frontend; ele nao pretende acrescentar validacao fiscal completa a APIs que antes nao a possuissem.

## Pontos usados na origem

| Entrada | Responsabilidade |
| --- | --- |
| conf_opcoes.hhvm | Incluir o controle Sim/Nao no formulario de Recursos e o token oculto |
| cliente_ins.hhvm | Aplicar a regra somente ao campo cpf_cnpj no cadastro |
| cliente_alt.hhvm | Aplicar a mesma regra na alteracao |
| executar_cliente.hhvm | Chamar nn_cpf_guard_client() apos autenticar e antes do despacho de ins.cliente ou alt.cliente |
| executar_provedor.hhvm | Chamar nn_cpf_save_policy() apos autenticar e antes do fluxo nativo de opcoes |

As permissoes e o salvamento originais continuam executando. O patch nao substitui a autenticacao nem o controle de acesso do addon.

## Modulos PHP e JavaScript

`policy.php` espera a configuracao e conexao nativas carregadas. Na origem foram usados `$LOADMYSQL`, `KEY2PROVEDOR`, a sessao `MKA_Usuario` e o verificador nativo de permissao `MKAC_PERMISSAO`. A chamada concreta do verificador esta no codigo; conferir sua existencia e semantica na versao de destino antes de usar.

Para a tela de Recursos, a renderizacao gera `window.nnCpfPolicy` com mode, allowEmpty e csrf. O JavaScript insere o controle apos a linha de validar_cpf e anexa nn_cpf_token ao formulario. As duas escolhas sao salvas pelo fluxo original de Recursos; a chave nova e tratada por nn_cpf_save_policy().

Para cadastro/alteracao, o JavaScript envolve a funcao nativa verifica_form. Quando a politica permite e o CPF/CNPJ esta em branco, altera df_verificar somente durante a chamada e restaura o atributo ao final. Documento preenchido e demais campos continuam passando pelo validador original.

`nn_cpf_guard_client()` bloqueia documento vazio no servidor quando a opcao esta desligada. `nn_cpf_save_policy()` verifica perm_config, token da sessao e os valores sim/nao antes de persistir com consulta preparada.

Se o outro addon usar formularios, permissoes ou CSRF proprios, integrar a mesma regra usando os mecanismos do destino. Nao desabilitar globalmente validadores ou remover a obrigatoriedade de outros campos.

## Como os arquivos nativos foram preservados

Na origem, as cinco entradas delegam a runtimes acompanhados dos originais locais intactos, protegidos contra acesso HTTP direto. A renderizacao usa buffer de saida e nn_cpf_render(). Nos controladores, nn_cpf_filter_payload() inclui a guarda antes do despacho, depois da autenticacao nativa.

O formato dessa versao le bytes do proprio arquivo com offsets. Acrescentar codigo diretamente ao original pode quebrar sua execucao. Os runtimes mantem a leitura apontada para os originais preservados. Nenhum arquivo nativo ou payload decodificado e distribuido neste repositorio.

Esta publicacao nao inclui gerador ou instalador automatico para o formato nativo. Para outro addon, preferir integrar as funcoes ao codigo-fonte e ao instalador dele. Se a integracao precisar adaptar novamente o formato nativo, verificar a versao e testar o carregamento com os arquivos da propria instalacao, sem copiar binarios ou runtimes de outro servidor.

## Roteiro de implantacao

1. Fazer backup novo do banco, arquivos envolvidos e estado anterior da chave.
2. Identificar versao, conexao, autenticacao, permissoes, CSRF e hooks de outros addons.
3. Criar uma copia temporaria do banco e apontar os testes explicitamente para ela.
4. Integrar interface e backend, preservando os mecanismos do destino.
5. Validar sintaxe PHP/JavaScript, criacao e edicao sem documento, chave ligada/desligada, documento preenchido invalido, outro campo obrigatorio vazio, CSRF e permissao insuficiente.
6. Confirmar que nenhuma gravacao de teste ocorreu na base ativa e remover rotas temporarias de teste.
7. Aplicar o conjunto validado com reversao preparada. Ativar conforme a preferencia do provedor e validar as telas e a configuracao novamente.
8. Integrar verificacao dos hooks ao instalador/atualizador do addon; conferir apos atualizacoes nativas.

## Reversao

Restaurar somente os arquivos correspondentes ao patch, sem sobrescrever alteracoes posteriores de outros addons. Restaurar o valor anterior da chave ou sua ausencia. Validar as telas. Nao substituir um banco inteiro com dados novos para reverter apenas esta funcionalidade.

## Limites desta publicacao

O codigo de policy.php/policy.js corresponde aos modulos usados na origem. Os testes HTTP foram realizados la e nao incluem neste pacote as sessoes autenticadas, formulÃ¡rios de clientes ou backups privados. O teste publicado de frontend usa somente dados sinteticos e o JavaScript nativo local. A confirmacao do usuario na origem nao substitui o teste no addon de destino.
