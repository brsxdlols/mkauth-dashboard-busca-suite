const fs=require('fs'),vm=require('vm'),assert=require('assert');
let els={},created=[],timers=[];for(const id of ['server-time-clock','server-time-details','server-time-state','server_timezone_mode','server_timezone_zone','server_timezone_enable','server_timezone_apply'])els[id]={textContent:'',before(){},addEventListener(e,f){this.change=f;}};
els.server_timezone_mode.value='auto';els.server_timezone_zone.value='America/Manaus';els.server_timezone_zone.form={append(){}};
let ctx={window:{confirm:()=>false},Intl,Date,Error,performance:{now:()=>0},document:{hidden:false,getElementById:id=>els[id],createElement:()=>{let e={setAttribute(){},style:{}};created.push(e);return e;}},setInterval:f=>timers.push(f),fetch:async()=>({ok:true,json:async()=>({epoch:Date.parse('2026-10-26T16:00:00Z')/1000,server_zone:'America/Maceio',offset:'-03:00',provider:{},config:{enabled:true,mode:'auto',zone:'America/Maceio'},status:{status:'applied',detected_zone:'America/Maceio',config:{mode:'auto',zone:'America/Maceio'},provider:{}}})})};
vm.runInNewContext(fs.readFileSync(require('path').join(__dirname,'../addons/shared/server_timezone.js'),'utf8'),ctx);
setImmediate(()=>{
 assert(els.server_timezone_mode.disabled&&els.server_timezone_zone.disabled&&els.server_timezone_apply.disabled);
 els.server_timezone_enable.checked=true;els.server_timezone_enable.change();assert(!els.server_timezone_mode.disabled&&!els.server_timezone_apply.disabled);
 let prevented=false;els.server_timezone_apply.change({preventDefault(){prevented=true;}});assert(prevented);
 assert(els['server-time-clock'].textContent.includes('13:00:00'));
 els.server_timezone_mode.value='manual';els.server_timezone_mode.change();let preview=created.find(e=>e.id==='server-time-preview');assert(preview.textContent.includes('12:00:00'));assert(preview.textContent.includes('America/Manaus'));assert(preview.textContent.includes('ainda não salva'));assert(els['server-time-clock'].textContent.includes('13:00:00'));
 els.server_timezone_mode.value='auto';els.server_timezone_mode.change();assert(preview.textContent.includes('13:00:00'));
 els.server_timezone_enable.checked=false;els.server_timezone_enable.change();assert(els.server_timezone_mode.disabled&&els.server_timezone_apply.disabled);
 console.log('Optional UI: disabled by default, explicit enable, cancelled confirmation, timezone preview and unchanged actual clock passed.');
});
