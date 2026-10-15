const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
function check(url,text,hasForm,expected){
 let removed=false;
 const node={textContent:text,querySelector:()=>hasForm?{}:null,remove:()=>{removed=true;}};
 const context={location:{pathname:url},document:{body:{},querySelectorAll:()=>[node]},window:{addEventListener(){}},MutationObserver:class{observe(){} disconnect(){}}};
 vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../addons/shared/receive_notice.js'),'utf8'),context);
 assert.equal(removed,expected);
}
check('/admin/receber.hhvm','Aviso!!! Passou 36 dias bloqueado, valor em R$ 66,00 pelo tempo....',false,true);
check('/admin/receber.php','Passou 1 dia bloqueado, valor em R$ 2,00',false,true);
check('/admin/receber.hhvm','Possui mais 001 títulos vencidos',false,false);
check('/admin/receber.hhvm','Passou 36 dias bloqueado, valor em R$ 66,00',true,false);
check('/admin/outro.hhvm','Passou 36 dias bloqueado, valor em R$ 66,00',false,false);
console.log('PASS only the blocked-days notice is removed; forms, other alerts and other pages remain.');
