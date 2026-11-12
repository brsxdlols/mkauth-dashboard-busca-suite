#!/usr/bin/env python3
# Run only after database reconciliation and fresh RADIUS session inventory.
import re,subprocess,os,tempfile,json,pathlib,datetime
import argparse,ipaddress
parser=argparse.ArgumentParser(description='Update an existing RADIUS session rate with CoA; never disconnect.')
parser.add_argument('--sessions',required=True,help='JSON map: username -> {session, ip, radius: true}; fresh RouterOS RADIUS inventory only')
parser.add_argument('--nas',required=True)
parser.add_argument('--port',type=int,default=3799)
parser.add_argument('--apply',action='store_true')
args=parser.parse_args()
server=str(ipaddress.IPv4Address(args.nas));assert 1<=args.port<=65535
snap=json.loads(pathlib.Path(args.sessions).read_text())
assert snap and all(x.get('radius') is True for x in snap.values()), 'Only sessions confirmed RADIUS are allowed'
selected=list(snap)
s=open('/etc/freeradius/3.0/mods-enabled/sql').read()
def val(k):return re.search(r'^\s*'+k+r'\s*=\s*"([^"]*)"',s,re.M).group(1)
env=os.environ.copy();env['MYSQL_PWD']=val('password')
def sql(q):
 r=subprocess.run(['mysql','-u',val('login'),'-D',val('radius_db'),'-B','-N','-e',q],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,check=True)
 return [x.split('\t') for x in r.stdout.decode().splitlines()]
secrets=sql("SELECT secret FROM nas WHERE nasname='"+server+"'")
assert len(secrets)==1
records=[]
for u in selected:
 assert re.fullmatch(r'[a-zA-Z0-9@_.-]+',u)
 rows=sql("SELECT a.acctsessionid,a.framedipaddress,g.groupname,r.value FROM radacct a JOIN radusergroup g ON g.username=a.username JOIN radgroupreply r ON r.groupname=g.groupname AND r.attribute='Mikrotik-Rate-Limit' WHERE a.username='"+u+"' AND a.nasipaddress='"+server+"' AND a.acctstoptime IS NULL")
 assert len(rows)==1,(u,'ambiguous accounting or group')
 session,ip,group,rate=rows[0]
 assert session.lower()==snap[u]['session'] and ip==snap[u]['ip'],(u,'session changed')
 assert re.fullmatch(r'[0-9kKMmGg /]+',rate)
 records.append({'username':u,'session':session,'ip':ip,'group':group,'rate':rate,'previous_rate':None})
if not args.apply:
 print(json.dumps({'mode':'dry-run','nas':server,'records':records},indent=2));raise SystemExit
folder=pathlib.Path('/opt/mk-auth/backups/codex-queues')/(datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'-coa')
folder.mkdir(mode=0o700)
path=folder/'sessions-before.json';path.write_text(json.dumps(records,indent=2));os.chmod(str(path),0o600)
# Before correction these sessions had no queue/rate. Reversal for the same
# session: CoA with Mikrotik-Rate-Limit = "0/0" to remove the imposed cap.
# Validate resulting queue state; never send disconnect as fallback.
(folder/'reversal.txt').write_text('For these exact session IDs only: CoA Mikrotik-Rate-Limit="0/0". Verify resulting state. Do not disconnect.\n')
with tempfile.NamedTemporaryFile(mode='w') as f:
 os.chmod(f.name,0o600);f.write(secrets[0][0]);f.flush()
 for r in records:
  request='User-Name = "'+r['username']+'"\nAcct-Session-Id = "'+r['session']+'"\nFramed-IP-Address = '+r['ip']+'\nMikrotik-Rate-Limit = "'+r['rate']+'"\n'
  result=subprocess.run(['radclient','-F','-r','1','-t','3','-S',f.name,server+':'+str(args.port),'coa'],input=request.encode(),stdout=subprocess.PIPE,stderr=subprocess.STDOUT,timeout=8)
  out=result.stdout.decode(errors='replace')
  print(json.dumps({'username':r['username'],'session':r['session'],'code':result.returncode,'reply':out.strip()}),flush=True)
  with (folder/'result.jsonl').open('a') as log:log.write(json.dumps({'username':r['username'],'returncode':result.returncode,'reply':out})+'\n')
  if result.returncode or 'CoA-ACK' not in out: raise RuntimeError('CoA not acknowledged; stopping')
print('CoA backup:',str(folder),flush=True)
