const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(require('path').join(__dirname,'../addons/shared/monitor_loading.js'),'utf8');
function setup(initial=false){
 let exists=initial,observe,timeout,frames=[],classes=new Set(),disconnected=false,reloads=0;
 const body={append(n){this.panel=n;}};
 const document={body,documentElement:{classList:{add(s){classes.add(s);}}},querySelector(){return exists?{}:null;},createElement(){return {events:{},append(...n){this.children=n;},addEventListener(k,f){this.events[k]=f;}};}};
 vm.runInNewContext(source,{document,MutationObserver:class{constructor(fn){observe=fn;}observe(){}disconnect(){disconnected=true;}},setTimeout(fn){timeout=fn;return 1;},clearTimeout(){timeout=null;},requestAnimationFrame(fn){frames.push(fn);},location:{reload(){reloads++;}}});
 return {classes,body,load(){exists=true;observe();},paint(){while(frames.length)frames.shift()();},fail(){timeout();},disconnected:()=>disconnected,reloads:()=>reloads};
}
let f=setup();assert(!f.classes.has('mka-monitor-ready'));f.load();assert(!f.classes.has('mka-monitor-ready'));f.paint();assert(f.classes.has('mka-monitor-ready'));assert(f.disconnected());assert.equal(f.reloads(),0);
f=setup(true);f.paint();assert(f.classes.has('mka-monitor-ready'));
f=setup();f.fail();assert(f.classes.has('mka-monitor-message'));assert(!f.classes.has('mka-monitor-ready'));assert.equal(f.reloads(),0);f.body.panel.children[1].events.click();assert.equal(f.reloads(),1);
console.log('PASS hide map until client panel layout; already-loaded panel; timeout with explicit retry only');
