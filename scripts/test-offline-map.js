const assert=require('assert'),fs=require('fs'),vm=require('vm'),path=require('path');
class Element {
 constructor(){this.children=[];this._text='';this.value='';this.dataset={};this.handlers={};this.classList={add(){},remove(){}};}
 set textContent(x){this._text=x;this.children=[];} get textContent(){return this._text;}
 appendChild(x){this.children.push(x);return x;}
 addEventListener(k,f){this.handlers[k]=f;}
 querySelectorAll(){return [];}
}
const points=[{lat:-20,lon:-48,login:'teste',nome:'<img onerror=alert(1)>',online:'0',bloqueado:'nao',acctstoptime:'2026-10-18 10:00:00',offline_seconds:90000},{lat:-20.1,lon:-48.1,login:'online',nome:'Online',online:'1',bloqueado:'nao'},{lat:null,lon:null,login:'sem-coordenadas',nome:'José sem localização',online:'0',bloqueado:'nao',acctstoptime:null,offline_seconds:null}];
const elements={};let popups=[],interval,events={},layerSelected;
const doc={getElementById(id){if(id==='off-map-data')return {textContent:JSON.stringify(points)};return elements[id]||(elements[id]=new Element());},createElement(){return new Element()},createTextNode(text){return {textContent:text}}};
const map={on(k,f){events[k]=f;},fitBounds(){},setView(){},getZoom(){return 13;}};
const layer={addTo(){return this}};
const L={map(){return map},tileLayer(){return layer},control:{zoom(){return layer},layers(){return layer}},divIcon(x){return x},marker(coords){return {addTo(){return this},bindPopup(fn){popups.push(fn)},getLatLng(){return coords},openPopup(){}}}};
const context={L,document:doc,Date,window:{},location:{origin:'https://example.test'},history:{},localStorage:{getItem(){return 'Mapa';},setItem(k,v){layerSelected=v;}},setInterval(f,ms){assert.equal(ms,30000);interval=f;},URL};
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
events.baselayerchange({name:'Satélite'});assert.equal(layerSelected,'Satélite');interval();
console.log('Map: offline duration, online status, missing coordinates, safe text, search, details, layer preference and timer OK');
