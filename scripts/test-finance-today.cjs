const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict');
const source=fs.readFileSync(__dirname+'/../addons/dashboard/finance_today.js','utf8');
async function fixture(reply,status=200,storage=new Map()){
 const nodes={},events={},timers=[];let requests=0;const urls=[];
 function node(){return {attrs:{},getAttribute(k){return this.attrs[k];},setAttribute(k,v){this.attrs[k]=v;},textContent:'',hidden:false,disabled:false,classList:{toggle(k,v){this[k]=v;}},addEventListener(k,f){this[k]=f;}};}
 const root=node();root.querySelector=s=>nodes[s]||(nodes[s]=node());
 const buttons=['day','week'].map(value=>{const n=node();n.attrs['data-finance-period']=value;return n;});root.querySelectorAll=()=>buttons;root.attrs['data-finance-preference-key']='fixture-user-period';
 const doc={hidden:false,querySelector:()=>root,addEventListener:(k,f)=>events[k]=f};
 vm.runInNewContext(source,{localStorage:{getItem:k=>storage.get(k),setItem:(k,v)=>storage.set(k,v)},document:doc,window:{addEventListener(){}},Intl,Number,AbortController,Error,clearTimeout(){},setTimeout(f,n){timers.push(n);return n;},fetch:async(url)=>{urls.push(url);requests++;return {status,ok:status===200,json:async()=>reply};}});
 await new Promise(r=>setImmediate(r));return {nodes,root,doc,events,timers,requests:()=>requests,urls,buttons,storage};
}
(async()=>{
 const data={ok:true,day:'2026-11-06',updated_at:'2026-11-06 13:25:20',due:{amount:'1234.56',quantity:2,overdue_quantity:1},cash:{incoming:'100.25',outgoing:'150.50',balance:'-50.25'}};
 let f=await fixture(data);assert(f.nodes['[data-finance-amount]'].textContent.includes('1.234,56'));assert(f.nodes['[data-finance-balance]'].classList['is-negative']);assert(f.timers.includes(30000));assert(f.nodes['[data-finance-quantity]'].textContent.includes('1 em atraso'));
 f.doc.hidden=true;f.events.visibilitychange();assert.equal(f.requests(),1);
 f=await fixture({...data,cash:{incoming:'100',outgoing:'0'}});assert(f.nodes['[data-finance-balance-wrap]'].hidden);
 f=await fixture({ok:false},403);assert(f.root.hidden);assert(!f.timers.includes(30000));
 f=await fixture({ok:false},503);assert(f.nodes['[data-finance-updated]'].textContent.includes('Não foi possível'));assert(!Object.values(f.nodes).some(n=>n.textContent==='R$ 0,00'));
 f=await fixture({...data,period:'week',range:{start:'2026-11-01',last:'2026-11-07'}});assert(f.nodes['[data-finance-title]'].textContent.includes('semana'));assert(f.nodes['[data-finance-updated]'].textContent.includes('01/11/2026 a 07/11/2026'));f.buttons[1].click();await new Promise(r=>setImmediate(r));assert(f.urls.at(-1).endsWith('period=week'));
 const saved=f.storage;f=await fixture(data,200,saved);assert(f.urls[0].endsWith('period=week'));assert.equal(f.buttons[1].attrs['aria-pressed'],'true');f.buttons[0].click();await new Promise(r=>setImmediate(r));assert.equal(saved.get('fixture-user-period'),'day');assert(f.urls.at(-1).endsWith('period=day'));assert.equal(f.buttons[0].attrs['aria-pressed'],'true');
 const brokenStorage={get(){throw Error('blocked');},set(){throw Error('blocked');}};f=await fixture(data,200,brokenStorage);assert(f.urls[0].endsWith('period=day'));f.buttons[1].click();await new Promise(r=>setImmediate(r));assert(f.urls.at(-1).endsWith('period=week'));
 console.log('PASS last choice restored, day/week buttons and blocked browser storage fallback');
 console.log('PASS day/week selector and visible Sunday-Saturday interval');
 console.log('PASS BRL display, negative balance, balance permission, hidden-page pause, denied access, unavailable values and refresh interval');
})().catch(e=>{console.error(e);process.exitCode=1;});
