# Permissões dos addons e intervalo de desbloqueio

A instalação e a atualização executam duas revisões de permissões. A primeira prepara os componentes principais da suíte. A revisão final, depois das dependências, também alcança os outros addons existentes e o carregador `addons/addon.js`, usando `scripts/fix-addon-permissions.py --all-addons`.

- Diretórios públicos recebem `0755`; temporários chamados `tmp` continuam em `0770`.
- Dono/grupo são ajustados para a conta web disponível: `www-data` ou `apache`.
- Não se aplica `chmod 755` aos arquivos.
- Links simbólicos não são seguidos. Pastas de backups/privadas e metadados como `.git`/`.ssh` são preservados, inclusive dentro dos componentes principais.
- Donos, grupos e modos anteriores ficam no backup privado. O modo padrão do helper continua limitado aos componentes da suíte; a ampliação é explícita no passe final do instalador.

Para reverter as permissões alteradas pelo passe final, usando o backup daquela instalação:

```sh
python3 scripts/fix-addon-permissions.py --restore /opt/mk-auth/admin/addons /CAMINHO/DO/BACKUP/addon-permissions-final/addon-permissions.json
```

## Cron nativo de desbloqueio

`scripts/configure-unblock-cron.py` verifica `/etc/cron.d/desbloqueio`. Se existir, identifica as chamadas nativas de:

- `/opt/mk-auth/scripts/liberar.php` — liberação financeira;
- `/opt/mk-auth/scripts/desbloq_obs.php` — liberação de observação.

São aceitas chamadas diretas ou via PHP, inclusive `-q`/`-f`. Intervalos como `*/10`, `*/5` e `*/2` tornam-se `*/1`. `*` e `*/1` já equivalem a uma execução por minuto e não geram alteração. Os outros campos de agendamento, comentários, redirecionamentos e demais tarefas permanecem intactos.

O arquivo original é salvo como `unblock-cron/desbloqueio.before`, acompanhado de JSON com as alterações e os metadados. Depois da mudança, o serviço cron reinicia uma vez. Se o reinício falhar, o arquivo original é restaurado e a etapa informa aviso. A rotina não chama os scripts financeiros manualmente e não altera estado de clientes diretamente.

Se o arquivo não existir, nenhum cron é criado. Se o comando, usuário ou agendamento não corresponder ao formato nativo reconhecido, o arquivo permanece intacto e o instalador registra um aviso para revisão. Em versões antigas, o financeiro pode ser agendado de outra forma; não se presume equivalência nem se cria tarefa duplicada. O passe final não redefine todas as tarefas do sistema para um minuto.

As revisões são repetidas nas atualizações. O PGCORTE continua sendo a última etapa obrigatória, com suas quatro condições de sucesso preservadas. O progresso total passa a 20 etapas.

## Testes isolados em Linux

```sh
python3 scripts/test-addon-permissions.py scripts/fix-addon-permissions.py
python3 scripts/test-final-addon-permissions.py
python3 scripts/test-unblock-cron.py
python3 scripts/test-install-progress.py
python3 scripts/test-pgcorte-integration.py
```

Os testes de propriedade precisam de root, mas só usam diretórios temporários. O reinício do cron é substituído por mock. Não há alteração de permissões, cron ou clientes em produção durante os testes.
