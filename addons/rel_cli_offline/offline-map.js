(function () {
  'use strict';
  var points = JSON.parse(document.getElementById('off-map-data').textContent);
  var savedLayer = 'Satélite';
  try { savedLayer = localStorage.getItem('mk-auth.offline.map-layer') || savedLayer; } catch(e) {}
  var map = L.map('offline-map', {zoomControl:false});
  var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'});
  var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom: 19, attribution: 'Tiles &copy; Esri — Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community'});
  (savedLayer === 'Mapa' ? street : satellite).addTo(map);
  map.on('baselayerchange',function(event){try{localStorage.setItem('mk-auth.offline.map-layer',event.name);}catch(e){}});
  L.control.zoom({position:'bottomright'}).addTo(map);
  L.control.layers({'Mapa': street, 'Satélite': satellite}, null, {position: 'topleft', collapsed: false}).addTo(map);
  function duration(seconds) {
    seconds = Math.max(0, Number(seconds) || 0);
    return Math.floor(seconds / 86400) + ' dia(s), ' + Math.floor(seconds % 86400 / 3600) + ' hora(s) e ' + Math.floor(seconds % 3600 / 60) + ' minuto(s)';
  }
  function line(root, title, value) {
    if (value === null || value === undefined || value === '') return;
    var row = document.createElement('div'), label = document.createElement('strong');
    if (title === 'DESCONECTADO HÁ') row.className = 'off-disconnect-highlight';
    label.textContent = title + ': '; row.appendChild(label); row.appendChild(document.createTextNode(String(value))); root.appendChild(row);
  }
  var loadedAt = Date.now();
  function timeText(p) { return p.acctstoptime && p.offline_seconds !== null ? duration(Number(p.offline_seconds)+(Date.now()-loadedAt)/1000) : 'Sem histórico de desconexão'; }
  var markers = [];
  points.forEach(function (point) {
    if (point.lat === null || point.lon === null) return;
    var online = String(point.online) === '1', blocked = point.bloqueado !== 'nao';
    var house = blocked ? 'home-3.png' : online ? 'home.png' : 'home-1.png';
    var icon = '<img src="map-icons/'+house+'" alt="">' + (!online ? '<b class="off-disconnected-dot"></b>' : '');
    var marker = L.marker([point.lat, point.lon], {icon: L.divIcon({className:'off-house-marker', html:icon, iconSize:[22,25], iconAnchor:[11,25]}), title: point.nome}).addTo(map);
    markers.push(marker);
    point.mapMarker = marker;
    marker.bindPopup(function () {
      var box = document.createElement('div'); box.className = 'off-map-client';
      [['LOGIN',point.login],['NOME',point.nome],['STATUS',online?'Online':blocked?'Bloqueado e offline':'Offline'],['RAMAL',point.ramal],['CELULAR',point.celular],['FONE',point.fone],['E-MAIL',point.email],['TIPO',point.tipo],['PLANO',point.plano],['SSID',point.ssid],['PORTA OLT',point.porta_olt],['CAIXA HERMÉTICA/CTO',point.caixa_herm],['PORTA SPLITTER',point.porta_splitter],['ONU/ONT',point.onu_ont]].forEach(function (item) { line(box,item[0],item[1]); });
      if (!online) {
        line(box, 'DESCONECTADO HÁ', point.acctstoptime ? duration(Number(point.offline_seconds) + (Date.now()-loadedAt)/1000) : 'Sem histórico de desconexão');
        line(box, 'ÚLTIMA DESCONEXÃO', point.acctstoptime);
      }
      line(box, 'COORDENADAS', point.lat + ', ' + point.lon);
      var external = document.createElement('a'); external.href='https://www.openstreetmap.org/?mlat='+point.lat+'&mlon='+point.lon+'#map=17/'+point.lat+'/'+point.lon; external.target='_blank'; external.rel='noopener noreferrer'; external.textContent='Abrir localização no OpenStreetMap'; box.appendChild(external);
      return box;
    }, {minWidth:300,maxWidth:440,autoPanPaddingTopLeft:[15,15],autoPanPaddingBottomRight:[15,15]});
  });
  if (markers.length) map.fitBounds(markers.map(function (marker) { return marker.getLatLng(); }), {padding:[45,45],maxZoom:17});
  else map.setView([-14.235,-51.925],4);
  if (markers.length === 1) markers[0].openPopup();
  var search = document.getElementById('off-client-search');
  var list = document.getElementById('off-client-list');
  var details = document.getElementById('off-client-details');
  function normalize(value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
  function renderClients() {
    var query = normalize(search.value.trim());
    var filtered = points.filter(function(p) { return normalize(p.nome + ' ' + p.login).indexOf(query) !== -1; });
    list.textContent = '';
    var located = points.filter(function(p) { return !!p.mapMarker; }).length;
    document.getElementById('off-client-count').textContent = filtered.length + ' de ' + points.length + ' clientes · ' + (points.length-located) + ' sem coordenadas';
    filtered.forEach(function(p) {
      var button = document.createElement('button'); button.type = 'button'; button.className = 'off-client-row';
      var name = document.createElement('strong'); name.textContent = p.nome || p.login; button.appendChild(name);
      var status = document.createElement('span'); status.className = 'off-client-status';
      button.classList.add(String(p.online)==='1'?'is-online':p.bloqueado!=='nao'?'is-blocked':'is-offline');
      status.textContent = p.login + ' · ' + (String(p.online)==='1'?'Online':p.bloqueado!=='nao'?'Bloqueado e offline':'Offline') + (p.mapMarker?'':' · Sem coordenadas'); button.appendChild(status);
      if (String(p.online)!=='1') { var time = document.createElement('small'); time.className='off-client-time'; time.textContent='Desconectado há '+timeText(p); time.dataset.login=p.login; button.appendChild(time); }
      button.addEventListener('click', function() {
        details.textContent = '';
        list.querySelectorAll('.is-selected').forEach(function(row){row.classList.remove('is-selected');}); button.classList.add('is-selected');
        if (p.mapMarker) { map.setView(p.mapMarker.getLatLng(), Math.max(map.getZoom(),17)); p.mapMarker.openPopup(); }
        else { line(details,'CLIENTE',p.nome); line(details,'LOGIN',p.login); line(details,'LOCALIZAÇÃO','Sem coordenadas válidas cadastradas'); line(details,'RAMAL',p.ramal); line(details,'CELULAR',p.celular); if(String(p.online)!=='1') line(details,'DESCONECTADO HÁ',timeText(p)); }
      }); list.appendChild(button);
    });
    if (!filtered.length) list.textContent = 'Nenhum cliente encontrado.';
  }
  search.addEventListener('input', renderClients); renderClients();
  var clientsByLogin = Object.create(null); points.forEach(function(p){clientsByLogin[p.login]=p;});
  setInterval(function(){list.querySelectorAll('.off-client-time').forEach(function(el){el.textContent='Desconectado há '+timeText(clientsByLogin[el.dataset.login]);});},30000);
  document.getElementById('off-map-return').addEventListener('click',function(){
    if(window.parent !== window){var dialog=window.parent.document.getElementById('off-map-modal');if(dialog){dialog.close();return;}}
    if(document.referrer && new URL(document.referrer).origin===location.origin && history.length>1){history.back();}else{location.href='/admin/addons/busca_inteligente/clientes_offline.php';}
  });
}());
