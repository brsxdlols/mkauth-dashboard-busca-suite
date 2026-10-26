#!/usr/bin/env python3
"""Apply the selected timezone as root. Never changes the UTC clock or past records."""
import argparse,datetime,json,os,re,shutil,subprocess,tempfile,unicodedata
from pathlib import Path

def normalize(value):return ''.join(c for c in unicodedata.normalize('NFKD',str(value)) if not unicodedata.combining(c)).strip().lower()
def resolve(provider,catalog):
 state=normalize(provider.get('estado','')).upper();city=normalize(provider.get('cidade',''))
 names={'SAO PAULO':'SP','ALAGOAS':'AL','AMAZONAS':'AM','GOIAS':'GO','RONDONIA':'RO','ACRE':'AC','SERGIPE':'SE','MATO GROSSO':'MT','MATO GROSSO DO SUL':'MS','RORAIMA':'RR','PERNAMBUCO':'PE','BAHIA':'BA','CEARA':'CE','PARA':'PA','AMAPA':'AP','TOCANTINS':'TO','DISTRITO FEDERAL':'DF','MINAS GERAIS':'MG','ESPIRITO SANTO':'ES','RIO DE JANEIRO':'RJ','PARANA':'PR','SANTA CATARINA':'SC','RIO GRANDE DO SUL':'RS','MARANHAO':'MA','PIAUI':'PI','PARAIBA':'PB','RIO GRANDE DO NORTE':'RN'}
 state=names.get(state,state)
 if state=='AM' and not city:return None
 if state=='AM' and city in catalog['amazonas_west']:return 'America/Eirunepe'
 if state=='PE' and city=='fernando de noronha':return 'America/Noronha'
 return catalog['states'].get(state)
def ensure_safe_transition(current_seconds,target_offset,radius_running):
 hours,minutes=map(int,target_offset[1:].split(':'));target_seconds=-(hours*3600+minutes*60)
 if int(current_seconds)!=target_seconds and radius_running:
  raise ValueError('Alteração de UTC bloqueada para proteger os clientes online. O RADIUS está ativo; a troca exige migração controlada das conexões de accounting. Nenhum horário foi alterado.')

def radius_is_running():
 for p in Path('/proc').iterdir():
  if not p.name.isdigit():continue
  try:
   if (p/'comm').read_text().strip() in ('freeradius','radiusd'):return True
  except (FileNotFoundError,ProcessLookupError,PermissionError):continue
 return False

def output(args):return subprocess.check_output(args,stderr=subprocess.STDOUT).decode().strip()
def atomic(path,data):
 path.parent.mkdir(parents=True,exist_ok=True);fd,tmp=tempfile.mkstemp(prefix='.mka-time-',dir=str(path.parent));os.write(fd,data);os.close(fd);os.chmod(tmp,0o644);os.replace(tmp,path)
def main():
 import fcntl
 ap=argparse.ArgumentParser();ap.add_argument('--check',action='store_true');ap.add_argument('--backup',type=Path,default=Path('/opt/mk-auth/backups/server-timezone'));a=ap.parse_args()
 folder=Path(__file__).resolve().parent;catalog=json.loads((folder/'server_timezones.json').read_text());state=Path('/var/lib/mkauth-suite-timezone');state.mkdir(parents=True,exist_ok=True);os.chmod(state,0o755)
 with open(state/'worker.lock','w') as lock:
  fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
  data=json.loads(output(['php',str(folder/'server-timezone-db.php'),'read']));cfg=data['config'];detected=resolve(data['provider'],catalog);zone=detected if cfg.get('mode')=='auto' else cfg.get('zone');report={'checked_at':datetime.datetime.now(datetime.timezone.utc).isoformat(),'detected_zone':detected,'target_zone':zone,'provider':data['provider'],'config':cfg,'mysql':data['mysql'],'status':'waiting_save'}
  previous=json.loads((state/'status.json').read_text()) if (state/'status.json').exists() else {}
  if not a.check and cfg.get('revision') and previous.get('config',{}).get('revision')==cfg['revision'] and previous.get('status') in ('applied','error'):
   print(json.dumps(previous,ensure_ascii=False));return
  try:
   if zone not in catalog['zones']:raise ValueError('Estado/cidade insuficiente: confira o cadastro ou escolha o fuso manualmente.')
   zonefile=Path('/usr/share/zoneinfo')/zone
   if not zonefile.is_file():raise ValueError('Fuso não disponível no servidor.')
   report['target_offset']=output(['env','TZ='+zone,'date','+%:z'])
   if cfg.get('enabled') and cfg.get('manual_request') is True and cfg.get('revision') and not a.check:
    conf=Path('/etc/mysql/conf.d/99-mkauth-suite-timezone.cnf');current=Path('/etc/localtime');tz=Path('/etc/timezone');expected=('# MK-AUTH suite timezone\n[mysqld]\ndefault-time-zone = '+report['target_offset']+'\n').encode()
    if conf.exists() and not conf.read_bytes().startswith(b'# MK-AUTH suite timezone\n'):raise ValueError('Arquivo de fuso do MySQL pertence a outra configuração; revisão necessária.')
    changed=(not current.exists() or current.resolve()!=zonefile.resolve() or not conf.exists() or conf.read_bytes()!=expected or data['mysql']['global_zone']!=report['target_offset'])
    if changed:
     radius_running=radius_is_running()
     ensure_safe_transition(data['mysql']['offset_seconds'],report['target_offset'],radius_running)
     backup=a.backup/datetime.datetime.now().strftime('%Y%m%d_%H%M%S_%f');backup.mkdir(parents=True,mode=0o700)
     for name,p in [('localtime',current),('timezone',tz),('mysql.cnf',conf)]:
      if p.exists():shutil.copy2(p,backup/name)
     (backup/'state.json').write_text(json.dumps({'mysql_zone':data['mysql']['global_zone'],'localtime_link':os.readlink(current) if current.is_symlink() else None,'mysql_config_existed':conf.exists(),'target_zone':zone},indent=2));report['backup']=str(backup)
     try:
      offset=report['target_offset'];output(['php',str(folder/'server-timezone-db.php'),'set',offset])
      link=current.parent/('.mka-localtime-'+str(os.getpid()));os.symlink(str(zonefile),str(link));os.replace(str(link),str(current));atomic(tz,(zone+'\n').encode());atomic(conf,expected)
     except Exception:
      output(['php',str(folder/'server-timezone-db.php'),'restore',data['mysql']['global_zone']])
      old=json.loads((backup/'state.json').read_text())
      if old['localtime_link']:
       link=current.parent/('.mka-localtime-rollback-'+str(os.getpid()));os.symlink(old['localtime_link'],str(link));os.replace(str(link),str(current))
      else:shutil.copy2(backup/'localtime',current)
      for name,p in [('timezone',tz),('mysql.cnf',conf)]:
       if (backup/name).exists():shutil.copy2(backup/name,p)
       elif p.exists():p.unlink()
      raise
    report['status']='applied';report['mysql']=json.loads(output(['php',str(folder/'server-timezone-db.php'),'read']))['mysql']
   elif a.check:report['status']='checked'
  except Exception as error:report['status']='error';report['message']=str(error)
  if not a.check:atomic(state/'status.json',json.dumps(report,ensure_ascii=False,indent=2).encode())
  print(json.dumps(report,ensure_ascii=False))
if __name__=='__main__':main()
