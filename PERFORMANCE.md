## Análise automática no navegador

Após instalar, abra `/admin/addons/busca_inteligente/performance.php` com seu login administrativo. A medição inicia automaticamente, sem exportar cookies nem informar URL no terminal. Aplicar uma otimização continua exigindo confirmação e validação antes/depois. O CLI com cookies permanece disponível apenas para uso técnico opcional.

# Diagnostico e otimizacao Online/Offline

Escopo desta etapa: desempenho e otimizacao. O alerta de multiplas sessoes PPPoE ficou para outra etapa. Sessoes simultaneas legitimas e registros duplicados de acctuniqueid sao problemas distintos; este recurso nao limpa nenhum deles.

## Pela interface

Depois da instalacao, entre na Busca > Configuracoes > Desempenho, com uma sessao administrativa que tenha acesso a todos os grupos.

1. Analisar desempenho verifica MariaDB, colunas, collations, indice de sessoes abertas e espacos nos logins. Compara as classificacoes antigas e candidatas em uma unica consulta SQL. Timeout ou incompatibilidade bloqueia a aplicacao.
2. Faz tres requisicoes completas de Online e Offline com a mesma ordenacao, 25 clientes na primeira pagina e sua sessao real. As paginas retornam metricas das consultas reais: totalizacao completa, primeira pagina, base dos cards, adicionais e Online. Offline do card e um valor derivado; nao ha uma consulta independente a fingir que foi medida.
3. Mostra medianas HTTP e de processamento SQL/aplicacao, com resultados e variacoes durante o accounting. HTTP inclui a resposta HTML inteira, mas nao mede pintura da tela nem download posterior de CSS/imagens. Medidas simplificadas nao sao apresentadas como tempo da pagina completa.
4. A regra candidata e testada apenas nas requisicoes desse diagnostico. Nenhuma politica global muda nesta fase.
5. Aplicar exige confirmacao. O servidor exige tres amostras por filtro, compatibilidade atual, ausencia de regressao superior a 10% e ganho de pelo menos 15% em um filtro originalmente acima de 0,5 segundo.
6. A politica fica provisoria por cinco minutos. Repete os testes reais, verifica classificacao e tempos de aplicacao e HTTP, confirma se seguros e reverte se falharem. Se a tela ou o CLI forem fechados, a politica provisoria expira e as requisicoes voltam a regra anterior. Backup e logs privados ficam em /var/lib/mkauth-suite-performance.

A otimizacao troca somente a regra de classificacao da Busca nos filtros on/online/off/offline por comparacoes diretas que aproveitam os indices existentes. Nao muda a regra compartilhada do Dashboard. Uma regra direta preexistente e reconhecida e preservada na reinstalacao. Codigo, versao do banco, colunas e collations alterados exigem revalidacao da politica ativada por esta ferramenta.

A politica ativada por esta ferramenta tambem confere logins irregulares nas requisicoes normais: se aparecerem espacos incompatíveis ou uma combinacao de logins vazios que altere a classificacao, volta para a regra normalizada sem renomear clientes nem modificar accounting. Esse custo faz parte da medicao posterior a aplicacao.

Clientes principais, logins cadastrados (incluindo adicionais) e sessoes PPPoE sao indicadores diferentes; nao forcamos suas contagens a coincidir. Variacao entre amostras pode vir de entradas e saidas reais; a compatibilidade e comparada dentro de uma consulta, nao por dois totais coletados em momentos diferentes.

## No final da instalacao

O instalador em terminal interativo pergunta se deseja medir pela web e avaliar a otimizacao. Para medir no Debian precisa da URL e de um arquivo privado de cookies Netscape exportado da sua propria sessao administrativa (chmod 600). Nenhum usuario ou cookie e inventado e nenhuma senha/cookie e registrada no relatorio. Se preferir nao transferir cookies, recuse a etapa do terminal e utilize a interface autenticada.

O CLI instalado em /opt/mk-auth/scripts/mkauth-search-performance.py tambem solicita autorizacao depois do diagnostico. O relatorio HTTP fica no backup da instalacao. Em execucao nao interativa nenhuma otimizacao e aplicada.

## Accounting e instalacao

Esta etapa nao executa DELETE/UPDATE em radacct, nao faz OPTIMIZE, nao cria/remove indices e nao reinicia MySQL ou FreeRADIUS. O reconciliador e novos workers de sincronizacao de adicionais nao sao instalados/executados automaticamente por esta versao do instalador. Jobs ja existentes sao preservados; qualquer manutencao ou nova instalacao dessas rotinas requer procedimento separado. A checagem leve de indices/EXPLAIN continua incluida.

Testes realizados: sintaxe PHP/Bash; fixtures MariaDB em tabelas TEMPORARY de uma conexao isolada (sem modificar tabelas nativas), incluindo adicionais, caixa, multiplas sessoes, espacos e expiracao; fixture HTTP local para recusa, confirmacao e rollback apos falha. Isso nao constitui medicao de melhora em uma instalacao real. A confirmacao de ganho ocorre somente depois das medicoes autenticadas nesse ambiente. A lentidao da Quality Telecom ainda nao tem solucao comprovada.
