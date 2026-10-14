<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/native_activity_performance.php';
function activity_assert($value,$message){if(!$value)throw new RuntimeException($message);}
$body="include('configure.php');include('conexao.php');include('sessao_admin.hhvm');\$nativeSql=\"SELECT *\nFROM vtab_ativ\nWHERE tipo <> 'central'\nORDER BY data DESC\nLIMIT 0,90\";\$nativeResult=\$queryFunction(\$nativeConnection,\$nativeSql);echo 'native rendering';";
$patched=mka_activity_patch_body($body);
activity_assert(strpos($patched,"include('sessao_admin.hhvm');")<strpos($patched,'session_write_close()'),'Native authentication must finish before releasing the session.');
activity_assert(strpos($patched,"echo 'native rendering';")!==false,'Native rendering must remain intact.');
try{mka_activity_patch_body($body.'$_SESSION["write"]=true;');throw new LogicException('Session mutation accepted.');}catch(RuntimeException $expected){}
activity_assert(mka_activity_recent_sql('SELECT * FROM unrelated')==='SELECT * FROM unrelated','Unrelated SQL changed.');
$db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'vertrigo',getenv('MYSQL_DB')?:'mkradius');
$db->set_charset('utf8mb4');
// Temporary tables shadow the production names only in this connection.
$db->query("CREATE TEMPORARY TABLE sis_ativ(id varchar(64) PRIMARY KEY,registro longtext,data datetime NULL,login varchar(64),ip varchar(64),tipo varchar(20),operacao varchar(8))");
$db->query("CREATE TEMPORARY TABLE sis_logs(id int PRIMARY KEY,registro longtext,data varchar(255) NULL,login varchar(64),tipo varchar(20),operacao varchar(8))");
$a=$db->prepare('INSERT INTO sis_ativ VALUES(?,?,?,?,?,?,?)');
$l=$db->prepare('INSERT INTO sis_logs VALUES(?,?,?,?,?,?)');
for($i=1;$i<=240;$i++){
    $id=(string)$i;$record='activity '.$i;$unix=strtotime('2026-01-01')+$i*60;$date=$i===1?null:date('Y-m-d H:i:s',$unix);$logDate=$i===1?null:date('d/m/Y H:i:s',$unix);$login=$i%2?'operator A':'operator B';$ip='0.0.0.0';$type=$i%3?'admin':'central';$operation='test';
    $a->bind_param('sssssss',$id,$record,$date,$login,$ip,$type,$operation);activity_assert($a->execute(),'Activity fixture insertion failed.');
    // Every seventh row duplicates the other branch exactly. Remaining rows
    // have a different timestamp and must survive UNION DISTINCT.
    if($i%7!==0 && $i!==1)$logDate=date('d/m/Y H:i:s',$unix+30);
    $l->bind_param('isssss',$i,$record,$logDate,$login,$type,$operation);activity_assert($l->execute(),'Log fixture insertion failed.');
}
$checks=0;
foreach(array("tipo <> 'central'","tipo = 'admin' AND login = 'operator A'","tipo = 'central' AND login = 'operator B'","tipo = 'admin' AND login = 'missing'") as $where){
    foreach(array(array(0,90),array(90,90),array(180,90),array(0,1000)) as $page){
        list($offset,$count)=$page;
        $native="SELECT * FROM vtab_ativ WHERE $where ORDER BY data DESC LIMIT $offset,$count";
        $reference="SELECT * FROM (SELECT id,registro,data,login,ip,tipo,operacao FROM sis_ativ UNION SELECT id,registro,STR_TO_DATE(data,'%d/%m/%Y %H:%i:%s') AS data,login,'0.0.0.0' AS ip,tipo,operacao FROM sis_logs) AS native_view WHERE $where ORDER BY data DESC LIMIT $offset,$count";
        $before=$db->query($reference)->fetch_all(MYSQLI_ASSOC);$after=$db->query(mka_activity_recent_sql($native))->fetch_all(MYSQLI_ASSOC);
        activity_assert($before===$after,'Activity pagination/filter/UNION equivalence failed.');$checks++;
    }
}
printf("PASS %d activity SQL comparisons; duplicate events, null dates, filters, paging and native authentication/session ordering.\n",$checks);
