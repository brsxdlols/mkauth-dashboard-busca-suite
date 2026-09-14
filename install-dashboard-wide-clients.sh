#!/usr/bin/env bash
set -euo pipefail

TARGET_FILE="${1:-/opt/mk-auth/admin/addons/dashboard/index.php}"
BACKUP_ROOT="${2:-/opt/mk-auth/backups/dashboard-wide-clients}"
PATCH_START='/* dashboard-wide-clients-patch:start */'
PATCH_END='/* dashboard-wide-clients-patch:end */'

if [ ! -f "${TARGET_FILE}" ]; then
    echo "Erro: dashboard nao encontrada em ${TARGET_FILE}" >&2
    exit 1
fi

if ! grep -Fq "dashboard-summary-row" "${TARGET_FILE}"; then
    echo "Erro: esta dashboard nao possui a estrutura compativel com o patch." >&2
    exit 1
fi

STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "${BACKUP_ROOT}/${STAMP}"
BACKUP_FILE="${BACKUP_ROOT}/${STAMP}/index.php"
cp -a "${TARGET_FILE}" "${BACKUP_FILE}"

TMP_FILE="$(mktemp)"
trap 'rm -f "${TMP_FILE}"' EXIT

awk -v patch_start="${PATCH_START}" -v patch_end="${PATCH_END}" '
    BEGIN { inserted = 0; skipping_old_patch = 0 }
    index($0, patch_start) {
        skipping_old_patch = 1
        next
    }
    skipping_old_patch && index($0, patch_end) {
        skipping_old_patch = 0
        next
    }
    skipping_old_patch { next }
    !inserted && /<\/style>/ {
        print "        " patch_start
        print "        @media (min-width: 992px) {"
        print "            .dashboard-summary-row {"
        print "                display: grid;"
        print "                grid-template-columns: minmax(0, 1fr) 330px;"
        print "                column-gap: 8px;"
        print "                margin-right: 0;"
        print "                margin-left: 0;"
        print "            }"
        print ""
        print "            .dashboard-summary-row > .dashboard-clients-column {"
        print "                width: 100%;"
        print "                max-width: 100%;"
        print "                padding-right: 0;"
        print "                padding-left: 0;"
        print "            }"
        print ""
        print "            .dashboard-summary-row > .dashboard-attendance-column {"
        print "                width: 100%;"
        print "                max-width: 100%;"
        print "                padding-right: 0;"
        print "                padding-left: 0;"
        print "            }"
        print ""
        print "            .dashboard-summary-row > [class*=\"col-\"]:not(.dashboard-clients-column):not(.dashboard-attendance-column) {"
        print "                grid-column: 1 / -1;"
        print "                width: 100%;"
        print "                max-width: 100%;"
        print "                padding-right: 0;"
        print "                padding-left: 0;"
        print "            }"
        print ""
        print "            .dashboard-summary-row .dashboard-stat-card {"
        print "                height: 126px;"
        print "                min-height: 126px;"
        print "            }"
        print ""
        print "            .dashboard-attendance-grid {"
        print "                gap: 7px;"
        print "            }"
        print ""
        print "            .dashboard-attendance-grid .dashboard-stat-card {"
        print "                padding-right: 7px;"
        print "                padding-left: 7px;"
        print "            }"
        print ""
        print "            .dashboard-attendance-grid .dashboard-stat-head {"
        print "                font-size: 10px;"
        print "                letter-spacing: -0.2px;"
        print "                overflow-wrap: normal;"
        print "                word-break: normal;"
        print "                white-space: nowrap;"
        print "            }"
        print "        }"
        print "        " patch_end
        inserted = 1
    }
    { print }
    END { if (!inserted) exit 42 }
' "${TARGET_FILE}" > "${TMP_FILE}" || {
    echo "Erro: nao foi possivel inserir o ajuste de estilos." >&2
    exit 1
}

sed -i \
    -e "s/col-12 col-md-12 col-lg-9 mb-2'/col-12 col-md-12 col-lg-10 mb-2 dashboard-clients-column'/" \
    -e "s/col-12 col-md-12 col-lg-3 mb-2'/col-12 col-md-12 col-lg-2 mb-2 dashboard-attendance-column'/" \
    "${TMP_FILE}"

if ! grep -Fq "dashboard-clients-column" "${TMP_FILE}" || \
   ! grep -Fq "dashboard-attendance-column" "${TMP_FILE}" || \
   ! grep -Fq "${PATCH_END}" "${TMP_FILE}"; then
    echo "Erro: validacao estrutural do patch falhou; arquivo original preservado." >&2
    exit 1
fi

if command -v php >/dev/null 2>&1; then
    php -l "${TMP_FILE}" >/dev/null || {
        echo "Erro: validacao PHP falhou; arquivo original preservado." >&2
        exit 1
    }
fi

cat "${TMP_FILE}" > "${TARGET_FILE}"
chown --reference="${BACKUP_FILE}" "${TARGET_FILE}" 2>/dev/null || true
chmod --reference="${BACKUP_FILE}" "${TARGET_FILE}" 2>/dev/null || true

echo "Ajuste instalado com sucesso."
echo "Backup: ${BACKUP_FILE}"
echo "Clientes: area ampla preservada."
echo "Atendimentos: largura ajustada para 330px e espaco entre blocos reduzido."
echo "Cards: altura alinhada em 126px no desktop."
