#!/usr/bin/env bash
# Optional maintenance. Never invoked automatically by install.sh or cron.
set -euo pipefail
umask 077
mode=${1:---check}
case "$mode" in --check|--apply) ;; *) echo 'Uso: bash radacct-maintenance.sh [--check|--apply]'; exit 2;; esac
db=${MYSQL_DB:-mkradius}
[[ "$db" =~ ^[A-Za-z0-9_]+$ ]] || exit 2
export MYSQL_PWD=${MYSQL_PASS:-vertrigo}
mysql_args=(-h "${MYSQL_HOST:-127.0.0.1}" -u "${MYSQL_USER:-root}" --batch --skip-column-names "$db")
q() { mysql "${mysql_args[@]}" -e "$1"; }
has_unique() {
 q "SELECT COUNT(*) FROM (SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='radacct' GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MIN(NON_UNIQUE)=0 AND MIN(COLUMN_NAME)='acctuniqueid' AND SUM(SUB_PART IS NOT NULL)=0) indexes_ok;"
}
unique=$(has_unique)
if (( unique > 0 )); then
 echo '[OK] Já aplicado: UNIQUE completo e exclusivo de acctuniqueid existe. Nenhum DELETE, ALTER ou novo backup necessário.'
 echo 'Isso não significa um login por sessão: a dashboard conta logins distintos.'
 exit 0
fi
echo '[PENDENTE] UNIQUE(acctuniqueid) completo não encontrado.'
engine=$(q "SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='radacct';")
[[ "$engine" == InnoDB ]] || { echo '[BLOQUEADO] Exige InnoDB para backup e exclusão transacionais.'; exit 1; }
indexed=$(q "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='radacct' AND COLUMN_NAME='acctuniqueid' AND SEQ_IN_INDEX=1 AND SUB_PART IS NULL;")
(( indexed > 0 )) || { echo '[BLOQUEADO] Sem índice adequado; não farei varreduras repetidas nem DELETE.'; exit 1; }
empty=$(q "SELECT COUNT(*) FROM radacct WHERE acctuniqueid='';")
(( empty <= 1 )) || { echo '[BLOQUEADO] Vários acctuniqueid vazios. Revisão manual necessária.'; exit 1; }
duplicates=$(q "SELECT COALESCE(SUM(n-1),0) FROM (SELECT COUNT(*) n FROM radacct WHERE acctuniqueid IS NOT NULL AND acctuniqueid<>'' GROUP BY acctuniqueid HAVING COUNT(*)>1) d;")
echo "Duplicados excedentes: $duplicates"
collision=$(q "SELECT EXISTS(SELECT 1 FROM radacct a JOIN radacct b ON a.acctuniqueid=b.acctuniqueid AND a.radacctid<b.radacctid WHERE a.acctuniqueid IS NOT NULL AND a.acctuniqueid<>'' AND (NOT(BINARY a.username <=> BINARY b.username) OR NOT(a.nasipaddress <=> b.nasipaddress) OR NOT(BINARY a.acctsessionid <=> BINARY b.acctsessionid)) LIMIT 1);")
(( collision == 0 )) || { echo '[BLOQUEADO] Mesmo acctuniqueid associado a logins/NAS/sessões diferentes. Nenhuma exclusão autorizada pelo script.'; exit 1; }
if [[ "$mode" == --check ]]; then
 echo 'Diagnóstico somente leitura. Para aplicar em janela de manutenção: bash radacct-maintenance.sh --apply'
 echo 'Não modifica up_database.sql nem remove índices existentes.'
 exit 0
fi
exec 9>/var/lock/mkauth-radacct-maintenance.lock
flock -n 9 || { echo 'Manutenção já em execução.'; exit 1; }
# Recheck after acquiring the lock: another invocation may have finished.
(( $(has_unique) == 0 )) || { echo '[OK] Correção já aplicada; ignorando.'; exit 0; }
backup=$(mktemp -d /root/mkauth-radacct-maintenance-XXXXXXXX)
echo "Backup obrigatório: $backup/radacct.sql.gz"
mysqldump -h "${MYSQL_HOST:-127.0.0.1}" -u "${MYSQL_USER:-root}" --single-transaction --skip-lock-tables --hex-blob "$db" radacct > "$backup/radacct.sql"
test -s "$backup/radacct.sql"
gzip "$backup/radacct.sql"
gzip -t "$backup/radacct.sql.gz"
archive="mka_radacct_backup_$(date +%Y%m%d%H%M%S)_$$"
q "CREATE TABLE \`$archive\` LIKE radacct;"
echo "Cópia transacional dos registros removidos: $db.$archive" | tee "$backup/restore.txt"
echo 'O dump permite restauração completa em ambiente isolado. Não restaure sobre accounting ativo.' >> "$backup/restore.txt"
while :; do
 # Lock candidates until their exact current rows have been copied and deleted.
 # Reject collisions between different logins, NAS or session IDs.
 # Keep the greatest radacctid. Do not confuse duplicate logins with duplicate IDs.
 result=$(q "SET SESSION innodb_lock_wait_timeout=5;
 CREATE TEMPORARY TABLE candidates (id BIGINT PRIMARY KEY);
 START TRANSACTION;
 INSERT INTO candidates SELECT DISTINCT a.radacctid FROM radacct a JOIN radacct b ON a.acctuniqueid=b.acctuniqueid AND a.radacctid<b.radacctid WHERE a.acctuniqueid IS NOT NULL AND a.acctuniqueid<>'' AND (BINARY a.username <=> BINARY b.username) AND (a.nasipaddress <=> b.nasipaddress) AND (BINARY a.acctsessionid <=> BINARY b.acctsessionid) LIMIT 500;
 SELECT r.radacctid FROM radacct r JOIN candidates c ON c.id=r.radacctid FOR UPDATE;
 INSERT INTO \`$archive\` SELECT r.* FROM radacct r JOIN candidates c ON c.id=r.radacctid;
 DELETE r FROM radacct r JOIN candidates c ON c.id=r.radacctid JOIN \`$archive\` a ON a.radacctid=r.radacctid;
 SELECT CONCAT('REMOVED=',ROW_COUNT()); COMMIT;")
 removed=$(printf '%s\n' "$result" | sed -n 's/^REMOVED=//p')
 [[ "$removed" =~ ^[0-9]+$ ]] || { echo 'Resultado inválido. Interrompido.'; exit 1; }
 echo "Lote removido com backup: $removed"
 (( removed > 0 )) || break
 sleep 1
done
remaining=$(q "SELECT COUNT(*) FROM (SELECT acctuniqueid FROM radacct WHERE acctuniqueid IS NOT NULL GROUP BY acctuniqueid HAVING COUNT(*)>1) d;")
(( remaining == 0 )) || { echo '[BLOQUEADO] Duplicados/colisões restantes ou novos registros. Backup preservado; não criar UNIQUE.'; exit 1; }
# Online DDL only; if unsupported, stop instead of silently blocking accounting.
q 'ALTER TABLE radacct ADD UNIQUE INDEX mka_acctuniqueid_unique (acctuniqueid), ALGORITHM=INPLACE, LOCK=NONE;'
(( $(has_unique) > 0 )) || { echo '[ERRO] Validação final falhou.'; exit 1; }
echo '[OK] UNIQUE validado. Próxima execução detectará a correção e não repetirá a manutenção.'
