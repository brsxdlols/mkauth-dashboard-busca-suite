# Limpeza automática

A instalação padrão da suíte instala uma rotina diária às **07:30 no horário do servidor**.
A instalação apenas simula a primeira execução: não apaga arquivos nem executa Docker prune.
O cron inicia as limpezas posteriores. Uma trava impede duas execuções da rotina ao mesmo tempo.

## Retenção e alcance

- Mantém pelo menos os **15 backups automáticos mais recentes** do índice nativo `dados/backup.db`.
  Somente entradas `descricao='backup automatico'` e `tipo='maz'` são elegíveis.
  Arquivos acessados ou modificados nos últimos sete dias são preservados mesmo que isso retenha mais de 15.
  Backups manuais, referências compartilhadas, links e nomes de arquivo inseguros são preservados.
- Índices de arquivos ausentes são retirados somente quando são backups automáticos excedentes com mais de sete dias.
  Não remove índices de backups manuais. Nunca limpa os backups de instalação em `/opt/mk-auth/backups/`.
- Temporários: arquivos sem acesso/modificação há mais de sete dias em `tmp_bot`, `tmp_eml` e `tmp_sql`.
  Em `/tmp` e `/var/tmp`, somente arquivos diretos com prefixo `mkauth-`.
  Não varre outros aplicativos, sessões PHP `sess_*` nem links simbólicos.
- Docker: containers parados há sete dias, imagens dangling e cache de construção sem uso há sete dias.
  Não remove volumes, não reinicia Docker e não modifica `daemon.json`.
  Dados mantidos exclusivamente em containers parados serão perdidos ao remover esses containers.
- O log **desta rotina** usa rotação diária e sete arquivos retidos.
  As políticas existentes de syslog, Apache e FreeRADIUS são preservadas para não duplicar rotações.

O SQLite é verificado antes de alterar o índice. Uma cópia consistente é criada pela API de backup,
inclusive em WAL, em `/opt/mk-auth/backups/suite-cleanup/backup-index.previous.sqlite`.
A exclusão do índice é transacional; os arquivos são movidos temporariamente antes do commit e
restaurados se a transação falhar. Permissões/proprietário do banco nativo são preservados.
Uma falha posterior ao commit pode deixar um arquivo na pasta `.suite-cleanup-PID`; confira o log.

## Operação

- Configuração local preservada nas atualizações: `/etc/mkauth-suite-cleanup.json`.
  Campos: `keep_backups` (mínimo 15), `temporary_days` (mínimo 7) e `docker` (`true`/`false`).
- Simular: `python3 /usr/local/lib/mkauth-suite/cleanup.py`.
- Executar manualmente: `/usr/local/sbin/mkauth-limpeza-diaria.sh`.
- Log: `/var/log/mkauth-suite-cleanup.log`.
- Agendamento: `/etc/cron.d/mkauth-limpeza`.
- Desativar: retirar esse arquivo de `/etc/cron.d/` e guardar sua cópia fora do diretório.

## Reversão da instalação

Os arquivos substituídos e permissões anteriores ficam em `BACKUP_DA_SUITE/cleanup/`.
Usando o checkout da mesma versão da suíte:

```sh
python3 scripts/install-cleanup.py /opt/mk-auth/admin BACKUP_DA_SUITE/cleanup --restore
```

Isso restaura os arquivos/agendamento anteriores. **Não recupera arquivos ou containers já excluídos.**
A cópia do índice não recupera arquivos `.maz` apagados; não a restaure sobre o banco em uso sem revisão.
Testes usam diretórios e bancos temporários, incluindo falha da transação e reversão dos arquivos.
