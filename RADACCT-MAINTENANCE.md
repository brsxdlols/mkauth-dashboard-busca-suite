# Online e manutenção opcional da radacct

Dashboard e busca compartilham `addons/shared/online_rule.php`: login normalizado com LOWER/TRIM, cadastro ativo correspondente (principal ou adicional de um principal ativo) e sessão com acctstoptime NULL. Sessões duplicadas não aumentam o contador. Na busca, cada cartão representa o cliente principal; adicionais aparecem dentro dele. Por isso número de cartões não é número de logins.

O reconciliador mantém um único cron, a cada dois minutos, com trava contra execuções concorrentes. A guarda de ausência foi incorporada do instalador do mkauth-toolkit. Exige duas observações completas no mesmo NAS, separadas por 120 segundos, e accounting sem atualização por 10 minutos. Respostas incompletas, vazias, malformadas e ausência em massa não autorizam fechamento. Cada encerramento condicional tem cópia anterior do registro.

Na reinstalação, o instalador confere seu próprio checksum, a configuração solicitada e os arquivos reais instalados (reconciliador, guarda, status e cron). Se coincidirem, não reinstala nem executa uma conciliação extra. O cron continua ativo: só a instalação é ignorada. Falhas da execução inicial não geram marca de instalação concluída.

Reaberturas também salvam a linha anterior e usam comparação de datas para não sobrescrever accounting atualizado entre leitura e escrita. O arquivo `reopen-rollback-*.jsonl` guarda os bytes originais em `row_php_base64` (serialização PHP codificada em base64); não interpretar arquivos de rollback recebidos de terceiros.

## Manutenção separada, nunca automática

Dentro do pacote no servidor:

```bash
bash scripts/radacct-maintenance.sh --check
```

O diagnóstico não altera dados. Se existe um UNIQUE completo de uma única coluna, `acctuniqueid`, a operação termina como já aplicada. Índices compostos ou parciais não contam como essa proteção.

Após instalar, o diagnóstico também fica disponível em `bash /opt/mk-auth/scripts/mkauth-radacct-maintenance.sh --check`. O instalador apenas disponibiliza a ferramenta; não executa sua limpeza.

Para aplicar deliberadamente em janela de manutenção:

```bash
bash scripts/radacct-maintenance.sh --apply
```

Requer InnoDB e índice existente em acctuniqueid. Faz dump obrigatório e validado; copia também cada lote para uma tabela de backup antes de excluir, na mesma transação. Preserva o maior radacctid. Logins/NAS/session IDs conflitantes, IDs vazios repetidos e falhas SQL interrompem a operação. O UNIQUE é criado apenas com DDL online; se não for suportado, interrompe em vez de assumir bloqueio longo.

NULL continua permitido pela semântica do UNIQUE; o script não inventa IDs. Não deduplica por username, não apaga sessões normais, não desconecta PPPoE, não faz OPTIMIZE, não reinicia serviços e não modifica up_database.sql nem remove índices existentes. Essas duas últimas ações do script original ficam fora da automação por exigirem revisão específica de cada instalação.

Backups ficam em `/root/mkauth-radacct-maintenance-*` e em `mka_radacct_backup_*` no banco. Não restaurar dump sobre accounting ativo; restaurar primeiro em banco isolado para selecionar os registros necessários. Configuração via MYSQL_HOST, MYSQL_USER, MYSQL_PASS e MYSQL_DB.

PHP 7: compatibilidade ainda não validada. Não executar limpeza em produção como teste.
