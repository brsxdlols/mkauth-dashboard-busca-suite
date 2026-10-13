const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(require('path').join(__dirname,'../addons/busca_inteligente/performance.js'),'utf8');
(async()=>{for(const crypto of [undefined,{randomUUID:undefined},{randomUUID(){throw Error('UUID must not be required');}}]){
 const nodes=Object.fromEntries(['measure','apply','rollback','status','report','details'].map(k=>[k,{}]));const urls=[];
 const context={document:{getElementById:k=>nodes[k]},location:{href:'http://example.test/admin/addons/busca_inteligente/performance.php'},URL,URLSearchParams,Date,crypto,AbortController,setTimeout,clearTimeout,performance:{now:()=>0},DOMParser:class{parseFromString(){return{getElementById:()=>null};}},fetch:async(url)=>{if(url==='performance_api.php')return{ok:true,json:async()=>({plan:{id:'session-bound-plan'},csrf:'token'})};urls.push(url.toString());return{ok:true,text:async()=>''};}};
 vm.runInNewContext(source,context);await nodes.measure.onclick();await nodes.measure.onclick();assert.equal(urls.length,2);assert.notEqual(new URL(urls[0]).searchParams.get('_mka_run'),new URL(urls[1]).searchParams.get('_mka_run'));assert.equal(new URL(urls[0]).searchParams.get('_mka_perf'),'session-bound-plan');assert(!nodes.status.textContent.includes('randomUUID'));}
 console.log('PASS HTTP and HTTPS diagnostic requests without crypto.randomUUID; unique cache keys preserve server authorization.');
})().catch(e=>{console.error(e);process.exit(1);});
