# Compatibilidade nativa do MK-Auth 24.03

A instalação foi validada em MK-Auth 24.03, PHP CLI 7.3, runtime nativo PHP 7.1 e tabelas de estoque TokuDB. Essa versão tem duas estruturas diferentes das versões recentes:

- O contrato substitui as variáveis diretamente no ramo de clientes, sem `new MKAC_VARS`. O hook de `%equipamentos%` identifica esse laço e sua saída final, sem alterar a autenticação ou o ramo de solicitações/instalações. A expansão das variáveis nativas e de `%produtos%` continua sendo feita pelo código original.
- A classe FreeRADIUS possui um segundo carregamento dentro de uma função do primeiro codec base64. O adaptador intercepta somente o `eval` dessa função e mantém o restante do codec, lendo o arquivo original imutável. A única consulta alterada continua sendo a fórmula conhecida de encerramento manual: horário atual do banco com guarda `acctstoptime IS NULL`. O transporte ao NAS e as demais funções nativas permanecem intactos.

Formatos, autenticações, consultas ou ramos desconhecidos continuam sendo recusados. A instalação preserva os arquivos existentes nesses casos. Não é necessário trocar a versão do PHP, reiniciar o RADIUS ou converter as tabelas para aplicar essa compatibilidade.

O instalador de equipamentos agora apresenta o motivo da falha PHP e grava a saída completa em `client-equipment/compatibility.log` ou `schema.log` dentro do backup. A validação da desconexão também grava `compatibility.log` em seu diretório de backup. Esses arquivos são privados (`0600`) e a pasta de backup é `0700`. Uma incompatibilidade esperada produz aviso legível em vez de traceback sem a mensagem PHP.

Testes:

```sh
php scripts/test-legacy-native-codecs.php
php scripts/test-legacy-equipment-contract.php
python3 scripts/test-equipment-installer-diagnostics.py
php scripts/test-native-disconnect-clock.php
python3 scripts/test-native-disconnect-install.py /caminho/original/freeradius.class
```

Os testes de codecs e contrato não executam a autenticação, os métodos da classe nem a geração nativa de documentos. O teste de desconexão usa tabelas temporárias locais à conexão e substitui o transporte por stub: nenhum pacote é enviado ao roteador e os dados operacionais não são alterados. O teste de instalação usa arquivos isolados e verifica repetição, rollback e preservação de alterações externas.

O comando manual `sed ... /etc/cron.d/desbloqueio` não pertence a esse reparo. A ausência desse arquivo não deve ser contornada criando um cron fictício; as rotinas nativas variam entre instalações.
