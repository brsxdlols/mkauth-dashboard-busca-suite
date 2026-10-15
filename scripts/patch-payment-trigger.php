<?php
// CLI migration for the known BRL payment notification trigger, never invoice backfill.
function mka_payment_trigger_body($body) {
    $original = "BEGIN IF NEW.status = 'pago' AND EXISTS (SELECT 1 FROM sis_cliente WHERE login = NEW.login AND zap <> 'nao' AND cli_ativado <> 'n') AND DATE(NEW.datapag) = DATE(NOW()) THEN INSERT INTO brl_pago (id,data,login,coletor,datavenc,datapag,valor,valorpag,formapag) VALUES (NEW.id,NOW(),NEW.login,NEW.coletor,NEW.datavenc,NEW.datapag,NEW.valor,NEW.valorpag,NEW.formapag); END IF; END";
    $corrected = "BEGIN IF NEW.status = 'pago' AND NOT (OLD.status <=> 'pago') AND EXISTS (SELECT 1 FROM sis_cliente WHERE login=NEW.login AND zap<>'nao' AND cli_ativado<>'n') AND DATE(NEW.datapag)=CURDATE() THEN INSERT INTO brl_pago (id,data,login,coletor,datavenc,datapag,valor,valorpag,formapag) VALUES (NEW.id,NOW(),NEW.login,NEW.coletor,NEW.datavenc,NEW.datapag,NEW.valor,NEW.valorpag,NEW.formapag) ON DUPLICATE KEY UPDATE data=VALUES(data),login=VALUES(login),coletor=VALUES(coletor),datavenc=VALUES(datavenc),datapag=VALUES(datapag),valor=VALUES(valor),valorpag=VALUES(valorpag),formapag=VALUES(formapag),envio=0; END IF; END";
    $normalize = function($value) { return preg_replace('/[\s`]+/', '', strtolower(str_replace('DATE(NOW())', 'CURDATE()', $value))); };
    if ($normalize($body)===$normalize($corrected)) return $body;
    return $normalize($body)===$normalize($original) ? $corrected : null;
}
function mka_payment_trigger_install($backup) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'vertrigo',getenv('MYSQL_DB')?:'mkradius');
    if (strpos($db->server_info,'MariaDB')===false) { echo "[receber] Servidor sem CREATE OR REPLACE TRIGGER; preservado.\n"; return; }
    $lock=$db->query("SELECT GET_LOCK('mka_suite_payment_trigger',10)")->fetch_row();
    if ((int)$lock[0]!==1) throw new RuntimeException('Outra migracao de pagamentos em andamento.');
    try {
        $row=$db->query("SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='tig_brl_pag'")->fetch_assoc();
        if (!$row) { echo "[receber] Trigger BRL ausente; nenhuma alteracao financeira necessaria.\n"; return; }
        $body=mka_payment_trigger_body($row['ACTION_STATEMENT']);
        if ($body===null || $row['ACTION_TIMING']!=='AFTER' || $row['EVENT_MANIPULATION']!=='UPDATE' || $row['EVENT_OBJECT_TABLE']!=='sis_lanc') {
            echo "[aviso] tig_brl_pag personalizado/desconhecido; preservado para revisao.\n"; return;
        }
        if ($body===$row['ACTION_STATEMENT']) { echo "[receber] Correcao de baixa/estorno BRL ja instalada.\n"; return; }
        $columns=array_column($db->query('SHOW COLUMNS FROM brl_pago')->fetch_all(MYSQLI_ASSOC),'Field');
        foreach (array('id','data','login','coletor','datavenc','datapag','valor','valorpag','formapag','envio') as $column)
            if (!in_array($column,$columns,true)) throw new RuntimeException('Estrutura brl_pago nao reconhecida.');
        if (!is_dir($backup) && !mkdir($backup,0700,true)) throw new RuntimeException('Falha ao criar backup.');
        chmod($backup,0700);
        $file=$backup.'/tig_brl_pag-before.json';
        if (file_put_contents($file,json_encode($row,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false) throw new RuntimeException('Backup nao gravado.');
        chmod($file,0600);
        $parts=explode('@',$row['DEFINER'],2);
        if (count($parts)!==2) throw new RuntimeException('Definer nao reconhecido.');
        $quote=function($value){return '`'.str_replace('`','``',$value).'`';};
        $prefix='CREATE OR REPLACE DEFINER='.$quote($parts[0]).'@'.$quote($parts[1]).' TRIGGER `tig_brl_pag` AFTER UPDATE ON `sis_lanc` FOR EACH ROW ';
        $rollback=$backup.'/rollback-trigger.sql';
        if (file_put_contents($rollback,"DELIMITER //\n".$prefix.$row['ACTION_STATEMENT']."//\nDELIMITER ;\n")===false) throw new RuntimeException('Rollback nao gravado.');
        chmod($rollback,0600);
        $oldMode=$db->query('SELECT @@SESSION.sql_mode')->fetch_row()[0];
        $db->query("SET SESSION sql_mode='".$db->real_escape_string($row['SQL_MODE'])."'");
        try { $db->query($prefix.$body); } finally { $db->query("SET SESSION sql_mode='".$db->real_escape_string($oldMode)."'"); }
        echo "[receber] Trigger BRL corrigido: nova baixa apos estorno atualiza a fila existente. Backup: $backup\n";
    } finally { $db->query("SELECT RELEASE_LOCK('mka_suite_payment_trigger')"); $db->close(); }
}
if (PHP_SAPI==='cli' && realpath($_SERVER['SCRIPT_FILENAME'])===__FILE__) {
    try { if (empty($argv[1])) throw new RuntimeException('Informe o diretorio de backup.'); mka_payment_trigger_install($argv[1]); }
    catch (Throwable $error) { fwrite(STDERR,'[erro] Correcao BRL nao aplicada: '.$error->getMessage().PHP_EOL); exit(1); }
}
