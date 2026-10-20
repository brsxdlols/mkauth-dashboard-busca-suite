const assert=require('assert'),fs=require('fs'),vm=require('vm'),path=require('path');
class Element {
 constructor(){this.children=[];this._text='';this.value='';this.dataset={};this.handlers={};this.classes=new Set();this.attributes={};this.scrollTop=0;this.clientHeight=200;this.classList={add:(x)=>this.classes.add(x),remove:(x)=>this.classes.delete(x),toggle:(x,on)=>on?this.classes.add(x):this.classes.delete(x)};}
 set textContent(x){this._text=x;this.children=[];} get textContent(){return this._text;}
 appendChild(x){this.children.push(x);return x;}
 setAttribute(k,v){this.attributes[k]=v;}
 getBoundingClientRect(){return {top:400,height:70};}
 scrollTo(x){this.scrolled=x;}
 addEventListener(k,f){this.handlers[k]=f;}
 querySelectorAll(){return [];}
 closest(){return null;}
 setPointerCapture(id){this.capture=id;}
 hasPointerCapture(id){return this.capture===id;}
 releasePointerCapture(){this.capture=null;}
}
const points=[{lat:-20,lon:-48,login:'teste',nome:'<img onerror=alert(1)>',online:'0',bloqueado:'nao',acctstoptime:'2026-10-18 10:00:00',offline_seconds:90000},{lat:-20.1,lon:-48.1,login:'online',nome:'Online',online:'1',bloqueado:'nao'},{lat:null,lon:null,login:'sem-coordenadas',nome:'José sem localização',online:'0',bloqueado:'nao',acctstoptime:null,offline_seconds:null}];
points[0].endereco='Rua Z';points[1].endereco='Rua A 2';points[2].endereco='Rua A 10';
const elements={};let docHandlers={},windowHandlers={},markerEvents=[],popups=[],interval,events={},layerSelected;
const doc={addEventListener(k,f){docHandlers[k]=f;},getElementById(id){if(id==='off-map-data')return {textContent:JSON.stringify(points)};return elements[id]||(elements[id]=new Element());},createElement(){return new Element()},createTextNode(text){return {textContent:text}}};
const surface=new Element();
const map={getContainer(){return surface;},dragging:{active:true,enabled(){return this.active},disable(){this.active=false},enable(){this.active=true}},closePopup(){},hasLayer(marker){return marker.visible},removeLayer(marker){marker.visible=false;},mouseEventToLatLng(event){return event.ll},on(k,f){events[k]=f;},fitBounds(){},setView(){},getZoom(){return 13;}};
const layer={addTo(){return this}};
function bounds(a,b){return {contains(p){return p[0]>=Math.min(a[0],b[0])&&p[0]<=Math.max(a[0],b[0])&&p[1]>=Math.min(a[1],b[1])&&p[1]<=Math.max(a[1],b[1]);}}}
const L={latLngBounds:bounds,rectangle(){return {addTo(){return this},setBounds(){}}},map(){return map},tileLayer(){return layer},control:{zoom(){return layer},layers(){return layer}},divIcon(x){return x},marker(coords){const handlers={};markerEvents.push(handlers);return {visible:true,on(k,f){handlers[k]=f;},addTo(){this.visible=true;return this},bindPopup(fn){popups.push(fn)},getLatLng(){return coords},openPopup(){}}}};
const context={L,document:doc,Date,window:{addEventListener(k,f){windowHandlers[k]=f;}},location:{origin:'https://example.test'},history:{},localStorage:{getItem(){return 'Mapa';},setItem(k,v){layerSelected=v;}},setInterval(f,ms){assert.equal(ms,30000);interval=f;},URL};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../addons/rel_cli_offline/offline-map.js'),'utf8'),context);
const text=e=>e.textContent+(e.children||[]).map(text).join('');
assert.equal(popups.length,2,'No marker for customer without coordinates');
assert(text(popups[0]()).includes('1 dia(s), 1 hora(s)'));
assert(text(popups[0]()).includes('<img onerror=alert(1)>'));
assert(!text(popups[1]()).includes('DESCONECTADO HÁ'));
assert(popups[0]().children.some(x=>x.href&&x.href.startsWith('https://www.openstreetmap.org/')));
assert.equal(elements['off-client-list'].children.length,3);
assert(text(elements['off-client-list']).includes('Sem coordenadas'));
elements['off-client-search'].value='jose';elements['off-client-search'].handlers.input();
assert.equal(elements['off-client-list'].children.length,1,'Accent-insensitive search');
assert(text(elements['off-client-list']).includes('José'));
elements['off-client-list'].children[0].handlers.click();assert(text(elements['off-client-details']).includes('Sem coordenadas'));
elements['off-client-search'].value='online';elements['off-client-search'].handlers.input();assert(!text(elements['off-client-list']).includes('Desconectado há'));
markerEvents[0].popupopen();
assert.equal(elements['off-client-search'].value,'','Reveal marker customer hidden by search');
assert.equal(elements['off-client-list'].children.length,3);
assert.equal(elements['off-client-list'].children[0].attributes['aria-pressed'],'true');
assert(elements['off-client-list'].children[0].classes.has('is-selected'));
assert.equal(elements['off-client-list'].scrolled.behavior,'smooth');
markerEvents[1].popupopen();
assert.equal(elements['off-client-list'].children[0].attributes['aria-pressed'],'false');
assert.equal(elements['off-client-list'].children.find(row=>text(row).includes('Online')).attributes['aria-pressed'],'true');
elements['off-sort-name'].handlers.click();
assert.equal(elements['off-sort-name'].attributes['aria-pressed'],'true');
const names=elements['off-client-list'].children.map(row=>row.children[0].textContent);
elements['off-sort-name'].handlers.click();assert.deepEqual(elements['off-client-list'].children.map(row=>row.children[0].textContent),names.slice().reverse());
elements['off-sort-address'].handlers.click();assert.equal(elements['off-sort-address'].attributes['aria-pressed'],'true');assert(elements['off-client-list'].children[0].children[0].textContent==='Online');
elements['off-sort-address'].handlers.click();assert(elements['off-client-list'].children[0].children[0].textContent.includes('<img'));
elements['off-sort-time'].handlers.click();assert(text(elements['off-client-list'].children[0]).includes('teste'));
events.baselayerchange({name:'Satélite'});assert.equal(layerSelected,'Satélite');interval();
console.log('Map: offline duration, online status, missing coordinates, safe text, search, details, layer preference and timer OK');

function event(extra){return Object.assign({target:surface,preventDefault(){},stopPropagation(){},stopImmediatePropagation(){},button:0,pointerId:1},extra);}
docHandlers.keydown(event({code:'Space'}));assert.equal(map.dragging.active,false);
surface.handlers.pointerdown(event({ll:[-20.05,-48.05],clientX:0,clientY:0}));
surface.handlers.pointermove(event({ll:[-19.95,-47.95],clientX:100,clientY:100}));
surface.handlers.pointerup(event({}));assert.equal(elements['off-client-list'].children.length,1,'Area filters list');assert(text(elements['off-client-list']).includes('teste'));assert.equal(elements['off-area-clear'].hidden,false);
docHandlers.keyup(event({code:'Space'}));assert.equal(map.dragging.active,true);
docHandlers.keydown(event({key:'Escape'}));assert.equal(elements['off-client-list'].children.length,3,'Escape restores all including missing coordinates');assert.equal(elements['off-area-clear'].hidden,true);
docHandlers.keydown(event({code:'Space',target:{closest(){return true}}}));assert.equal(map.dragging.active,true,'Typing Space must not start area selection');
console.log('Area drag, list filtering, Escape restore and keyboard input guards OK');
