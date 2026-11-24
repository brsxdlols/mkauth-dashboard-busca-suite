# Equipamentos do cliente e estoque

Na edição do cliente, o **+** ao lado de **Equipamento** repete os campos nativos **Equipamento** e **MAC/Serial**, no mesmo local e com o mesmo estilo do formulário. Cada linha controlada pelo estoque representa uma unidade. O mesmo produto pode aparecer em várias linhas. Cada linha exibe **Comodato: Sim/Não** e permite alterar essa informação individualmente. Alterar o comodato do primeiro equipamento não modifica os demais.

O botão **Gravar** original salva a lista de equipamentos e prossegue com a gravação nativa do cadastro. Os campos passam primeiro pela validação do formulário. Uma falha no estoque mantém o formulário aberto para corrigir ou tentar novamente. Adicionar ou remover uma linha na tela só movimenta o estoque ao gravar. O saldo aparece apenas no primeiro seletor, onde o equipamento é escolhido para adicionar. As linhas abaixo não repetem o estoque. O número exibido já desconta as inclusões e soma as remoções pendentes desta tela. O banco de estoque só é alterado ao gravar.

Os nomes da lista rápida nativa não têm quantidade de estoque. Quando não há produto/saldo cadastrado, o primeiro seletor mostra **Estoque: ?**. Passar o mouse mostra uma dica; clicar abre uma explicação em modal com acesso ao cadastro nativo de produtos e quantidades. Saldo zero cadastrado aparece como **Estoque: 0**. Ao retornar da tela de estoque, os saldos são consultados novamente sem descartar alterações locais.

- Adicionar: baixa uma unidade e cria um vínculo nativo em `sis_prodcliente`.
- Remover: devolve uma unidade e remove somente o vínculo criado pela suíte.
- A primeira linha serve apenas para selecionar e adicionar com o botão +; não tem botão de remover.
- Nos itens adicionados, o equipamento fica bloqueado. Para trocar, remova a unidade e adicione o novo equipamento. Comodato e MAC/serial continuam editáveis.
- Sem quantidade cadastrada ou sem saldo disponível, o + informa que o equipamento não foi adicionado e orienta acessar **Provedor → Controle de produtos**, cadastrar o produto se necessário e registrar uma entrada no estoque. O modal inclui um atalho para essa tela; a explicação também fica visível junto aos campos. Não há inclusão nem movimentação até existir saldo válido.
- Alterar apenas MAC/serial ou comodato: não movimenta estoque.
- Salvar novamente ou repetir uma requisição após falha de rede: não duplica a baixa.
- Estoque insuficiente, alterações concorrentes ou vínculos inconsistentes: nenhuma movimentação parcial é confirmada.

É preciso ter as permissões **Alterar clientes** (`perm_altcliente`) e **Estoque** (`perm_estoque`), além de acesso ao grupo do cliente. O saldo é conferido novamente no servidor. Clientes novos precisam ser gravados antes de vincular equipamentos.

O equipamento e MAC/serial antigos são preservados. Não há importação nem baixa retroativa automática, pois esse cadastro pode não representar uma saída de estoque. Produtos vinculados por outras telas também não são removidos pela lista da suíte. Se um vínculo da suíte for removido no estoque nativo, a linha fica sinalizada e sua remoção na lista não devolve a unidade uma segunda vez.

## Contrato

- `%produtos%`: variável nativa, lista nomes e quantidades dos produtos vinculados. As unidades da suíte aparecem automaticamente nessa lista.
- `%equipamentos%`: na geração/impressão administrativa por `gera_contrato.hhvm`, lista uma linha por unidade com equipamento, quantidade, MAC/serial e comodato. Dez unidades geram dez linhas, mesmo com modelos repetidos. Lista tanto comodato Sim quanto Não. Os nomes e números de série são escapados como texto. Inclui também o equipamento antigo se ele for distinto das unidades vinculadas, sem duplicar o primeiro campo quando ele representa uma unidade já listada.

A instalação não altera modelos existentes e não emite contratos. Inclua a variável no modelo desejado. Fluxos de assinatura/geração externos que não usam `gera_contrato.hhvm` continuam com sua expansão nativa de `%produtos%`.

`php scripts/preview-equipment-contract.php > exemplo.html` gera uma prévia com seis unidades fictícias usando o mesmo renderizador da variável. Não conecta ao banco nem emite um contrato real. O estilo final da tabela acompanha o modelo de contrato utilizado.

## Instalação e dados

`python3 scripts/install-client-equipment.py /opt/mk-auth/admin /caminho/do/backup` instala o loader e preserva o gerador original fora da pasta pública. `--dry-run` valida a compatibilidade sem criar tabelas ou alterar arquivos nativos. Instalações com tabelas de estoque sem transações são recusadas.

As tabelas da suíte são `suite_equipment_clients` (revisão), `suite_equipment_items` (MAC/serial e vínculo nativo) e `suite_equipment_operations` (gravações idempotentes). Nenhuma coluna nativa é adicionada. Movimentações constam no histórico nativo de produtos. Remover o addon não deve apagar alocações nem somar estoque; devolva os equipamentos pela lista antes de desinstalar, se necessário.

Para testar o layout com o CSS nativo, execute `node scripts/test-client-equipment-native-ui.cjs` com `MKA_NATIVE_CSS` apontando para `estilos/mk-auth.css`. `CHROME_BIN` permite escolher o Chrome/Chromium. O teste usa dados fictícios e verifica desktop/celular, preservação dos campos, repetição das linhas, validação, Gravar, envio pelo teclado e repetição após erro de rede. Não acessa clientes nem movimenta estoque real.

