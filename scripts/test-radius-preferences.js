const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../addons/shared/radius_preferences.js'),'utf8');
let globalValue=true,failSave=false;
async function settle(){for(let n=0;n<6;n++)await new Promise(resolve=>setImmediate(resolve));}
async function browser(){
 const input={},feedback={},context={window:{addEventListener(){}},document:{hidden:false},setInterval(){return 1;},clearInterval(){},fetch:async(url,options={})=>{
  if(options.method==='POST'){
   if(failSave)return {ok:false};
   const data=JSON.parse(options.body);assert.equal(data.csrf,'token');globalValue=data.hide_disabled;
  }
  return {ok:true,json:async()=>({ok:true,hide_disabled:globalValue,csrf:'token'})};
 }};
 vm.runInNewContext(source,context);context.window.mkaRadiusPreference(input,feedback,()=>{});await settle();return {input,feedback};
}
(async()=>{
 const a=await browser();assert.equal(a.input.checked,true);
 a.input.checked=false;a.input.onchange();await settle();assert.equal(globalValue,false);
 const b=await browser();assert.equal(b.input.checked,false,'different browser reads the global choice');
 b.input.checked=true;b.input.onchange();await settle();assert.equal((await browser()).input.checked,true);
 failSave=true;b.input.checked=false;b.input.onchange();await settle();assert.equal(b.input.checked,true);assert.equal(globalValue,true);assert.match(b.feedback.textContent,/poss\u00edvel salvar/);
 console.log('PASS shared preference across browsers, persistence and rollback after failed save');
})().catch(e=>{console.error(e);process.exitCode=1;});
