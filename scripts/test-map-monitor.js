const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
class Node {
 constructor(tag,classes=''){this.tag=tag;this.className=classes;this.children=[];this.attrs={};this.events={};this.style={};this.textContent='';this.classList={contains:c=>this.className.split(' ').includes(c),add:c=>{if(!this.classList.contains(c))this.className+=' '+c;},toggle:(c,on)=>{if(on===undefined)on=!this.classList.contains(c);this.className=this.className.split(' ').filter(x=>x&&x!==c).concat(on?[c]:[]).join(' ');}};}
 append(...nodes){for(const node of nodes){if(node.parent)node.remove();this.children.push(node);node.parent=this;}}
 before(node){const i=this.parent.children.indexOf(this);this.parent.children.splice(i,0,node);node.parent=this.parent;}
 remove(){if(this.parent)this.parent.children=this.parent.children.filter(x=>x!==this);this.parent=null;}
 replaceChildren(...nodes){for(const node of [...this.children])node.remove();this.append(...nodes);}
 setAttribute(k,v){this.attrs[k]=String(v);}getAttribute(k){return this.attrs[k]??null;}
 addEventListener(k,v){this.events[k]=v;}
 querySelectorAll(selector){const result=[];for(const child of this.children){if(selector[0]==='.'?child.classList.contains(selector.slice(1)):child.tag===selector)result.push(child);result.push(...child.querySelectorAll(selector));}return result;}
 querySelector(selector){return this.querySelectorAll(selector)[0]||null;}
 closest(selector){return this.classList.contains(selector.slice(1))?this:this.parent?.closest(selector);}
 get isConnected(){return this===body||!!this.parent?.isConnected;}
 set innerHTML(_){throw Error('Use text nodes for log data.');}
}
const body=new Node('body','monitor-client'),popup=new Node('div','popup');body.append(popup);
function box(login){const b=new Node('div','traffic-mini');b.setAttribute('data-traffic-login',login);b.append(new Node('svg','traffic-mini-chart'));const legend=new Node('div','traffic-mini-legend');legend.append(new Node('b','traffic-rx'),new Node('b','traffic-tx'));b.append(legend);popup.append(b);return b;}
let selected=box('alpha'),requests=[],stored={},timers=new Set();
const doc={body,hidden:false,readyState:'complete',createElement:t=>new Node(t),createTextNode:t=>Object.assign(new Node('#text'),{textContent:t}),querySelector:()=>selected,addEventListener(){}};
const context={document:doc,window:{addEventListener(){}},localStorage:{getItem:k=>stored[k],setItem:(k,v)=>stored[k]=v},AbortController,Map,Date,Array,String,encodeURIComponent,setTimeout(fn){const token={fn};timers.add(token);return token;},clearTimeout:t=>timers.delete(t),fetch:(url,opts)=>new Promise(resolve=>requests.push({url,opts,resolve}))};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../addons/shared/map_monitor.js'),'utf8'),context);
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
 assert(requests[0].url.includes('login=alpha'));
 const old=requests[0];selected.remove();selected=box('beta@demo');context.window.mkaMapMonitor.sync(selected);
 assert(old.opts.signal.aborted,'Switch must cancel the old request');assert(requests[1].url.includes('login=beta%40demo'));
 const row=(login,line,time)=>({login,line,event_time:time,type:'conectados',label:'Login autorizado',source:'radius',event_key:login+time});
 requests[1].resolve({ok:true,json:async()=>({current:{login:'beta@demo',state:'online'},rows:[row('alpha','foreign login',99),...Array.from({length:7},(_,i)=>row('beta@demo','event '+i,i))],updated_at:'test'})});await flush();
 assert.equal(body.querySelectorAll('.mka-radius-event').length,5);assert(!body.querySelectorAll('code').some(x=>x.textContent==='foreign login'));
 old.resolve({ok:true,json:async()=>({current:{login:'alpha',state:'offline'},rows:[row('alpha','stale response',100)]})});await flush();
 assert(!body.querySelectorAll('code').some(x=>x.textContent==='stale response'),'Old response must never overwrite the selected login');
 const check=selected.querySelector('input');check.checked=false;check.events.change.call(check);assert(selected.classList.contains('mka-traffic-hide-values'));assert.equal(stored['mka-monitor-show-values'],'0');
 const pause=body.querySelector('.mka-radius-heading').querySelector('button');pause.events.click.call(pause);assert.equal(pause.getAttribute('aria-pressed'),'true');assert.equal(timers.size,0);
 context.window.mkaMapMonitor.stop();assert.equal(body.querySelector('.mka-radius-monitor'),null);assert.equal(timers.size,0);
 console.log('PASS selected-login isolation, stale response cancellation, five-event limit, value toggle, pause and cleanup.');
})().catch(e=>{console.error(e);process.exit(1);});
