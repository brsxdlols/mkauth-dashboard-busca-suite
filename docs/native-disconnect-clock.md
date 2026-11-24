# Horário do encerramento manual

Algumas versões nativas do MK-AUTH encerram uma sessão em `MKAC_FREERADIUS` usando `DATE_ADD(acctstarttime, interval acctsessiontime second)`. Quando o início gravado e a duração recebida não correspondem à mesma origem temporal, esse cálculo pode produzir datas futuras. Um NTP correto no momento da consulta não corrige esse histórico.

A instalação reconhece somente a consulta conhecida e altera sua gravação para `CURRENT_TIMESTAMP()`, usando o relógio do banco no momento da solicitação. A condição `acctstoptime IS NULL` impede que a rotina sobrescreva um Accounting-Stop já recebido, inclusive se ele chegar durante a desconexão.

O transporte nativo de desconexão, seus parâmetros e os demais métodos da classe são preservados. O instalador não solicita desconexões, não reinicia serviços e não recalcula históricos. Datas antigas exigem evidência do horário; `acctupdatetime` indica a última atualização, não prova o instante exato de encerramento.

O original fica imutável em `dados/suite-native-disconnect`, fora da raiz web. O backup da instalação registra o arquivo anterior e o manifesto. A classe delega ao codec original e aplica somente a substituição da consulta. Código desconhecido, arquivos alterados e caminhos simbólicos são preservados para revisão. Reinstalações validam os hashes antes de reaplicar.

`php scripts/test-native-disconnect-clock.php` usa uma classe de teste e tabelas temporárias. Um caminho de classe nativa original pode ser informado para testar o código daquela versão. O transporte é substituído por um stub: nenhum pacote é enviado ao roteador. Os testes cobrem duração inconsistente, data futura, encerramento correto, Stop concorrente e recusa de consultas desconhecidas.
