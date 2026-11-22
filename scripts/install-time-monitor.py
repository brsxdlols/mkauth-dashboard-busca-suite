#!/usr/bin/env python3
import os,sys,re,shutil,subprocess,grp
from pathlib import Path
START=b'/* MKA-SUITE-TIME-START */'
END=b'/* MKA-SUITE-TIME-END */'
BLOCK=START+b"\n;(function(){if(window.mkaTimeLoader)return;window.mkaTimeLoader=true;var s=document.createElement('script');s.src='/admin/addons/shared/server_clock.js?v=20261122-8';s.defer=true;document.head.appendChild(s);})();\n"+END+b'\n'
def patch(source):
    source=re.sub(re.escape(START)+b'.*?'+re.escape(END)+b'\r?\n?',b'',source,flags=re.S)
    return source+(b'\n' if source and not source.endswith(b'\n') else b'')+BLOCK

def main():
    admin,backup=Path(sys.argv[1]),Path(sys.argv[2])
    if str(admin)!='/opt/mk-auth/admin':print('[horario] Destino alternativo: servico nao instalado.');return
    if os.geteuid()!=0:raise SystemExit('Execute como root')
    backup.mkdir(parents=True,exist_ok=True)
    for rel in ('addons/addon.js','scripts/mk-auth.js'):
        p=admin/rel
        if p.is_symlink():raise RuntimeError('Link recusado: '+rel)
        if not p.exists() and rel.startswith('scripts/'):continue
        source=p.read_bytes() if p.exists() else b''
        if p.exists():shutil.copy2(str(p),str(backup/rel.replace('/','_')))
        p.parent.mkdir(parents=True,exist_ok=True);p.write_bytes(patch(source))
    state=Path('/opt/mk-auth/dados/suite-time-monitor')
    if state.is_symlink():raise RuntimeError('Diretorio simbolico recusado')
    state.mkdir(mode=0o2770,exist_ok=True);os.chown(str(state),0,grp.getgrnam('www-data').gr_gid);state.chmod(0o2770)
    files={'/usr/local/lib/mkauth-suite/time-monitor.py':Path(__file__).with_name('time-monitor.py').read_bytes(),'/etc/cron.d/mkauth-time-monitor':b'* * * * * root /usr/bin/python3 /usr/local/lib/mkauth-suite/time-monitor.py >> /var/log/mkauth-time-monitor.log 2>&1\n','/etc/logrotate.d/mkauth-time-monitor':b'/var/log/mkauth-time-monitor.log {\n daily\n rotate 7\n compress\n missingok\n notifempty\n copytruncate\n}\n'}
    for name,data in files.items():
        p=Path(name)
        if p.is_symlink():raise RuntimeError('Link recusado: '+name)
        if p.exists():shutil.copy2(str(p),str(backup/p.name))
        p.parent.mkdir(parents=True,exist_ok=True);p.write_bytes(data);p.chmod(0o700 if name.endswith('.py') else 0o644)
    try:subprocess.run([sys.executable,'/usr/local/lib/mkauth-suite/time-monitor.py'],check=True,timeout=35)
    except (subprocess.SubprocessError,OSError):print('[aviso] Monitor instalado; primeira consulta indisponivel. A rotina diaria de monitoramento tentara novamente.')
    print('[horario] Diagnostico de horario e relogio instalados; nenhuma correcao de hora foi executada.')
if __name__=='__main__':main()
