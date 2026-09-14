#!/usr/bin/env bash
set -euo pipefail

# Remove primeiro qualquer versao antiga baseada em verificacao periodica.
rm -f /etc/cron.d/mkauth-manual-block-enforcer /etc/cron.d/mkauth-manual-block-enforcer.disabled
rm -f /opt/mk-auth/scripts/manual-block-enforcer.php

if ! mysql -uroot -pvertrigo -N -B mkradius -e "SHOW COLUMNS FROM sis_cliente LIKE 'tipobloq'" | grep -q '^tipobloq'; then
    echo "[AVISO] Esta versao do MK-AUTH nao possui sis_cliente.tipobloq; rotina periodica removida e protecao preventiva nao instalada."
    exit 0
fi

mysql -uroot -pvertrigo mkradius <<'SQL'
CREATE TABLE IF NOT EXISTS dashboard_am_manual_block_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid_cliente VARCHAR(64) NOT NULL,
    login_cliente VARCHAR(64) NOT NULL DEFAULT '',
    nome_cliente VARCHAR(255) NOT NULL DEFAULT '',
    acao VARCHAR(16) NOT NULL,
    motivo TEXT NOT NULL,
    usuario VARCHAR(100) NOT NULL DEFAULT '',
    criado_em DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_manual_block_uuid (uuid_cliente, id),
    KEY idx_manual_block_login (login_cliente, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TRIGGER IF EXISTS tig_guard_dashboard_manual_block;
DELIMITER $$
CREATE TRIGGER tig_guard_dashboard_manual_block
BEFORE UPDATE ON sis_cliente
FOR EACH ROW
BEGIN
    DECLARE v_last_action VARCHAR(16) DEFAULT NULL;
    IF OLD.bloqueado='sim' AND OLD.tipobloq='man' AND NEW.bloqueado='nao' THEN
        SET v_last_action = (
            SELECT a.acao
              FROM dashboard_am_manual_block_audit a
             WHERE a.uuid_cliente=OLD.uuid_cliente
             ORDER BY a.id DESC LIMIT 1
        );
        IF v_last_action='bloqueio' THEN
            SET NEW.bloqueado=OLD.bloqueado;
            SET NEW.tipobloq=OLD.tipobloq;
            SET NEW.data_bloq=OLD.data_bloq;
        END IF;
    END IF;
END$$
DELIMITER ;
SQL
echo "Protecao preventiva de bloqueio manual instalada."
