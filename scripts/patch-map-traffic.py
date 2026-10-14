#!/usr/bin/env python3
"""Patch the optional installed Mapa de Clientes monitor; never copy provider configuration."""
import pathlib,re,shutil,subprocess,sys
CSS="""/* suite-map-traffic-v2 */
@media(min-width:821px){
body.monitor-client>.leaflet-popup .leaflet-popup-content{height:auto!important;max-height:calc(100dvh - 48px)!important;overflow-y:auto!important}
body.monitor-client>.leaflet-popup .popup{height:auto!important;grid-template-rows:auto!important;align-items:stretch!important}
body.monitor-client>.leaflet-popup .popup-grid{max-height:none!important;overflow-y:visible!important}
body.monitor-client>.leaflet-popup .traffic-mini{height:auto!important;align-self:stretch!important;display:flex!important;flex-direction:column!important;margin-top:0!important}
body.monitor-client>.leaflet-popup .traffic-mini-chart{flex:1 1 0!important;min-height:150px!important}
}
.traffic-mini-point-value{font:600 12px Arial,sans-serif;paint-order:stroke;stroke:#f8fbfd;stroke-width:3px;stroke-linejoin:round}
"""
POINTS="""  function trafficGeometry(box){var svg=box.querySelector('.traffic-mini-chart'),w=Math.max(320,svg.clientWidth||400),h=Math.max(92,svg.clientHeight||92);return{w:w,h:h,left:74,right:90,top:25,bottom:24};}
  function trafficPoints(values,max,g){g=g||{w:400,h:92,left:34,right:0,top:8,bottom:8};return values.map(function(v,i){var x=values.length<2?g.w-g.right:g.left+i*(g.w-g.left-g.right)/(values.length-1),y=g.h-g.bottom-(Number(v||0)/max*(g.h-g.top-g.bottom));return x.toFixed(1)+','+y.toFixed(1);}).join(' ');}"""
DOTS="""  function trafficDots(box,values,max,kind,g){var svg=box.querySelector('.traffic-mini-chart'),ns='http://www.w3.org/2000/svg',group=svg.querySelector('.traffic-mini-dots-'+kind);if(!group){group=document.createElementNS(ns,'g');group.setAttribute('class','traffic-mini-dots-'+kind);svg.appendChild(group);}while(group.firstChild)group.removeChild(group.firstChild);values.forEach(function(v,i){var x=values.length<2?g.w-g.right:g.left+(g.w-g.left-g.right)*i/(values.length-1),y=g.h-g.bottom-(Math.min(v,max)/max*(g.h-g.top-g.bottom)),dot=document.createElementNS(ns,'circle'),tip=document.createElementNS(ns,'title');dot.setAttribute('cx',x);dot.setAttribute('cy',y);dot.setAttribute('r',i===values.length-1?'4':'2.5');dot.setAttribute('class','traffic-mini-dot-'+kind+(i===values.length-1?' traffic-mini-dot-last':''));tip.textContent='Coleta '+trafficSeries.times[i]+' · Download '+trafficSeries.rx[i].toFixed(2)+' Mbps · Upload '+trafficSeries.tx[i].toFixed(2)+' Mbps';dot.appendChild(tip);group.appendChild(dot);if(i===values.length-1){var label=document.createElementNS(ns,'text'),other=kind==='rx'?trafficSeries.tx[i]:trafficSeries.rx[i],otherY=g.h-g.bottom-other/max*(g.h-g.top-g.bottom),offset=Math.abs(y-otherY)<22?(kind==='rx'?-12:16):4;label.setAttribute('class','traffic-mini-point-value');label.setAttribute('x',x+9);label.setAttribute('y',Math.max(16,Math.min(g.h-5,y+offset)));label.setAttribute('fill',kind==='rx'?'#3182ce':'#e45756');label.textContent=(kind==='rx'?'↓ ':'↑ ')+Number(v).toFixed(2)+' Mbps';group.appendChild(label);}});}"""
RENDER="""  function renderTraffic(box,data){trafficSeries.rx.push(Math.max(0,Number(data.rx_mbps)||0));trafficSeries.tx.push(Math.max(0,Number(data.tx_mbps)||0));trafficSeries.times.push(new Date().toLocaleTimeString('pt-BR'));if(trafficSeries.rx.length>24){trafficSeries.rx.shift();trafficSeries.tx.shift();trafficSeries.times.shift();}var observed=Math.max.apply(null,trafficSeries.rx.concat(trafficSeries.tx)),max=Math.max(1,Number(trafficScale(observed))),g=trafficGeometry(box),svg=box.querySelector('.traffic-mini-chart');svg.setAttribute('viewBox','0 0 '+g.w+' '+g.h);svg.setAttribute('preserveAspectRatio','xMidYMid meet');var grids=svg.querySelectorAll('.traffic-mini-grid'),scales=svg.querySelectorAll('.traffic-mini-scale');[g.top,g.top+(g.h-g.top-g.bottom)/2,g.h-g.bottom].forEach(function(y,i){if(grids[i]){grids[i].setAttribute('x1',g.left);grids[i].setAttribute('x2',g.w-g.right);grids[i].setAttribute('y1',y);grids[i].setAttribute('y2',y);}if(scales[i]){scales[i].setAttribute('x',4);scales[i].setAttribute('y',y+4);scales[i].style.fontSize='12px';}});['rx','tx'].forEach(function(kind){var points=trafficPoints(trafficSeries[kind],max,g),line=box.querySelector('.traffic-mini-'+kind),area=svg.querySelector('.traffic-mini-area-'+kind);if(!area){area=document.createElementNS('http://www.w3.org/2000/svg','polygon');area.setAttribute('class','traffic-mini-area-'+kind);area.setAttribute('fill',kind==='rx'?'#3182ce':'#e45756');area.setAttribute('fill-opacity','0.16');area.setAttribute('stroke','none');svg.insertBefore(area,svg.querySelector('.traffic-mini-rx'));}var firstX=trafficSeries[kind].length<2?g.w-g.right:g.left;area.setAttribute('points',firstX+','+(g.h-g.bottom)+' '+points+' '+(g.w-g.right)+','+(g.h-g.bottom));line.setAttribute('points',points);trafficDots(box,trafficSeries[kind],max,kind,g);});box.querySelector('.traffic-scale-max').textContent=trafficScale(max)+' Mbps';box.querySelector('.traffic-scale-mid').textContent=trafficScale(max/2);box.querySelector('.traffic-rx').textContent=trafficSeries.rx[trafficSeries.rx.length-1].toFixed(2);box.querySelector('.traffic-tx').textContent=trafficSeries.tx[trafficSeries.tx.length-1].toFixed(2);box.querySelector('.traffic-mini-status').textContent=(data.ping_ms!=null?data.ping_ms+' ms · ':'')+(data.uptime||'online');}"""
def patch_v2(source):
    if 'suite-map-traffic-v2' in source:return source
    for token in ['function trafficPoints(', 'function trafficDots(', 'function renderTraffic(', '/* Desktop traffic monitor: client details beside the chart. */']:
        if token not in source:raise ValueError('Unsupported Mapa de Clientes monitor; preserving installed source.')
    source=re.sub(r'^[ \t]*function trafficGeometry\([^\n]*$', '', source, flags=re.M)
    for name,replacement in [('trafficPoints',POINTS),('trafficDots',DOTS),('renderTraffic',RENDER)]:
        source,count=re.subn(r'^[ \t]*function '+name+r'\([^\n]*$',lambda m:replacement,source,flags=re.M)
        if count!=1:raise ValueError('Ambiguous monitor function; preserving source.')
    source=source.replace("new URLSearchParams(location.search).get('monitor')!=='1'", "!(new URLSearchParams(location.search).get('monitor')==='1'||/\\/monitor\\/?$/.test(location.pathname))")
    source=source.replace("var c=state.clients.find(function(client){return client.status==='online';});", "var requestedLogin=new URLSearchParams(location.search).get('login');var c=state.clients.find(function(client){return client.status==='online'&&(!requestedLogin||client.login===requestedLogin);});")
    source=source.replace("setMonitorMode(!monitorMode);", "if(!monitorMode){location.href='monitor/?login='+encodeURIComponent(activePopupLogin||trafficLogin||'');}else setMonitorMode(false);")
    return source.replace('</style>',CSS+'\n</style>',1)
def patch_v3(source):
    source=patch_v2(source)
    if 'suite-map-monitor-v3' in source:return source
    for token in ['function setupMonitorSearch(){','function setMonitorMode(on){','function renderTraffic(box,data){','</head>']:
        if source.count(token)!=1:raise ValueError('Monitor integration point unavailable; preserving installed source.')
    source=source.replace('function setupMonitorSearch(){','function setupMonitorSearch(){if(window.mkaMapMonitor)window.mkaMapMonitor.sync();',1)
    source=source.replace('function setMonitorMode(on){','function setMonitorMode(on){if(!on&&window.mkaMapMonitor)window.mkaMapMonitor.stop();',1)
    source=source.replace('function renderTraffic(box,data){','function renderTraffic(box,data){if(window.mkaMapMonitor)window.mkaMapMonitor.sync(box);',1)
    assets='<!-- suite-map-monitor-v3 -->\n<link rel="stylesheet" href="/admin/addons/shared/map_monitor.css?v=20261014">\n<script defer src="/admin/addons/shared/map_monitor.js?v=20261014"></script>\n'
    return source.replace('</head>',assets+'</head>',1)

PAUSE="""  /* suite-map-monitor-v4 */
  var trafficPaused=false,trafficEpoch=0;
  window.mkaMapTrafficControl={pause:function(on){trafficPaused=!!on;trafficEpoch++;stopTraffic(false);if(!trafficPaused&&trafficLogin)pollTraffic(trafficLogin);return trafficPaused;}};
  function pollTraffic(login){if(trafficPaused||login!==trafficLogin)return;var box=document.querySelector('.traffic-mini[data-traffic-login="'+CSS.escape(login)+'"]');if(!box)return stopTraffic();var epoch=trafficEpoch,request=new AbortController();trafficRequest=request;fetch('traffic_api.hhvm?login='+encodeURIComponent(login),{credentials:'same-origin',cache:'no-store',signal:request.signal}).then(function(r){return r.json().then(function(data){if(!r.ok||!data.ok)throw new Error(data.error||'HTTP '+r.status);return data;});}).then(function(data){if(!trafficPaused&&epoch===trafficEpoch&&login===trafficLogin)renderTraffic(box,data);}).catch(function(e){if(e.name!=='AbortError'&&!trafficPaused&&epoch===trafficEpoch){var status=box.querySelector('.traffic-mini-status');if(status)status.textContent=e.message;}}).finally(function(){if(epoch!==trafficEpoch)return;if(trafficRequest===request)trafficRequest=null;if(!trafficPaused&&login===trafficLogin&&box.isConnected)trafficTimer=setTimeout(function(){pollTraffic(login);},3000);});}
  function startTraffic(login){trafficEpoch++;trafficPaused=false;stopTraffic(trafficLogin!==login);trafficLogin=login;trafficTimer=setTimeout(function(){pollTraffic(login);},80);}"""

def patch_v4(source):
    source=patch_v3(source)
    if 'suite-map-monitor-v4' in source:return source
    for name in ['pollTraffic','startTraffic','trafficDots']:
        pattern=r'^  function '+name+r'\([^\n]*'
        if len(re.findall(pattern,source,re.M))!=1:raise ValueError('Traffic integration unavailable: '+name)
    dots=DOTS.replace("if(i===values.length-1){var label=", "{var label=")
    dots=dots.replace("label.setAttribute('x',x+9);", "label.setAttribute('text-anchor',i===values.length-1?'start':'middle');label.setAttribute('x',i===values.length-1?x+9:x);")
    dots=dots.replace("label.textContent=(kind==='rx'?'↓ ':'↑ ')+Number(v).toFixed(2)+' Mbps';", "label.textContent=(kind==='rx'?'↓ ':'↑ ')+Number(v).toFixed(2)+(i===values.length-1?' Mbps':'');")
    source=re.sub(r'^  function trafficDots\([^\n]*',lambda m:dots,source,count=1,flags=re.M)
    source=re.sub(r'^  function startTraffic\([^\n]*','',source,count=1,flags=re.M)
    source=re.sub(r'^  function pollTraffic\([^\n]*',lambda m:PAUSE,source,count=1,flags=re.M)
    return source.replace('map_monitor.css?v=20261014','map_monitor.css?v=20261014-4').replace('map_monitor.js?v=20261014','map_monitor.js?v=20261014-4')

def patch(source):
    source=patch_v4(source)
    return re.sub(r'(map_monitor\.(?:css|js)\?v=)[^\"<\s]+',r'\g<1>20261014-7',source)

def install_monitor_route(admin,backup):
    template=pathlib.Path(__file__).with_name('map-monitor-entry.php')
    if not template.is_file():raise RuntimeError('Monitor entry template unavailable.')
    target=admin/'addons/mapa-clientes/monitor/index.php'
    if target.is_file() and 'suite-map-monitor-entry-v1' not in target.read_text(encoding='utf-8'):
        raise RuntimeError('Existing monitor route belongs to another version; preserving it.')
    backup.mkdir(parents=True,exist_ok=True)
    staged=backup/'monitor-entry-staged.php';shutil.copy2(template,staged)
    subprocess.run(['php','-l',str(staged)],check=True)
    if target.is_file():shutil.copy2(target,backup/'monitor-entry-before.php')
    target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(staged,target);target.chmod(0o644)
    print('[mapa] Rota /admin/addons/mapa-clientes/monitor/ instalada.')
def migrate_monitor_menu(admin,backup):
    menu=admin/'addons/mapa-clientes/menu.js'
    if not menu.is_file():return
    old=menu.read_bytes();new=re.sub(rb'mapa-clientes/\?monitor=1(&)?',lambda m:b'mapa-clientes/monitor/'+(b'?' if m[1] else b''),old)
    if old==new:return
    shutil.copy2(menu,backup/'map-menu-before.js')
    staged=menu.with_name(menu.name+'.suite-monitor-new');shutil.copy2(menu,staged);staged.write_bytes(new);shutil.copystat(menu,staged)
    import os
    stat=menu.stat();os.chown(staged,stat.st_uid,stat.st_gid);os.replace(staged,menu)
    print('[mapa] Menu aponta para /monitor/.')
def migrate_monitor_links(backup):
    # Only change the monitoring URL; keep all other configured shortcuts intact.
    if shutil.which('mysql') is None:return
    import os,json
    env=dict(os.environ);env['MYSQL_PWD']=env.get('MYSQL_PASS','vertrigo')
    db=env.get('MYSQL_DB','mkradius')
    if not re.fullmatch('[A-Za-z0-9_]+',db):raise ValueError('Invalid database name.')
    args=['mysql','-h',env.get('MYSQL_HOST','127.0.0.1'),'-u',env.get('MYSQL_USER','root'),'-N','-B',db]
    try:
        rows=subprocess.run(args+['-e',"SELECT id,HEX(link) FROM dashboard_am_sis_cfg"],env=env,check=True,capture_output=True,text=True).stdout.splitlines()
        changes=[]
        for row in rows:
            key,hexlink=row.split('\t',1)
            if not key.isdigit():continue
            old=bytes.fromhex(hexlink) if hexlink!='NULL' else b''
            new=re.sub(rb'mapa-clientes/\?monitor=1(&)?',lambda m:b'mapa-clientes/monitor/'+(b'?' if m[1] else b''),old)
            if new!=old:changes.append((key,hexlink,new.hex()))
        if changes:
            report=backup/'monitor-shortcut-before.json';report.write_text(json.dumps(changes),encoding='ascii');report.chmod(0o600)
            for key,old,new in changes:
                sql="UPDATE dashboard_am_sis_cfg SET link=0x"+new+" WHERE id="+key+" AND HEX(link)='"+old+"'"
                subprocess.run(args+['-e',sql],env=env,check=True,capture_output=True)
        print('[mapa] Atalhos de monitoramento migrados:',len(changes))
    except subprocess.CalledProcessError:print('[aviso] Atalhos do banco nao migrados; URL antiga permanece compativel.')
def main():
    admin=pathlib.Path(sys.argv[1] if len(sys.argv)>1 else '/opt/mk-auth/admin')
    backup=pathlib.Path(sys.argv[2]);target=admin/'addons/mapa-clientes/maps.hhvm'
    if not target.is_file():print('[mapa] Addon ausente; nenhuma alteracao.');return
    source=target.read_bytes().decode('utf-8');updated=patch(source)
    if shutil.which('php') is None:print('[mapa] PHP indisponivel; patch nao aplicado.');return
    backup.mkdir(parents=True,exist_ok=True)
    if source!=updated:
        candidate=backup/'maps-traffic-staged.hhvm';candidate.write_bytes(updated.encode('utf-8'))
        subprocess.run(['php','-l',str(candidate)],check=True)
        shutil.copy2(target,backup/'maps-traffic-before.hhvm')
        if target.read_bytes().decode('utf-8')!=source:raise RuntimeError('Installed map changed during patch.')
        staged=target.with_name(target.name+'.suite-traffic-new');shutil.copy2(target,staged);staged.write_bytes(updated.encode('utf-8'));shutil.copystat(target,staged)
        import os
        # Keep ownership/mode, but give changed PHP a fresh timestamp so OPcache
        # detects the update instead of serving the previous monitor indefinitely.
        stat=target.stat();os.chown(staged,stat.st_uid,stat.st_gid);os.utime(staged,None);os.replace(staged,target)
        print('[mapa] Grafico alinhado, legenda Down/Up, controle dos valores e Log RADIUS por login aplicados.')
    else:print('[mapa] Grafico ja ajustado.')
    install_monitor_route(admin,backup);migrate_monitor_menu(admin,backup);migrate_monitor_links(backup)
if __name__=='__main__':main()
