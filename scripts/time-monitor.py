#!/usr/bin/env python3
"""Read-only clock monitor; repair requires an explicit authenticated queued request."""
import os,json,time,socket,struct,statistics,subprocess,shutil,fcntl,re,tempfile,argparse
from pathlib import Path
STATE=Path('/opt/mk-auth/dados/suite-time-monitor')
SERVERS=('a.ntp.br','b.ntp.br','c.ntp.br')
FALLBACK_CRON=Path('/etc/cron.d/mkauth-time-sync')
FALLBACK_MARKER='# MKA-SUITE-NTPDATE-FALLBACK'
def fallback_client():return shutil.which('ntpdate',path='/usr/sbin:/usr/bin:/sbin:/bin')
def fallback_scheduled():
    try:return not FALLBACK_CRON.is_symlink() and FALLBACK_MARKER in FALLBACK_CRON.read_text() and bool(fallback_client())
    except OSError:return False
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
    if not active and fallback_scheduled():
        try:
            if subprocess.run(['service','cron','status'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,timeout=3).returncode==0:active.append('ntpdate (programado)')
        except (OSError,subprocess.TimeoutExpired):pass
    zonefile=Path('/etc/localtime').resolve()
    timezone=str(zonefile).split('/zoneinfo/')[-1] if '/zoneinfo/' in str(zonefile) else 'Nao identificado'
    import datetime
    linux_offset=int(datetime.datetime.now().astimezone().utcoffset().total_seconds())
    return dict(system_timezone=timezone,system_utc_offset=linux_offset,checked_at=time.time(),offset_seconds=round(offset,2) if offset is not None else None,reference_count=len(agree),services=services,active_services=active,issue='unverified' if offset is None else ('clock_offset' if abs(offset)>600 else ('service_inactive' if not active else 'ok')))

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

def timezone_config_is_correct(state):
    zone,hours=provider_timezone()
    if state.get('system_timezone')!=zone or state.get('system_utc_offset')!=hours*3600:return False
    source=Path('/opt/mk-auth/include/configure.php').read_text()
    found=re.search(r"date_default_timezone_set\([ \t]*['\"]([^'\"]+)['\"][ \t]*\)",source)
    if not found or found.group(1) not in (zone,'Etc/GMT+'+str(-hours)):return False
    phpzone=found.group(1)
    for binary in Path('/opt/mk-auth/hhvm').glob('*/bin/php'):
        result=subprocess.run([str(binary),'-r',"date_default_timezone_set('"+phpzone+"');echo date('Z');"],stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=5,universal_newlines=True)
        if result.returncode or result.stdout.strip()!=str(hours*3600):return False
    return True

def install_fallback_schedule(backup):
    if FALLBACK_CRON.is_symlink():raise RuntimeError('Agendamento simbolico preservado.')
    if FALLBACK_CRON.exists():
        if FALLBACK_MARKER not in FALLBACK_CRON.read_text():raise RuntimeError('Agendamento existente nao reconhecido; preservado.')
        shutil.copy2(str(FALLBACK_CRON),str(Path(backup)/'ntpdate-cron-before'))
    data=FALLBACK_MARKER+'\n*/30 * * * * root /usr/bin/python3 /usr/local/lib/mkauth-suite/time-monitor.py --sync-existing-client >> /var/log/mkauth-time-monitor.log 2>&1\n'
    fd,name=tempfile.mkstemp(prefix='.mka-time-sync-',dir=str(FALLBACK_CRON.parent))
    try:
        with os.fdopen(fd,'w') as f:f.write(data)
        os.chmod(name,0o644);os.replace(name,str(FALLBACK_CRON))
    finally:
        if os.path.exists(name):os.unlink(name)

def repair(state,progress=None):
    progress=progress or (lambda label,percent:None)
    progress("Conferindo localidade e configuracoes",20)
    if state.get('issue')=='ok' and timezone_config_is_correct(state):
        progress('Tudo correto; nenhuma alteracao necessaria',100)
        return dict(no_change=True)
    if len(state['active_services'])>1:raise RuntimeError('Mais de um sincronizador ativo; revise a configuracao antes de corrigir.')
    services=[name for name in state['active_services'] if name in state['services']] or state['services']
    client=fallback_client() if not services else None
    if not services and not client:raise RuntimeError('Nenhum servico ou cliente NTP instalado. Nao e possivel sincronizar automaticamente neste servidor.')
    progress("Criando backup e ajustando fusos Linux/PHP",40)
    timezone_result=configure_timezones()
    progress("Sincronizando o relogio com NTP",65)
    if client:
        command([client,'-u']+list(SERVERS));install_fallback_schedule(timezone_result['timezone_backup'])
        progress('Validando sincronizacao NTP programada',85)
        return dict(timezone_result,sync_mode='ntpdate_programado')
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
    progress('Validando horario e servico NTP',85)
    return timezone_result

def write_status(data):
    stage=STATE/'status.new'
    if stage.is_symlink():raise RuntimeError('Link recusado')
    fd=os.open(str(stage),os.O_WRONLY|os.O_CREAT|os.O_TRUNC|os.O_NOFOLLOW,0o644)
    with os.fdopen(fd,'w') as f:json.dump(data,f)
    os.chmod(str(stage),0o644);os.replace(str(stage),str(STATE/'status.json'))

def main():
    parser=argparse.ArgumentParser();parser.add_argument('--diagnose-only',action='store_true');parser.add_argument('--sync-existing-client',action='store_true');args=parser.parse_args()
    configure_environment()
    if os.geteuid()!=0:raise SystemExit('Execute como root')
    if STATE.is_symlink():raise RuntimeError('Diretorio simbolico recusado')
    with open('/run/mkauth-time-monitor.lock','w') as lock:
        try:fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
        except BlockingIOError:return
        state=diagnose()
        if args.sync_existing_client:
            if fallback_scheduled() and not any(name in state['services'] for name in state['active_services']):command([fallback_client(),'-u']+list(SERVERS))
            return
        request=STATE/'request.json'
        if request.exists() and not request.is_symlink() and not args.diagnose_only:
            try:
                if request.stat().st_size>1024:raise ValueError('Pedido invalido')
                data=json.loads(request.read_text());request.unlink()
                if data.get('action') not in ('repair','check') or not isinstance(data.get('id'),str) or len(data['id'])!=32:raise ValueError('Pedido invalido')
                operation=data['action'];state.update(operation=operation,request_id=data['id'],repair_status='running');write_status(state)
                if operation=='check':
                    state.update(progress_label='Diagnostico concluido',progress_percent=100,repair_status='success',repair_message='Diagnostico atualizado. Nenhuma configuracao foi alterada.')
                else:
                    def update_progress(label,percent):
                        state.update(checked_at=time.time(),progress_label=label,progress_percent=percent);write_status(state)
                    timezone_result=repair(state,update_progress);state=diagnose();state.update(timezone_result);state.update(progress_label='Verificacao concluida',progress_percent=100,request_id=data['id'],repair_status='success' if state['issue']=='ok' else 'pending',repair_message='Ajuste do relogio e dos fusos solicitado. Confira abaixo os offsets Linux/PHP; se o PHP ainda divergir, a verificacao web informara a pendencia.')
            except Exception as e:state.update(progress_label='Correcao interrompida',repair_status='error',repair_message=str(e))
        else:
            try:
                previous=json.loads((STATE/'status.json').read_text())
                for k in ('operation','request_id','repair_status','repair_message','progress_label','progress_percent'):
                    if k in previous:state[k]=previous[k]
                if state.get('repair_status')=='running':state.update(repair_status='error',repair_message='A correcao anterior foi interrompida. Consulte os logs e tente novamente.')
            except (OSError,ValueError):pass
        write_status(state)
if __name__=='__main__':main()
