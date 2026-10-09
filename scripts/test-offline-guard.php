<?php
// Fixture tests only: fake database and temporary state, no NAS or production DB.
if (PHP_SAPI !== 'cli') exit(1);
$source=file_get_contents(__DIR__.'/install-radius-reconcile.sh');
if(!preg_match("/<<'GUARD_PHP'\n(.*?)\nGUARD_PHP/s",str_replace("\r\n","\n",$source),$m))exit(1);
eval(substr($m[1],5));
function log_line($message) {}
class ResultFixture {public $rows;function __construct($rows){$this->rows=$rows;}function fetch_assoc(){return array_shift($this->rows);}}
class StatementFixture {
 public $db,$sql,$affected_rows=0;
 function __construct($db,$sql){$this->db=$db;$this->sql=$sql;}
 function bind_param($types,&...$args){}
 function execute(){if(strpos($this->sql,'UPDATE')===0){$this->db->closed++;$this->affected_rows=1;}}
 function get_result(){return new ResultFixture($this->db->rows);}
 function close(){}
}
class DatabaseFixture {public $rows=array(),$closed=0;function prepare($sql){return new StatementFixture($this,$sql);}}
$dir=sys_get_temp_dir().'/mka-guard-test-'.bin2hex(random_bytes(8));mkdir($dir,0700);
$cfg=array('state_file'=>$dir.'/status.json');
$row=array('radacctid'=>1,'username'=>' Cliente ','acctsessionid'=>'session1','acctstarttime'=>date('Y-m-d H:i:s',time()-3600),'acctupdatetime'=>date('Y-m-d H:i:s',time()-700));
$tests=0;
function check_case($name,$rows,$active,$prior,$expected){
 global $tests;
 $db=new DatabaseFixture();$db->rows=$rows;$stats=array();$router='192.0.2.1';
 file_put_contents(offline_guard_path($router),json_encode($prior));
 offline_guard($db,$router,$active,true,null,$stats);
 if($db->closed!==$expected)throw new RuntimeException($name.' failed');
 $tests++;echo "OK $name\n";
}
$signature=hash('sha256',json_encode(array($row['acctsessionid'],$row['acctstarttime'],$row['acctupdatetime'],$row['username'])));
$prior=array('1'=>array('signature'=>$signature,'first'=>time()-121,'seen'=>time()-121));
$other=array(array('name'=>'other','service'=>'pppoe'));
try{
 check_case('first observation',array($row),$other,array(),0);
 check_case('second observation',array($row),$other,$prior,1);
 check_case('present normalized',array($row),array(array('name'=>'CLIENTE','service'=>'pppoe')),$prior,0);
 check_case('empty snapshot',array($row),array(),$prior,0);
 check_case('malformed snapshot',array($row),array(array('name'=>'other')),$prior,0);
 $recent=$row;$recent['acctupdatetime']=date('Y-m-d H:i:s',time()-60);
 check_case('recent accounting',array($recent),$other,$prior,0);
 $short=$prior;$short[1]['first']=time()-60;
 check_case('less than 120 seconds',array($row),$other,$short,0);
 $changed=$row;$changed['acctsessionid']='different';
 check_case('changed session',array($changed),$other,$prior,0);
 $many=array();for($i=1;$i<=30;$i++){$r=$row;$r['radacctid']=$i;$r['username']='absent'.$i;$many[]=$r;}
 check_case('mass absence', $many,$other,$prior,0);
 echo "$tests guard tests passed\n";
}finally{foreach(glob($dir.'/*') as $file)unlink($file);rmdir($dir);}
