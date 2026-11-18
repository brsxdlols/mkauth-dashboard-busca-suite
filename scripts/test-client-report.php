<?php
require __DIR__.'/../addons/rel_clientes/report_model.php';
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
$p=rc_period(array('ano'=>'2024','mes'=>'02'));check($p['from']==='2024-02-01'&&$p['until']==='2024-03-01','Leap month bounds');
$p=rc_period(array('ano'=>'2026','mes'=>'%'));check($p['from']==='2026-01-01'&&$p['until']==='2027-01-01','Legacy whole-year link');
$p=rc_period(array('ano'=>array('bad'),'mes'=>array('bad')));check(strlen($p['from'])===10,'Invalid input fallback');
check(rc_h("D'Ávila<script>")==="D&#039;Ávila&lt;script&gt;",'HTML escaping');
check(rc_kind('unknown')==='todos','Card allowlist');
class ReportResult{function fetch_all($mode){return array();}}
class ReportStatement{public $values;function bind_param($types,&$value){$this->values=array($types,$value);}function execute(){return true;}function get_result(){return new ReportResult;}function close(){}}
class ReportDb{public $sql;public $statement;function prepare($sql){$this->sql=$sql;return $this->statement=new ReportStatement;}}
$db=new ReportDb;rc_query($db,'SELECT cidade FROM sis_cliente WHERE cidade=?',array("D'Ávila"));check(strpos($db->sql,"D'Ávila")===false&&$db->statement->values[1]==="D'Ávila",'Bind apostrophe as data');
echo "PASS period boundaries, parameter binding, HTML escaping and card validation\n";
