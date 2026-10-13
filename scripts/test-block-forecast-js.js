const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../addons/shared/block_forecast.js'),'utf8');
async function test(f,pattern,error=false){
 const container={dataset:{},style:{},children:[],appendChild(n){this.children.push(n);}};
 const context={window:{},document:{createElement(){return {style:{},children:[],appendChild(n){this.children.push(n);}};}},fetch:async()=>({ok:!error,json:async()=>({ok:true,forecast:f})})};
 vm.runInNewContext(source,context);context.window.mkaLoadBlockForecast('fixture',container);
 for(let i=0;i<4;i++)await new Promise(resolve=>setImmediate(resolve));
 assert.match(container.textContent,pattern);
 assert.ok(!/[\u00c3\u00c2\ufffd]/.test(container.textContent),'text must not contain mojibake');
 assert.equal(container.hidden,false);
}
const base={open_count:2,oldest_due:'2026-10-26',cut_days:7,cut_date:'2026-11-02',eligible_date:'2026-11-03',eligible_now:false,automatic_block:'sim',automatic_unlock:'sim',active:true};
(async()=>{
 await test(base,/03\/11\/2026/);
 await test({...base,eligible_now:true},/pr\u00f3xima rotina/);
 await test({...base,open_count:0},/Nenhum boleto/);
 await test(base,/N\u00e3o foi poss\u00edvel/,true);
 console.log('PASS forecast text, future date, overdue state, empty balance and query failure');
})().catch(error=>{console.error(error);process.exitCode=1;});
