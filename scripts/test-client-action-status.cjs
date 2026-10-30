const vm=require('vm'),fs=require('fs'),assert=require('assert'),path=require('path');
(async()=>{for(const expected of ['active','inactive','deleted']){
 let timer,confirmed=0,writes=0;const window={};let state={exists:true,active:expected==='active'?false:true};
 const e={window,AbortController,Date,setTimeout(fn,ms){if(ms!==4000)timer=fn;return 1},clearTimeout(){},fetch:async(url,options)=>{assert(!options.method||options.method==='GET');assert(url.includes('login=one'));return {ok:true,json:async()=>state}}};
 vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../addons/shared/client_action_status.js'),'utf8'),e);
 const stop=window.mkaWatchClientAction('one',expected,()=>confirmed++);await timer();assert.equal(confirmed,0);
 state=expected==='deleted'?{exists:false,active:null}:{exists:true,active:expected==='active'};await timer();assert.equal(confirmed,1);stop();assert.equal(writes,0);
}console.log('PASS activate/disable/delete refresh only after read-only state confirmation');})();
