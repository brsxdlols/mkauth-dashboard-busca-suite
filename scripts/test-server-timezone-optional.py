"""Safety regressions: legacy/default/global form saves cannot apply clocks."""
from pathlib import Path
import json,tempfile,sys
root=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(root/'scripts'))
import importlib.util
spec=importlib.util.spec_from_file_location('optional_clock',root/'scripts/server-timezone-worker.py');clock=importlib.util.module_from_spec(spec);spec.loader.exec_module(clock)
with tempfile.TemporaryDirectory(dir=root) as tmp:
 tmp=Path(tmp);assert tmp.resolve().parent==root.resolve();folder=tmp/'scripts';folder.mkdir();(folder/'server_timezones.json').write_bytes((root/'addons/shared/server_timezones.json').read_bytes())
 source=(root/'scripts/server-timezone-worker.py').read_text(encoding='utf-8').replace('/var/lib/mkauth-suite-timezone',str(tmp/'state').replace('\\','/')).replace('/etc/',str(tmp/'etc').replace('\\','/')+'/').replace('/opt/mk-auth/backups/server-timezone',str(tmp/'backup').replace('\\','/'))
 namespace={'__file__':str(folder/'server-timezone-worker.py'),'__name__':'safety_test'};exec(compile(source,'worker-sandbox','exec'),namespace)
 cfg={};calls=[]
 def output(args):
  calls.append(args)
  if args[-1]=='read':return json.dumps({'config':cfg,'provider':{'cidade':'Manaus','estado':'AM'},'mysql':{'global_zone':'-03:00','offset_seconds':-10800}})
  if args[0]=='env':return '-04:00'
  raise AssertionError('Unexpected mutating command: '+repr(args))
 namespace['output']=output;namespace['radius_is_running']=lambda:True
 sys.argv=['worker']
 for candidate in [{'mode':'auto','zone':'America/Manaus','enabled':False},{'mode':'auto','zone':'America/Manaus','enabled':True}]:
  cfg=candidate;calls.clear();namespace['main']();assert not (tmp/'etc').exists();assert all(a[-1]=='read' or a[0]=='env' for a in calls)
 cfg={'mode':'manual','zone':'America/Manaus','enabled':True,'manual_request':True,'revision':'a'*32};namespace['main']();report=json.loads((tmp/'state/status.json').read_text());assert report['status']=='error' and 'bloqueada' in report['message'];assert not (tmp/'etc').exists()
 calls.clear();namespace['main']();assert len(calls)==1,'A failed request must not be retried'
 sys.argv=['worker','--check'];before=(tmp/'state/status.json').read_bytes();namespace['main']();assert (tmp/'state/status.json').read_bytes()==before,'Installer check must retain the processed request'
print('Optional timezone tests passed: default, legacy, active RADIUS refusal, one-shot processing and reinstall preservation.')
