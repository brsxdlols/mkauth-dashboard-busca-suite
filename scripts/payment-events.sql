CREATE TABLE IF NOT EXISTS mka_payment_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 invoice_id INT NOT NULL, login VARCHAR(255) NOT NULL,
 amount VARCHAR(255), due_at DATETIME, paid_at DATETIME,
 description TEXT, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY created_at(created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS mka_payment_preferences (
 username VARCHAR(64) NOT NULL PRIMARY KEY, enabled TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS mka_payment_display (
 username VARCHAR(64) NOT NULL PRIMARY KEY,
 duration_seconds INT NOT NULL DEFAULT 3,
 display_mode VARCHAR(16) NOT NULL DEFAULT 'simple'
) ENGINE=InnoDB;
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS mka_payment_notify_update AFTER UPDATE ON sis_lanc FOR EACH ROW
BEGIN
 DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
 IF NEW.status = 'pago' AND NOT (OLD.status <=> NEW.status) AND COALESCE(NEW.deltitulo,0)=0 THEN
  INSERT INTO mka_payment_events(invoice_id,login,amount,due_at,paid_at,description)
  VALUES(NEW.id,COALESCE(NEW.login,''),NEW.valorpag,NEW.datavenc,NEW.datapag,NEW.obs);
 END IF;
END$$
CREATE TRIGGER IF NOT EXISTS mka_payment_notify_insert AFTER INSERT ON sis_lanc FOR EACH ROW
BEGIN
 DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
 IF NEW.status = 'pago' AND COALESCE(NEW.deltitulo,0)=0 THEN
  INSERT INTO mka_payment_events(invoice_id,login,amount,due_at,paid_at,description)
  VALUES(NEW.id,COALESCE(NEW.login,''),NEW.valorpag,NEW.datavenc,NEW.datapag,NEW.obs);
 END IF;
END$$
DELIMITER ;
