import pathlib,tempfile,subprocess,os,json,time,sys
root=pathlib.Path(tempfile.mkdtemp(prefix='mka-time-api-compat-'));(root/'dashboard').mkdir();(root/'shared').mkdir();state=root/'state';state.mkdir()
source=(pathlib.Path(sys.argv[1]) if len(sys.argv)>1 else pathlib.Path(__file__).resolve().parent.parent/'addons/dashboard/time_status.php').read_text().replace('/opt/mk-auth/dados/suite-time-monitor',str(state));(root/'dashboard/time_status.php').write_text(source)
stub=r"""<?php
$_SESSION=array('suite_time_csrf'=>'fixture-token');
$conn=new mysqli('127.0.0.1','root','vertrigo','mkradius');
$conn->query("CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(128) PRIMARY KEY,valor TEXT)");
$conn->query("INSERT INTO sis_opcao VALUES('suite_time_alert_enabled','s')");
$conn->query("CREATE TEMPORARY TABLE sis_provedor(id INT,cidade VARCHAR(128),estado VARCHAR(10))");
$conn->query("INSERT INTO sis_provedor VALUES(1,'IBIASSUCE','BA')");
function permissao($name){return getenv('TEST_ALLOWED')!=='0';}
date_default_timezone_set(getenv('TEST_ZONE')?:'America/Bahia');
register_shutdown_function(function() use($conn){
 $expected=(int)getenv('TEST_CODE');$actual=http_response_code()?:200;
 if($actual!==$expected){fwrite(STDERR,'Unexpected status '.$actual);exit(1);}
 if(getenv('TEST_PREF')!==''){$r=$conn->query("SELECT valor FROM sis_opcao WHERE nome='suite_time_alert_enabled'")->fetch_assoc();if($r['valor']!==getenv('TEST_PREF')){fwrite(STDERR,'Preference not saved');exit(1);}}
});
"""
(root/'shared/notification_bootstrap.php').write_text(stub)
wrapper="<?php $_SERVER['REQUEST_METHOD']=getenv('TEST_METHOD');$_POST=json_decode(getenv('TEST_POST'),true);require __DIR__.'/dashboard/time_status.php';"
(root/'run.php').write_text(wrapper)
base={'checked_at':time.time(),'offset_seconds':0,'services':['ntpsec'],'active_services':['ntpsec'],'issue':'ok','system_timezone':'America/Bahia','system_utc_offset':-10800}
cases=[('read-active','GET',{},200,'1','America/Bahia','',lambda d:d['status']['issue']=='ok' and d['configuration']['expected_utc_offset']==-10800),('save-hidden','POST',{'action':'settings','enabled':'n','csrf':'fixture-token'},200,'1','America/Bahia','n',lambda d:d['saved'] and not d['enabled']),('save-shown','POST',{'action':'settings','enabled':'s','csrf':'fixture-token'},200,'1','America/Bahia','s',lambda d:d['saved'] and d['enabled']),('invalid-csrf','POST',{'action':'settings','enabled':'n','csrf':'wrong'},403,'1','America/Bahia','',lambda d:'error' in d),('no-permission','POST',{'action':'settings','enabled':'n','csrf':'fixture-token'},403,'0','America/Bahia','',lambda d:'error' in d),('detect-php-zone','GET',{},200,'1','Etc/GMT+2','',lambda d:d['status']['issue']=='timezone_mismatch'),('queue-isolated-repair','POST',{'action':'repair','csrf':'fixture-token'},200,'1','America/Bahia','',lambda d:d['queued'] and (state/'request.json').exists())]
for name,method,post,code,allowed,zone,pref,check in cases:
 (state/'status.json').write_text(json.dumps(base))
 if (state/'request.json').exists():(state/'request.json').unlink()
 env=dict(os.environ,TEST_METHOD=method,TEST_POST=json.dumps(post),TEST_CODE=str(code),TEST_ALLOWED=allowed,TEST_ZONE=zone,TEST_PREF=pref)
 p=subprocess.run([os.environ.get('PHP_TEST_BIN','php'),str(root/'run.php')],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True,timeout=10)
 assert p.returncode==0 and not p.stderr,(name,p.stderr,p.stdout)
 assert check(json.loads(p.stdout)),(name,p.stdout)
 print('PASS '+name)
print('Temporary tables only; no real correction queued')
