#!/usr/bin/env bash
# Read-only installation diagnostics. Never clean accounting or create indexes.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)"
RULE="${1:-${SCRIPT_DIR}/../addons/shared/online_rule.php}"
if [ ! -f "$RULE" ] && [ -f /opt/mk-auth/admin/addons/shared/online_rule.php ]; then RULE=/opt/mk-auth/admin/addons/shared/online_rule.php; fi
export MYSQL_PWD="${MYSQL_PASS:-vertrigo}"
db="${MYSQL_DB:-mkradius}"
[[ "$db" =~ ^[A-Za-z0-9_]+$ ]] || exit 2
args=(-h "${MYSQL_HOST:-127.0.0.1}" -u "${MYSQL_USER:-root}" --connect-timeout=5 --batch "$db")
echo '[CHECAGEM] Online/offline: tamanho estimado, indices e planos de consulta. Somente leitura.'
mysql "${args[@]}" <<'SQL'
SELECT TABLE_NAME,ENGINE,TABLE_ROWS AS estimated_rows,ROUND((DATA_LENGTH+INDEX_LENGTH)/1048576,1) AS estimated_mb FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('radacct','sis_cliente','sis_adicional');
SELECT TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('radacct','sis_cliente','sis_adicional') ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;
SELECT CASE WHEN EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='radacct' AND SEQ_IN_INDEX=1 AND COLUMN_NAME='acctstoptime') THEN 'OK: indice inicia por acctstoptime' ELSE 'AVISO: sem indice iniciando por acctstoptime; revisar plano e tempo real' END AS active_session_index;
SQL
if command -v php >/dev/null 2>&1 && [ -f "$RULE" ]; then
  php -r 'require $argv[1]; echo "EXPLAIN ".mka_online_sql().";\nEXPLAIN SELECT c.id FROM sis_cliente c WHERE c.cli_ativado=\x27s\x27 AND NOT (".mka_online_family_sql().") LIMIT 20;\n";' "$RULE" | mysql "${args[@]}"
else
  echo '[AVISO] PHP ou regra online ausente; planos nao verificados.'
fi
echo '[CHECAGEM] Concluida. O plano nao comprova o tempo real nem a causa da lentidao; nenhuma alteracao foi aplicada ao banco.'
