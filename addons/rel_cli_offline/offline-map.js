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
  var areaBounds = null, areaRectangle = null;
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
  var search = document.getElementById('off-client-search');
  var list = document.getElementById('off-client-list');
  var details = document.getElementById('off-client-details');
  var sortBy = 'time', sortDirection = -1;
  var selectedLogin = null, clientButtons = Object.create(null);
  function selectClient(point, scroll) {
    selectedLogin = point.login;
    if (!clientButtons[point.login]) { search.value = ''; renderClients(); }
    Object.keys(clientButtons).forEach(function(login) {
      var button = clientButtons[login], active = login === selectedLogin;
      button.classList.toggle('is-selected',active); button.setAttribute('aria-pressed',String(active));
    });
    details.textContent = '';
    var row = clientButtons[point.login];
    if (scroll && row) {
      var rowBounds = row.getBoundingClientRect(), listBounds = list.getBoundingClientRect();
      list.scrollTo({top:Math.max(0,list.scrollTop + rowBounds.top-listBounds.top-(list.clientHeight-rowBounds.height)/2),behavior:'smooth'});
    }
  }
  function normalize(value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
  function address(point) { return [point.endereco,point.numero,point.bairro,point.cidade].filter(Boolean).join(', '); }
  function compareClients(a,b) {
    var left, right, order;
    if (sortBy === 'time') {
      left = String(a.online)!=='1' && a.acctstoptime && a.offline_seconds!==null ? Number(a.offline_seconds) : null;
      right = String(b.online)!=='1' && b.acctstoptime && b.offline_seconds!==null ? Number(b.offline_seconds) : null;
      if (left===null || right===null) { if(left!==right)return left===null?1:-1; order=0; }
      else order=(left-right)*sortDirection;
    } else {
      left=sortBy==='name' ? (a.nome||a.login) : address(a); right=sortBy==='name' ? (b.nome||b.login) : address(b);
      if (!left || !right) { if(left!==right)return left? -1:1; order=0; }
      else order=left.localeCompare(right,'pt-BR',{sensitivity:'base',numeric:true})*sortDirection;
    }
    return order || (a.nome||a.login).localeCompare(b.nome||b.login,'pt-BR',{sensitivity:'base',numeric:true}) || a.login.localeCompare(b.login);
  }
  function updateSortButtons() {
    [['time','Tempo desconectado'],['name','Nome'],['address','Endereço']].forEach(function(item){
      var button=document.getElementById('off-sort-'+item[0]), active=sortBy===item[0];
      button.setAttribute('aria-pressed',String(active)); button.textContent=item[1]+(active?(sortDirection===1?' ↑':' ↓'):'');
      button.title=item[0]==='time' ? (active&&sortDirection===1?'Menor tempo primeiro':'Maior tempo primeiro') : (active&&sortDirection===-1?'Z a A':'A a Z');
    });
  }
  function renderClients() {
    var query = normalize(search.value.trim());
    var filtered = points.filter(function(p) { return (!areaBounds || (p.mapMarker && areaBounds.contains(p.mapMarker.getLatLng()))) && normalize(p.nome + ' ' + p.login).indexOf(query) !== -1; });
    filtered.sort(compareClients);
    list.textContent = ''; clientButtons = Object.create(null);
    var located = points.filter(function(p) { return !!p.mapMarker; }).length;
    document.getElementById('off-client-count').textContent = filtered.length + ' de ' + points.length + ' clientes · ' + (points.length-located) + ' sem coordenadas' + (areaBounds ? ' · área selecionada' : '');
    filtered.forEach(function(p) {
      var button = document.createElement('button'); button.type = 'button'; button.className = 'off-client-row';
      clientButtons[p.login] = button;
      button.setAttribute('aria-pressed',String(selectedLogin === p.login));
      if (selectedLogin === p.login) button.classList.add('is-selected');
      var name = document.createElement('strong'); name.textContent = p.nome || p.login; button.appendChild(name);
      var status = document.createElement('span'); status.className = 'off-client-status';
      button.classList.add(String(p.online)==='1'?'is-online':p.bloqueado!=='nao'?'is-blocked':'is-offline');
      status.textContent = p.login + ' · ' + (String(p.online)==='1'?'Online':p.bloqueado!=='nao'?'Bloqueado e offline':'Offline') + (p.mapMarker?'':' · Sem coordenadas'); button.appendChild(status);
      if (address(p)) { var locationLine=document.createElement('span'); locationLine.className='off-client-address'; locationLine.textContent=address(p); button.appendChild(locationLine); }
      if (String(p.online)!=='1') { var time = document.createElement('small'); time.className='off-client-time'; time.textContent='Desconectado há '+timeText(p); time.dataset.login=p.login; button.appendChild(time); }
      button.addEventListener('click', function() {
        selectClient(p, false);
        if (p.mapMarker) { map.setView(p.mapMarker.getLatLng(), Math.max(map.getZoom(),17)); p.mapMarker.openPopup(); }
        else { line(details,'CLIENTE',p.nome); line(details,'LOGIN',p.login); line(details,'LOCALIZAÇÃO','Sem coordenadas válidas cadastradas'); line(details,'RAMAL',p.ramal); line(details,'CELULAR',p.celular); if(String(p.online)!=='1') line(details,'DESCONECTADO HÁ',timeText(p)); }
      }); list.appendChild(button);
    });
    if (!filtered.length) list.textContent = 'Nenhum cliente encontrado.';
  }
  ['time','name','address'].forEach(function(key){document.getElementById('off-sort-'+key).addEventListener('click',function(){sortDirection=sortBy===key?-sortDirection:(key==='time'?-1:1);sortBy=key;updateSortButtons();renderClients();list.scrollTop=0;});});
  updateSortButtons();
  search.addEventListener('input', renderClients); renderClients();
  points.forEach(function(point){if(point.mapMarker)point.mapMarker.on('popupopen',function(){selectClient(point,true);});});
  if (markers.length === 1) markers[0].openPopup();
  var surface = map.getContainer(), spaceHeld = false, areaGesture = null, resumeDragging = false;
  var areaStatus = document.getElementById('off-area-status'), areaClear = document.getElementById('off-area-clear');
  function areaMessage() {
    areaClear.hidden = !areaBounds;
    areaStatus.textContent = areaBounds ? 'Pressione "Esc" para finalizar a seleção e mostrar todos os clientes.' : spaceHeld ? 'Arraste para selecionar a região. Pressione "Esc" para finalizar a seleção.' : 'Segure o espaço para arrastar seleção para a região dentro do mapa.';
    document.getElementById('off-map-selection-hint').textContent = areaStatus.textContent;
    document.getElementById('off-map-selection-hint').classList.toggle('is-active',!!areaBounds||spaceHeld);
    surface.classList.toggle('off-area-ready',spaceHeld);
  }
  function applyArea() {
    map.closePopup(); details.textContent='';
    points.forEach(function(p){if(!p.mapMarker)return;var visible=!areaBounds||areaBounds.contains(p.mapMarker.getLatLng());if(visible&&!map.hasLayer(p.mapMarker))p.mapMarker.addTo(map);else if(!visible&&map.hasLayer(p.mapMarker))map.removeLayer(p.mapMarker);});
    renderClients(); list.scrollTop=0; areaMessage();
  }
  function drawArea(bounds) {
    if(areaRectangle)map.removeLayer(areaRectangle);
    areaRectangle = bounds ? L.rectangle(bounds,{color:'#245dd8',weight:2,fillOpacity:0.13,interactive:false}).addTo(map) : null;
  }
  function finishArea(cancel) {
    if(!areaGesture)return;
    var gesture=areaGesture;areaGesture=null;
    if(surface.hasPointerCapture(gesture.id))surface.releasePointerCapture(gesture.id);
    if(!cancel&&gesture.moved){areaBounds=L.latLngBounds(gesture.start,gesture.end);drawArea(areaBounds);applyArea();}
    else drawArea(areaBounds);
  }
  function releaseSpace(){spaceHeld=false;if(resumeDragging)map.dragging.enable();resumeDragging=false;areaMessage();}
  function clearArea(){finishArea(true);areaBounds=null;drawArea(null);releaseSpace();applyArea();}
  function typing(event){return event.target.closest && event.target.closest('input,textarea,select,button,[contenteditable="true"]');}
  document.addEventListener('keydown',function(event){
    if(event.key==='Escape'&&(areaBounds||areaGesture||spaceHeld)){event.preventDefault();event.stopPropagation();clearArea();return;}
    if(event.code!=='Space'||typing(event))return;
    event.preventDefault();if(spaceHeld)return;spaceHeld=true;resumeDragging=map.dragging.enabled();map.dragging.disable();areaMessage();
  },true);
  document.addEventListener('keyup',function(event){if(event.code==='Space'&&spaceHeld){event.preventDefault();finishArea(false);releaseSpace();}},true);
  window.addEventListener('blur',function(){finishArea(true);releaseSpace();});
  surface.addEventListener('pointerdown',function(event){
    if(!spaceHeld||event.button!==0||event.target.closest('.leaflet-control'))return;
    event.preventDefault();event.stopImmediatePropagation();
    var start=map.mouseEventToLatLng(event);areaGesture={id:event.pointerId,start:start,end:start,x:event.clientX,y:event.clientY,moved:false};surface.setPointerCapture(event.pointerId);drawArea(L.latLngBounds(start,start));
  },true);
  surface.addEventListener('pointermove',function(event){
    if(!areaGesture||areaGesture.id!==event.pointerId)return;event.preventDefault();event.stopImmediatePropagation();
    areaGesture.end=map.mouseEventToLatLng(event);areaGesture.moved=areaGesture.moved||Math.hypot(event.clientX-areaGesture.x,event.clientY-areaGesture.y)>5;
    if(areaRectangle)areaRectangle.setBounds(L.latLngBounds(areaGesture.start,areaGesture.end));
  },true);
  surface.addEventListener('pointerup',function(event){if(areaGesture&&areaGesture.id===event.pointerId){event.preventDefault();event.stopImmediatePropagation();finishArea(false);}},true);
  surface.addEventListener('pointercancel',function(){finishArea(true);releaseSpace();},true);
  areaClear.addEventListener('click',clearArea);areaMessage();
  var clientsByLogin = Object.create(null); points.forEach(function(p){clientsByLogin[p.login]=p;});
  setInterval(function(){list.querySelectorAll('.off-client-time').forEach(function(el){el.textContent='Desconectado há '+timeText(clientsByLogin[el.dataset.login]);});},30000);
  document.getElementById('off-map-return').addEventListener('click',function(){
    if(window.parent !== window){var dialog=window.parent.document.getElementById('off-map-modal');if(dialog){dialog.close();return;}}
    if(document.referrer && new URL(document.referrer).origin===location.origin && history.length>1){history.back();}else{location.href='/admin/addons/busca_inteligente/clientes_offline.php';}
  });
}());
