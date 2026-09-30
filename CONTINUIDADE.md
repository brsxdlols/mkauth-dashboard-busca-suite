# Continuidade — Dashboard e Busca MK-AUTH

Resumo de 30/09/2026. Nao contem credenciais nem o historico integral da conversa.

## Estado publicado

- Repositorio: https://github.com/brsxdlols/mkauth-dashboard-busca-suite
- Branch: main.
- Correcao de compatibilidade mais recente: 6411b79075d27bb4a49912d2857ddf0ecc8b9832.
- Dashboard com modos Legada, Nova e Multiempresas.
- Decisao final do usuario: Multiempresas usa a mesma Busca Nova completa; somente a dashboard tem apresentacao diferenciada. Nao reativar a listagem multi separada abandonada.
- Acesso rapido da Multi usa atalhos configurados, abaixo dos cards e antes dos graficos.
- Cards sem carne e sem titulos brancos com borda vermelha; desativados cinza; bloqueio manual ao lado de offline na Busca, sem carne e sem titulos ao final.

## Ultimas correcoes

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
