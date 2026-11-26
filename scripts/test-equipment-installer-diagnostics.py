import importlib.util,subprocess,sys,tempfile
from pathlib import Path
script=Path(__file__).with_name('install-client-equipment.py')
spec=importlib.util.spec_from_file_location('equipment_installer',script);module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
with tempfile.TemporaryDirectory(prefix='equipment-diagnostics-')as directory:
 root=Path(directory);log=root/'php.log'
 try:module.command([sys.executable,'-c',"import sys;sys.stderr.write('fixture PHP failure');sys.exit(7)"],log);raise AssertionError('Failure ignored')
 except RuntimeError as error:assert 'fixture PHP failure'in str(error) and 'codigo 7'in str(error) and str(log)in str(error)
 assert log.read_text()=='fixture PHP failure' and log.stat().st_mode&0o777==0o600
 admin=root/'admin';admin.mkdir();entry=admin/'gera_contrato.hhvm';original=b'<?php /* unknown vendor version */';entry.write_bytes(original)
 result=subprocess.run([sys.executable,str(script),str(admin),str(root/'backup'),'--dry-run'],stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
 assert result.returncode==1 and 'Traceback'not in result.stderr and 'preservados'in result.stdout and 'compatibility.log'in result.stdout,(result.stdout,result.stderr)
 assert entry.read_bytes()==original
 print('PASS PHP error retained in private log; readable installer warning; unknown native file preserved')
