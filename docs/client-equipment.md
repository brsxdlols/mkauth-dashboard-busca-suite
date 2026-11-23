# Equipamentos do cliente e estoque

Na edição do cliente, **Equipamentos do cliente** permite adicionar linhas com **+ Adicionar equipamento**. Cada linha representa uma unidade e possui produto do estoque, MAC/serial e comodato. O mesmo produto pode aparecer em várias linhas.

**Salvar equipamentos** grava a lista e movimenta o estoque em uma transação. Não é o botão de gravar os demais dados cadastrais. Adicionar ou remover uma linha na tela só altera o estoque depois de salvar. A tela mostra o saldo atual e o saldo previsto após salvar, considerando todas as linhas repetidas e devoluções.

- Adicionar: baixa uma unidade e cria um vínculo nativo em `sis_prodcliente`.
- Remover: devolve uma unidade e remove somente o vínculo criado pela suíte.
- Trocar o produto: devolve o anterior e baixa o novo.
- Alterar apenas MAC/serial ou comodato: não movimenta estoque.
- Salvar novamente ou repetir uma requisição após falha de rede: não duplica a baixa.
- Estoque insuficiente, alterações concorrentes ou vínculos inconsistentes: nenhuma movimentação parcial é confirmada.

É preciso ter as permissões **Alterar clientes** (`perm_altcliente`) e **Estoque** (`perm_estoque`), além de acesso ao grupo do cliente. O saldo é conferido novamente no servidor. Clientes novos precisam ser gravados antes de vincular equipamentos.

O equipamento e MAC/serial antigos são preservados. Não há importação nem baixa retroativa automática, pois esse cadastro pode não representar uma saída de estoque. Produtos vinculados por outras telas também não são removidos pela lista da suíte. Se um vínculo da suíte for removido no estoque nativo, a linha fica sinalizada e sua remoção na lista não devolve a unidade uma segunda vez.

## Contrato

- `%produtos%`: variável nativa, lista nomes e quantidades dos produtos vinculados. As unidades da suíte aparecem automaticamente nessa lista.
- `%equipamentos%`: na geração/impressão administrativa por `gera_contrato.hhvm`, lista uma linha por unidade com equipamento, quantidade, MAC/serial e comodato. Os nomes e números de série são escapados como texto. Sem lista da suíte, usa o equipamento antigo, se preenchido.

A instalação não altera modelos existentes e não emite contratos. Inclua a variável no modelo desejado. Fluxos de assinatura/geração externos que não usam `gera_contrato.hhvm` continuam com sua expansão nativa de `%produtos%`.

## Instalação e dados

`python3 scripts/install-client-equipment.py /opt/mk-auth/admin /caminho/do/backup` instala o loader e preserva o gerador original fora da pasta pública. `--dry-run` valida a compatibilidade sem criar tabelas ou alterar arquivos nativos. Instalações com tabelas de estoque sem transações são recusadas.

As tabelas da suíte são `suite_equipment_clients` (revisão), `suite_equipment_items` (MAC/serial e vínculo nativo) e `suite_equipment_operations` (gravações idempotentes). Nenhuma coluna nativa é adicionada. Movimentações constam no histórico nativo de produtos. Remover o addon não deve apagar alocações nem somar estoque; devolva os equipamentos pela lista antes de desinstalar, se necessário.
