# Continuidade — Dashboard e Busca MK-AUTH

Resumo de 30/09/2026. Nao contem credenciais nem o historico integral da conversa.

## Ajuste separado em 13/10/2026

Versao promovida para `main` com autorizacao em 13/10/2026. A versao anterior ae4afd0 esta preservada em `backup/versao-ae4afd0`. O bootstrap padrao agora remove a protecao persistente de bloqueio manual, com backup dos triggers e arquivos antigos, e executa uma checagem online/offline somente de leitura com relatorio e limite de 45 segundos. Nenhuma limpeza de radacct ou criacao de indices e aplicada por essa checagem. Consulte BLOQUEIO-NATIVO.md para o escopo e a migracao; os registros abaixo descrevem a versao anteriormente publicada. Os ajustes de interface foram aplicados com autorizacao no Visual Net Fibra, preservando as demais diferencas locais. A lentidao da Quality Telecom continua sem solucao comprovada.

## Estado publicado

- Nova etapa solicitada: desempenho e otimizacao apenas, sem alerta de multiplas sessoes nesta entrega. Consultar PERFORMANCE.md. Instalador interativo oferece benchmark HTTP autenticado; interface Busca > Configuracoes > Desempenho mede consultas completas, solicita confirmacao e valida depois. A aplicacao depende de ganho medido e equivalencia; a politica provisoria expira se abandonada. Nao afirmar melhora em um servidor sem executar o benchmark real nesse ambiente.

- Repositorio: https://github.com/brsxdlols/mkauth-dashboard-busca-suite
- Branch: main.
- Correcao de compatibilidade mais recente: 6411b79075d27bb4a49912d2857ddf0ecc8b9832.
- Dashboard com modos Legada, Nova e Multiempresas.
- Decisao final do usuario: Multiempresas usa a mesma Busca Nova completa; somente a dashboard tem apresentacao diferenciada. Nao reativar a listagem multi separada abandonada.
- Acesso rapido da Multi usa atalhos configurados, abaixo dos cards e antes dos graficos.
- Cards sem carne e sem titulos brancos com borda vermelha; desativados cinza; bloqueio manual ao lado de offline na Busca, sem carne e sem titulos ao final.

## Ultimas correcoes

- 13/10/2026: incorporada a protecao de bloqueio manual observada no servidor Primos Net. O trigger respeita NEW.observacao='sim' para permitir o desbloqueio de confianca pelo mk-bot. A rotina nativa controla o vencimento e a remocao da observacao; o historico de bloqueio manual e preservado.
- No caso analisado, as tentativas repetidas de desbloqueio terminaram em 09/10 as 18h34, apos a recriacao do trigger as 18h33. Isso e consistente com a correcao, sem constituir teste controlado. Em 13/10 o cliente estava fora de observacao e foi bloqueado por atraso. Nenhuma alteracao foi aplicada ao servidor durante esta analise.
- Validacao local: `python scripts/test-manual-block-trust.py` verifica a condicao SQL extraida do instalador em oito cenarios. Nao valida a instalacao do trigger no MariaDB nem executa o mk-bot.

- addons/dashboard/config.php e addons/busca_inteligente/config.php carregam a configuracao nativa quando necessaria e inicializam a sessao administrativa nativa na variante HHVM.
- MK-AUTH 26.03: sessao administrativa usa cookie por instalacao; nao assumir a sessao PHP padrao. Manter verificacoes de autenticacao e permissao, sem inventar identidade de operador.
- Carregar /opt/mk-auth/include/configure.php evita constante VERSAO2UPDATE ausente no topo nativo.
- Busca carrega manifest.json explicitamente para corrigir titulo e botao que mostravam apenas V (nome Inicio, versao 7.85 no manifesto usado).
- Dashboard e Busca abriram no ambiente NLZ apos ajustes; ultimo ajuste do titulo passou validacao PHP, sem confirmacao visual posterior registrada neste resumo.
- Menu Multi utiliza mkauth_dashboard_top.php.

## Pendencias e limites

- Lentidao no ambiente Quality Telecom ainda nao teve causa confirmada ou solucao comprovada. Retomar com diagnostico somente quando solicitado.
- Problema de desconexao apos troca de IP no ambiente Planalto foi separado em outra tarefa; nao misturar com mudancas de layout.
- Nao acessar nem atualizar Plusnet: usuario encerrou esse escopo apos rollback.
- Solicitar acessos por canal privado quando necessario. Nunca adicionar senhas, cookies, chaves, dumps ou historico integral ao GitHub.
- Copia antiga de trabalho possui alteracoes locais historicas e codigo abandonado. Nao publicar tudo indiscriminadamente; usar main do GitHub como base e revisar cada diferenca.

## Instalacao no servidor MK-AUTH

Executar em Bash no servidor escolhido, com autorizacao de manutencao. Altera arquivos da instalacao; nao e um comando para importar chats no notebook.

```bash
bash <(curl -fsSL -H 'Cache-Control: no-cache' "https://raw.githubusercontent.com/brsxdlols/mkauth-dashboard-busca-suite/main/install-from-github.sh?cache=$(date +%s)")
```

Revisar o instalador e confirmar backup antes de atualizar producao. Destino padrao: /opt/mk-auth/admin.

## Novo notebook

```bash
git clone https://github.com/brsxdlols/mkauth-dashboard-busca-suite.git
```

Abrir a pasta clonada no Codex e pedir para ler este arquivo antes de continuar. GitHub preserva codigo e este resumo, nao importa a conversa original. Para preservar historicos locais, guardar backup privado do estado .codex e das pastas de projeto com o aplicativo fechado; validar a restauracao antes de apagar a maquina antiga.
