const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'patch-map-traffic.py'),'utf8').match(/PAUSE="""([\s\S]*?)"""/)[1];
const requests=[],timers=new Map();let next=0,renders=0;
const box={isConnected:true,querySelector:()=>({textContent:''})};
const context={window:{},document:{querySelector:()=>box},CSS:{escape:x=>x},AbortController,setTimeout:fn=>{timers.set(++next,fn);return next;},clearTimeout:id=>timers.delete(id),fetch:(url,opts)=>new Promise(resolve=>requests.push({url,opts,resolve})),renderTraffic:()=>renders++,stopTraffic:reset=>{if(context.trafficPaused)assert.equal(reset,false);for(const id of timers.keys())timers.delete(id);if(context.trafficRequest)context.trafficRequest.abort();context.trafficRequest=null;},trafficLogin:'alpha',trafficRequest:null,trafficTimer:null};
vm.createContext(context);vm.runInContext(source,context);
const flush=async()=>{for(let i=0;i<12;i++)await Promise.resolve();};
(async()=>{
 context.pollTraffic('alpha');assert.equal(requests.length,1);
 context.window.mkaMapTrafficControl.pause(true);assert(requests[0].opts.signal.aborted);
 requests[0].resolve({ok:true,json:async()=>({ok:true})});await flush();assert.equal(renders,0);assert.equal(timers.size,0);
 context.pollTraffic('alpha');assert.equal(requests.length,1);
 context.window.mkaMapTrafficControl.pause(false);assert.equal(requests.length,2);
 requests[1].resolve({ok:true,json:async()=>({ok:true})});await flush();assert.equal(renders,1);assert.equal(timers.size,1);
 context.window.mkaMapTrafficControl.pause(true);assert.equal(timers.size,0);
 context.startTraffic('beta');assert.equal(context.trafficPaused,false);assert.equal(context.trafficLogin,'beta');assert.equal(timers.size,1);
 console.log('PASS pause preserves history, ignores in-flight responses, stops scheduling, resumes once and switches login.');
})().catch(e=>{console.error(e);process.exit(1);});
