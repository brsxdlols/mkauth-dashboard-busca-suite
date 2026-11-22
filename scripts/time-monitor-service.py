#!/usr/bin/env python3
"""Root queue watcher: immediate operations, periodic read-only diagnostics."""
import fcntl,os,time,subprocess,json,tempfile
from pathlib import Path
WORKER='/usr/local/lib/mkauth-suite/time-monitor.py'
QUEUE=Path('/opt/mk-auth/dados/suite-time-monitor/request.json')
def failure(task,message):
 if not isinstance(task,dict) or len(str(task.get('id','')))!=32:return
 status=QUEUE.parent/'status.json'
 try:data=json.loads(status.read_text())
 except (OSError,ValueError):data={'issue':'unverified','active_services':[],'services':[],'offset_seconds':None}
 data.update(checked_at=time.time(),request_id=task['id'],operation=task.get('action'),repair_status='error',repair_message=message,progress_label='Operacao interrompida')
 fd,name=tempfile.mkstemp(prefix='.status-error-',dir=str(QUEUE.parent))
 with os.fdopen(fd,'w') as f:json.dump(data,f)
 os.chmod(name,0o644);os.replace(name,str(status))
 try:
  if json.loads(QUEUE.read_text()).get('id')==task['id']:QUEUE.unlink()
 except (OSError,ValueError):pass

def main():
 if os.geteuid()!=0:raise SystemExit('Execute como root')
 with open('/run/mkauth-time-monitor.pid','a+') as pid:
  try:fcntl.flock(pid,fcntl.LOCK_EX|fcntl.LOCK_NB)
  except BlockingIOError:return
  pid.seek(0);pid.truncate();pid.write(str(os.getpid()));pid.flush()
  last=0
  while True:
   if QUEUE.exists() or time.monotonic()-last>=60:
    task=None
    if QUEUE.exists():
     try:
      if QUEUE.stat().st_size<=1024:task=json.loads(QUEUE.read_text())
     except (OSError,ValueError):pass
    try:
     command=['/usr/bin/python3',WORKER]
     if not QUEUE.exists():command.append('--diagnose-only')
     result=subprocess.run(command,timeout=240)
     if result.returncode:failure(task,'A rotina administrativa falhou. Consulte o log do monitor de horario.')
    except (OSError,subprocess.TimeoutExpired) as e:
     failure(task,'Falha ao concluir a rotina administrativa: '+str(e));print('Monitor de horario: '+str(e),flush=True)
    last=time.monotonic()
   time.sleep(1)
if __name__=='__main__':main()
