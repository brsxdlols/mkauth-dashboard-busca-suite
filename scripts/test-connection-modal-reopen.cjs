const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
const source=fs.readFileSync(path.join(__dirname,'../addons/shared/connection_settings.js'),'utf8');
async function run(code){
 const queue=[],clicks=[];let finish,signal;
 class N{constructor(t){this.tagName=t.toUpperCase();this.children=[];this.style={};this.events={};this.attrs={};}append(...n){this.children.push(...n)}replaceChildren(...n){this.children=n}setAttribute(k,v){this.attrs[k]=v}getAttribute(k){return this.attrs[k]}addEventListener(k,f){this.events[k]=f}querySelector(k){for(const n of this.children){if(n.tagName===k.toUpperCase())return n;const found=n.querySelector&&n.querySelector(k);if(found)return found}}showModal(){this.open=true}close(){this.open=false;queue.push(()=>this.events.close())}focus(){} }
 const document={body:new N('body'),createElement:t=>new N(t),addEventListener(k,f){if(k==='click')clicks.push(f)},getElementById(){return null},querySelector(){return null}};
 const e={document,window:{},AbortController,URLSearchParams,setTimeout,clearTimeout,fetch(u,o){signal=o.signal;return new Promise((resolve,reject)=>{finish=()=>resolve({ok:true,json:async()=>({csrf:'test',can_global:false})});signal.addEventListener('abort',()=>reject(Object.assign(new Error('aborted'),{name:'AbortError'})));})}};
 vm.createContext(e);vm.runInContext(code,e);
 const target={getAttribute(){return 'nao'},focus(){}};
 clicks.forEach(f=>f({preventDefault(){},target:{closest:s=>s==='[data-binding-info]'?target:null}}));
 const dialog=document.body.children[0];function find(n){if(n.textContent==='Abrir IP / MAC dos cadastros')return n;for(const c of n.children||[]){let v=find(c);if(v)return v}}
 find(dialog).events.click();assert.equal(dialog.open,true);assert.equal(queue.length,1);queue.shift()();const canceled=signal.aborted;finish();for(let i=0;i<5;i++)await new Promise(setImmediate);
 function close(){dialog.close();queue.shift()();}return {close,canceled,status:dialog.children[1],body:dialog.children[2],document};
}
(async()=>{
 const old=await run(source.replace('if(modal.open)return;version++;','version++;'));assert.equal(old.canceled,true,'Regression must reproduce cancellation');
 const fixed=await run(source);assert.equal(fixed.canceled,false,'Delayed close must not cancel reopened modal');assert.equal(fixed.status.textContent,'');assert.equal(fixed.status.attrs['data-loading'],'false');assert(fixed.body.children.some(n=>n.className==='connection-settings-fields'));fixed.close();assert.equal(fixed.document.body.style.overflow,undefined,'Closing restores original page scrolling');
 console.log('PASS reproduced old delayed-close cancellation; reopening now completes and renders the tool. No server writes.');
})();
