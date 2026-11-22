#!/usr/bin/env python3
"""Read-only clock monitor; repair requires an explicit authenticated queued request."""
import os,json,time,socket,struct,statistics,subprocess,shutil,fcntl,re,tempfile
from pathlib import Path
STATE=Path('/opt/mk-auth/dados/suite-time-monitor')
SERVERS=('a.ntp.br','b.ntp.br','c.ntp.br')
ADMIN_PATH='/usr/sbin:/usr/bin:/sbin:/bin'
def configure_environment():
    # cron normally lacks sbin; missing service is not proof a daemon stopped.
    os.environ['PATH']=ADMIN_PATH
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

def provider_timezone():
    p=subprocess.run(['mysql','-N','-uroot','-pvertrigo','mkradius','-e','SELECT estado,cidade FROM sis_provedor ORDER BY id LIMIT 1'],stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=5,universal_newlines=True)
    if p.returncode:raise RuntimeError('Nao foi possivel consultar a localidade do provedor.')
    fields=p.stdout.strip().split('\t');uf=fields[0].strip().upper();city=fields[1].strip().lower() if len(fields)>1 else ''
    zones={'AC':('America/Rio_Branco',-5),'AL':('America/Maceio',-3),'AP':('America/Belem',-3),'BA':('America/Bahia',-3),'CE':('America/Fortaleza',-3),'DF':('America/Sao_Paulo',-3),'ES':('America/Sao_Paulo',-3),'GO':('America/Sao_Paulo',-3),'MA':('America/Fortaleza',-3),'MT':('America/Cuiaba',-4),'MS':('America/Campo_Grande',-4),'MG':('America/Sao_Paulo',-3),'PA':('America/Belem',-3),'PB':('America/Fortaleza',-3),'PR':('America/Sao_Paulo',-3),'PI':('America/Fortaleza',-3),'RJ':('America/Sao_Paulo',-3),'RN':('America/Fortaleza',-3),'RS':('America/Sao_Paulo',-3),'RO':('America/Porto_Velho',-4),'RR':('America/Boa_Vista',-4),'SC':('America/Sao_Paulo',-3),'SP':('America/Sao_Paulo',-3),'SE':('America/Maceio',-3),'TO':('America/Araguaina',-3)}
    if uf=='PE':return ('America/Noronha',-2) if 'noronha' in city else ('America/Recife',-3)
    if uf not in zones:raise RuntimeError('Localidade ambigua ou nao reconhecida; confirme o municipio antes de ajustar o fuso.')
    return zones[uf]

def patch_timezone(source,zone,ini=False):
    if ini:source=re.sub(r'(?m)^([ \t]*);[ \t]*(date\.timezone[ \t]*=)',r'\1\2',source)
    pattern=r'(?m)^([ \t]*date\.timezone[ \t]*=[ \t]*)[^\r\n]*' if ini else r"date_default_timezone_set\([ \t]*['\"][^'\"]+['\"][ \t]*\)"
    if not re.search(pattern,source):raise RuntimeError('Configuracao de fuso nao reconhecida; arquivo preservado.')
    return re.sub(pattern,(lambda m:m.group(1)+'"'+zone+'"') if ini else (lambda m:"date_default_timezone_set('"+zone+"')"),source)

def apply_timezone_files(changes,zonefile,zone,localtime=Path('/etc/localtime'),timezone=Path('/etc/timezone'),backup_root=Path('/opt/mk-auth/backups')):
    backup=Path(tempfile.mkdtemp(prefix='time-repair-',dir=str(backup_root)));backup.chmod(0o700)
    records={}
    for path in list(changes)+[localtime,timezone]:
        if path.exists():
            info=path.stat();records[path]=(path.read_bytes(),info.st_mode&0o777,info.st_uid,info.st_gid,os.readlink(str(path)) if path.is_symlink() else None)
            shutil.copy2(str(path),str(backup/str(path).strip('/').replace('/','_')))
        else:records[path]=None
    def write(path,data,record):
        fd,name=tempfile.mkstemp(prefix='.suite-time-',dir=str(path.parent))
        try:
            with os.fdopen(fd,'wb') as f:f.write(data)
            if record:os.chmod(name,record[1]);os.chown(name,record[2],record[3])
            else:os.chmod(name,0o644)
            os.replace(name,str(path))
        finally:
            if os.path.exists(name):os.unlink(name)
    try:
        for path,content in changes.items():write(path,content.encode(),records[path])
        # Replacing the link itself must never overwrite the zoneinfo target.
        fd,name=tempfile.mkstemp(prefix='.suite-time-',dir=str(localtime.parent));os.close(fd);os.unlink(name)
        try:os.symlink(str(zonefile),name);os.replace(name,str(localtime))
        finally:
            if os.path.lexists(name):os.unlink(name)
        write(timezone,(zone+'\n').encode(),records[timezone])
    except Exception:
        for path,record in records.items():
            if record is None:
                if path.exists() or path.is_symlink():path.unlink()
            elif record[4]:
                fd,name=tempfile.mkstemp(prefix='.suite-time-',dir=str(path.parent));os.close(fd);os.unlink(name)
                os.symlink(record[4],name);os.replace(name,str(path))
            else:write(path,record[0],record)
        raise
    return backup

def configure_timezones():
    zone,hours=provider_timezone();zonefile=Path('/usr/share/zoneinfo')/zone
    if not zonefile.is_file():raise RuntimeError('Fuso da localidade indisponivel no sistema.')
    configure=Path('/opt/mk-auth/include/configure.php')
    ini=list(Path('/opt/mk-auth/hhvm').glob('*/etc/php.ini'))+list(Path('/opt/mk-auth/hhvm').glob('*/lib/php.ini'))+list(Path('/etc/php').glob('*/*/php.ini'))
    binaries=list(Path('/opt/mk-auth/hhvm').glob('*/bin/php'))
    if configure.is_symlink() or not configure.is_file() or not ini or not binaries:raise RuntimeError('Configuracao nativa PHP nao reconhecida; ajuste automatico cancelado.')
    phpzone=zone
    # Old embedded PHP databases still project abolished Brazilian DST rules.
    # Use a fixed UTC offset only when a native interpreter proves incompatible.
    for binary in binaries:
        result=subprocess.run([str(binary),'-r',"date_default_timezone_set('"+zone+"');echo date('Z');"],stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=5,universal_newlines=True)
        if result.returncode or result.stdout.strip()!=str(hours*3600):phpzone='Etc/GMT+'+str(-hours)
    changes={configure:patch_timezone(configure.read_text(),phpzone)}
    for path in ini:
        if path.is_symlink():raise RuntimeError('Configuracao PHP simbolica preservada.')
        changes[path]=patch_timezone(path.read_text(),phpzone,True)
    backup=apply_timezone_files(changes,zonefile,zone)
    if hasattr(time,'tzset'):time.tzset()
    return dict(timezone_backup=str(backup),configured_system_timezone=zone,configured_php_timezone=phpzone)

def repair(state):
    if len(state['active_services'])>1:raise RuntimeError('Mais de um sincronizador ativo; revise a configuracao antes de corrigir.')
    services=state['active_services'] or state['services']
    if not services:raise RuntimeError('Nenhum sincronizador instalado. Instale/configure o servico NTP do sistema pelo suporte.')
    timezone_result=configure_timezones()
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
    return timezone_result

def write_status(data):
    stage=STATE/'status.new'
    if stage.is_symlink():raise RuntimeError('Link recusado')
    fd=os.open(str(stage),os.O_WRONLY|os.O_CREAT|os.O_TRUNC|os.O_NOFOLLOW,0o644)
    with os.fdopen(fd,'w') as f:json.dump(data,f)
    os.chmod(str(stage),0o644);os.replace(str(stage),str(STATE/'status.json'))

def main():
    configure_environment()
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
                timezone_result=repair(state);state=diagnose();state.update(timezone_result);state.update(request_id=data['id'],repair_status='success' if state['issue']=='ok' else 'pending',repair_message='Ajuste do relogio e dos fusos solicitado. Confira abaixo os offsets Linux/PHP; se o PHP ainda divergir, a verificacao web informara a pendencia.')
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
