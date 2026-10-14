# Dashboard: carregamento e atividades nativas

Os layouts Nova e Multiempresas carregam os gráficos em uma requisição separada, após a página principal. As permissões financeiras, filtros e configuração dos gráficos continuam sendo verificados no servidor. A sessão é liberada antes das consultas históricas.

O instalador também verifica o endpoint nativo `logs_ajax.hhvm`. Na versão compatível, a consulta de `vtab_ativ` materializa todo o histórico antes de retornar 90 atividades e mantém a sessão ocupada, bloqueando a navegação para a Busca.

A correção limita os candidatos em cada origem antes do `UNION DISTINCT`, preservando filtros, projeção e paginação. Libera a sessão somente depois da autenticação nativa. Não altera tabelas, índices, registros ou permissões. A versão original fica em `/var/lib/mkauth-suite-native-activity`, fora da pasta pública e de diretórios regenerados pelo PHP. O backup da instalação inclui `logs_ajax-before.hhvm` e o relatório `native-activity-performance.json`.

Antes de aplicar, o instalador valida a estrutura do programa nativo, a definição da view, as chaves primárias e as collations dos filtros. Compara a página original e a otimizada na mesma transação de leitura, sem query cache. Caso não comprove compatibilidade ou igualdade, mantém o endpoint nativo e registra o motivo no relatório. A correção não redistribui o programa nativo; usa a cópia existente na instalação.

Em uma base com aproximadamente seis mil assinantes, a consulta nativa passou de 19,2 s para 0,45 s sem query cache. A Dashboard ficou disponível em cerca de 1,6 s, e os gráficos carregaram separadamente em cerca de 4,1 s na primeira abertura. A navegação para Online deixou de esperar pelos gráficos. Esses tempos são medições desse servidor, não uma garantia para outras bases.

Validação: `scripts/test-native-activity-performance.php` compara filtros, páginas, duplicatas entre origens e datas nulas usando apenas tabelas temporárias, em PHP 7.3 e PHP 8. O teste `scripts/test-dashboard-graphs-frame.js` verifica o início após o carregamento principal e a validação de origem e janela das mensagens do iframe. Também foram verificados o bloqueio de acessos anônimos e a persistência da cópia original após recarga do PHP.
