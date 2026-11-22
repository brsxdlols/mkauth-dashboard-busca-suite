#!/usr/bin/env python3
"""Read-only clock monitor; repair requires an explicit authenticated queued request."""
import os,json,time,socket,struct,statistics,subprocess,shutil,fcntl
from pathlib import Path
STATE=Path('/opt/mk-auth/dados/suite-time-monitor')
SERVERS=('a.ntp.br','b.ntp.br','c.ntp.br')
def sample(host):
    with socket.socket(socket.AF_INET,socket.SOCK_DGRAM) as s:
        s.settimeout(2);s.connect((socket.gethostbyname(host),123))
        t1=time.time();stamp=int((t1+2208988800)*2**32)
        packet=bytearray(48);packet[0]=35;packet[40:48]=struct.pack('!Q',stamp)
        s.send(packet);data=s.recv(512);t4=time.time()
        if len(data)<48 or data[0]&7!=4 or data[0]>>6==3 or not 1<=data[1]<=15 or data[24:32]!=packet[40:48]:raise ValueError('Resposta NTP invalida')
        t2=struct.unpack('!Q',data[32:40])[0]/2**32-2208988800
        t3=struct.unpack('!Q',data[40:48])[0]/2**32-2208988800
        return ((t2-t1)+(t3-t4))/2

def diagnose(query=sample):
    samples=[]
    for host in SERVERS:
        try:samples.append(query(host))
        except (OSError,ValueError):pass
    agree=[x for x in samples if sum(abs(x-y)<=5 for y in samples)>=2]
    offset=statistics.median(agree) if len(agree)>=2 else None
    services=[name for name in ('chrony','chronyd','ntpsec','ntp','systemd-timesyncd') if Path('/etc/init.d/'+name).is_file()]
    active=[]
    for name in services:
        try:
            if subprocess.run(['service',name,'status'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,timeout=3).returncode==0:active.append(name)
        except (OSError,subprocess.TimeoutExpired):pass
    zonefile=Path('/etc/localtime').resolve()
    timezone=str(zonefile).split('/zoneinfo/')[-1] if '/zoneinfo/' in str(zonefile) else 'Nao identificado'
    import datetime
    linux_offset=int(datetime.datetime.now().astimezone().utcoffset().total_seconds())
    return dict(system_timezone=timezone,system_utc_offset=linux_offset,checked_at=time.time(),offset_seconds=round(offset,2) if offset is not None else None,reference_count=len(agree),services=services,active_services=active,issue='unverified' if offset is None else ('clock_offset' if abs(offset)>60 else ('service_inactive' if not active else 'ok')))

def command(args):
    p=subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,timeout=50,universal_newlines=True)
    if p.returncode:raise RuntimeError('Falha ao executar '+args[0]+': '+p.stdout[-500:])

def repair(state):
    if len(state['active_services'])>1:raise RuntimeError('Mais de um sincronizador ativo; revise a configuracao antes de corrigir.')
    services=state['active_services'] or state['services']
    if not services:raise RuntimeError('Nenhum sincronizador instalado. Instale/configure o servico NTP do sistema pelo suporte.')
    name=services[0]
    if name in ('chrony','chronyd'):
        command(['service',name,'start']);command(['chronyc','online']);command(['chronyc','burst','4/4']);command(['chronyc','waitsync','10']);command(['chronyc','makestep'])
    elif name in ('ntp','ntpsec'):
        binary=shutil.which('ntpd')
        if not binary:raise RuntimeError('Executavel ntpd ausente.')
        config='/etc/ntpsec/ntp.conf' if name=='ntpsec' else '/etc/ntp.conf'
        if not Path(config).is_file():raise RuntimeError('Configuracao NTP ausente; preservada.')
        command(['service',name,'stop'])
        try:command([binary,'-gq','-c',config])
        finally:command(['service',name,'start'])
    else:command(['service',name,'restart'])
    # Keep the existing service and configuration; never install a competing daemon.
    if shutil.which('update-rc.d'):
        command(['update-rc.d',name,'defaults']);command(['update-rc.d',name,'enable'])

def write_status(data):
    stage=STATE/'status.new'
    if stage.is_symlink():raise RuntimeError('Link recusado')
    fd=os.open(str(stage),os.O_WRONLY|os.O_CREAT|os.O_TRUNC|os.O_NOFOLLOW,0o644)
    with os.fdopen(fd,'w') as f:json.dump(data,f)
    os.chmod(str(stage),0o644);os.replace(str(stage),str(STATE/'status.json'))

def main():
    if os.geteuid()!=0:raise SystemExit('Execute como root')
    if STATE.is_symlink():raise RuntimeError('Diretorio simbolico recusado')
    with open('/run/mkauth-time-monitor.lock','w') as lock:
        try:fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
        except BlockingIOError:return
        state=diagnose();request=STATE/'request.json'
        if request.exists() and not request.is_symlink():
            try:
                if request.stat().st_size>1024:raise ValueError('Pedido invalido')
                data=json.loads(request.read_text());request.unlink()
                if data.get('action')!='repair' or not isinstance(data.get('id'),str) or len(data['id'])!=32:raise ValueError('Pedido invalido')
                state.update(request_id=data['id'],repair_status='running');write_status(state)
                repair(state);state=diagnose();state.update(request_id=data['id'],repair_status='success' if state['issue']=='ok' else 'pending',repair_message='Servico acionado. Confira o diagnostico atualizado; a sincronizacao pode levar alguns minutos.')
            except Exception as e:state.update(repair_status='error',repair_message=str(e))
        else:
            try:
                previous=json.loads((STATE/'status.json').read_text())
                for k in ('request_id','repair_status','repair_message'):
                    if k in previous:state[k]=previous[k]
                if state.get('repair_status')=='running':state.update(repair_status='error',repair_message='A correcao anterior foi interrompida. Consulte os logs e tente novamente.')
            except (OSError,ValueError):pass
        write_status(state)
if __name__=='__main__':main()
