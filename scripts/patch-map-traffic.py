#!/usr/bin/env python3
"""Patch the optional installed Mapa de Clientes monitor; never copy provider configuration."""
import pathlib,re,shutil,subprocess,sys
CSS="""/* suite-map-traffic-v1 */
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
def patch(source):
    if 'suite-map-traffic-v1' in source:return source
    for token in ['function trafficPoints(', 'function trafficDots(', 'function renderTraffic(', '/* Desktop traffic monitor: client details beside the chart. */']:
        if token not in source:raise ValueError('Unsupported Mapa de Clientes monitor; preserving installed source.')
    for name,replacement in [('trafficPoints',POINTS),('trafficDots',DOTS),('renderTraffic',RENDER)]:
        source,count=re.subn(r'^[ \t]*function '+name+r'\([^\n]*$',lambda m:replacement,source,flags=re.M)
        if count!=1:raise ValueError('Ambiguous monitor function; preserving source.')
    return source.replace('</style>',CSS+'\n</style>',1)
def main():
    admin=pathlib.Path(sys.argv[1] if len(sys.argv)>1 else '/opt/mk-auth/admin')
    backup=pathlib.Path(sys.argv[2]);target=admin/'addons/mapa-clientes/maps.hhvm'
    if not target.is_file():print('[mapa] Addon ausente; nenhuma alteracao.');return
    source=target.read_bytes().decode('utf-8');updated=patch(source)
    if source==updated:print('[mapa] Grafico ja ajustado.');return
    if shutil.which('php') is None:print('[mapa] PHP indisponivel; patch nao aplicado.');return
    backup.mkdir(parents=True,exist_ok=True);candidate=backup/'maps-traffic-staged.hhvm';candidate.write_bytes(updated.encode('utf-8'))
    subprocess.run(['php','-l',str(candidate)],check=True)
    shutil.copy2(target,backup/'maps-traffic-before.hhvm')
    if target.read_bytes().decode('utf-8')!=source:raise RuntimeError('Installed map changed during patch.')
    staged=target.with_name(target.name+'.suite-traffic-new');shutil.copy2(target,staged);staged.write_bytes(updated.encode('utf-8'));shutil.copystat(target,staged)
    import os
    stat=target.stat();os.chown(staged,stat.st_uid,stat.st_gid);os.replace(staged,target)
    print('[mapa] Altura alinhada, valores Down/Up e preenchimento translucido aplicados.')
if __name__=='__main__':main()
