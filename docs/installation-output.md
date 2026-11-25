# Saída da instalação

O cabeçalho informa 18 etapas previstas de instalação de pacotes e validações,
com estimativa de 3 a 6 minutos, sujeita ao servidor e à conexão. Esses 18 blocos
incluem vários componentes: não representam 18 downloads nem 18 addons individuais.

Cada etapa fica “em execução” com o tempo decorrido e, ao terminar, recebe
`[OK] Nome [####################] 100%`. Falhas e avisos mantêm sua classificação.
Não há barra geral nem percentuais intermediários. Os checkpoints emitidos pelos
componentes são preservados no log privado, sem simular avanço pelo tempo.

A lista nativa de sessões recebe o campo Usuário em cada cartão. A autenticação,
enumeração, indicação da sessão atual e a ação nativa Deslogar são preservadas.
O instalador mantém uma cópia imutável do controlador em
`dados/suite-native-sessions`, verifica sua integridade e recusa versões ou
alterações desconhecidas, mantendo a lista original nesses casos.
