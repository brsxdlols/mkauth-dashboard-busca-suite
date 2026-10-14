<?php
// TEMPORARY tables shadow the native tables only on this connection.
$conn = new mysqli('127.0.0.1', 'root', 'vertrigo', 'mkradius');
$conn->query("CREATE TEMPORARY TABLE sis_cliente (uuid_cliente VARCHAR(64), nome VARCHAR(64), data_ins VARCHAR(64), data_desativacao VARCHAR(64))");
$conn->query("CREATE TEMPORARY TABLE sis_logs (login VARCHAR(64) NULL, registro VARCHAR(255), data VARCHAR(64))");
$month = date('Y-m'); $previous = date('Y-m', strtotime('-1 month'));
$logDate = date('d/m/Y');
$s = $conn->prepare('INSERT INTO sis_cliente VALUES (?,?,?,?)');
foreach (array(array('a','Alice',$month.'-01',''),array('b','Bob','import '.$month.'-02',''),array('c','Carol',$previous.'-01',$previous.'-03')) as $row) {
    $s->bind_param('ssss', $row[0], $row[1], $row[2], $row[3]); $s->execute();
}
$s = $conn->prepare('INSERT INTO sis_logs VALUES (?,?,?)');
foreach (array(
    array('alice','inseriu o adicional: um',$logDate),
    array('alice','inseriu o adicional: dois',$logDate),
    array('alice','ativou o cliente - IP: 1',$logDate),
    array('ALICE','ativou o cliente - IP: 2',$logDate),
    array(null,'ativou o cliente - IP: 3',$logDate),
    array(null,'ativou o cliente - IP: 4',$logDate),
    array('bob','desativou o cliente - IP: 5',$logDate),
    array(null,'desativou pelo motivo teste',$logDate),
    array('bob','deletou o cliente Bob do sistema',$logDate),
    array('alice','deletou o adicional um',$logDate),
    array('outside','inseriu o adicional: outro','01/01/1990')
) as $row) { $s->bind_param('sss', $row[0], $row[1], $row[2]); $s->execute(); }
foreach (array(false,true) as $deleted) {
    $result = (function($conn,$deleted) {
        $per_meses=2; $cli_del_cancelalado=$deleted;
        require __DIR__.'/../addons/dashboard/cli_periodo.php';
        return array($num_novos,$num_add_ins,$num_reativados,$num_desativados,$num_adicional_del,$tot_cli_saldo,$tot_cli_saldo_no_add);
    })($conn,$deleted);
    $expected=array(2,2,2,$deleted?1:2,1,$deleted?4:3,$deleted?3:2);
    foreach ($expected as $i=>$value) if ($result[$i][$month]!==$value) throw new RuntimeException('Current month counter failed: '.$i);
    if ($result[3][$previous]!==1 || $result[5][$previous]!==0) throw new RuntimeException('Deactivation fallback failed');
}
echo "PASS monthly customer counts: duplicate/NULL logins, LIKE matching, both cancellation modes, fallback and totals\n";
