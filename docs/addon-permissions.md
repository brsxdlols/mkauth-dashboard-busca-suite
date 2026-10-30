# Compatibilidade de permissões em instalações antigas

O instalador executa `scripts/fix-addon-permissions.py` após copiar os arquivos da suíte. Ele ajusta o diretório `admin/addons` e os diretórios públicos da suíte para 0755, com proprietário www-data (ou apache quando www-data não existe). A pasta tmp fica em 0770. Modos dos arquivos são preservados; a propriedade dos arquivos da suíte passa ao usuário web.

Escopo: dashboard, busca_inteligente, dashboard-legado, busca_inteligente-legado e shared. Outros addons e destinos de links simbólicos não são alterados. Links simbólicos no lugar de uma pasta principal da suíte exigem revisão. Não há ajuste de banco, RADIUS ou sessões de clientes.

Os metadados anteriores são salvos em `BACKUP_DIR/addon-permissions/addon-permissions.json`, com acesso restrito. Reversão:

```sh
python3 scripts/fix-addon-permissions.py --restore /opt/mk-auth/admin/addons /caminho/do/backup/addon-permissions/addon-permissions.json
```

Teste isolado em Linux:

```sh
python3 scripts/test-addon-permissions.py scripts/fix-addon-permissions.py
```

O teste usa somente uma árvore temporária e verifica permissões antigas, propriedade web, tmp restrito, addons externos preservados, links simbólicos, repetição sem alterações e reversão.
