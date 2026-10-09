<?php
if(PHP_SAPI!=='cli')exit(1);
$source=file_get_contents(__DIR__.'/../addons/busca_inteligente/index.php');
$start=strpos($source,"        \$disabledSearchCondition = '';");
$end=$start===false?false:strpos($source,'        $query_ok =',$start);
if($start===false||$end===false)throw new RuntimeException('Disabled search branch not found');
$code=substr($source,$start,$end-$start);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$link=new mysqli('127.0.0.1','root',getenv('MYSQL_PASS')?:'vertrigo','mkradius');
$link->query('CREATE TEMPORARY TABLE fixture_disabled_clients(nome VARCHAR(100),login VARCHAR(100),cli_ativado CHAR(1))');
$link->query("INSERT INTO fixture_disabled_clients VALUES ('Ana Silva','ana.old','n'),('Ana Ativa','ana.new','s'),('Percent 100%','percent','n'),('Under_score','under','n')");
foreach(array(''=>3,'Ana'=>1,'ana.old'=>1,'ana.new'=>0,'100%'=>1,'Under_'=>1,"' OR 1=1 --"=>0,'missing'=>0) as $term=>$expected){
 $disabledSearchTerm=$term;eval($code);
 $result=$link->query("SELECT * FROM fixture_disabled_clients c WHERE c.cli_ativado='n' $disabledSearchCondition");
 if($result->num_rows!==$expected)throw new RuntimeException('Search count mismatch');
}
echo "PASS disabled-only name/login search, empty search, literal wildcards and SQL quoting\n";
$link->close();
