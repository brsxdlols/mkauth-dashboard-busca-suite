const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(path.join(__dirname,'../addons/dashboard/mkauth_dashboard_top.php'),'utf8');
const script=source.slice(source.indexOf('    var sessionsCount ='),source.indexOf("    $('#UUE_Data')")).replace(/<\?php.*?\?>/g,JSON.stringify({users:2,sessions:3}));
const label={textContent:''},counter={id:'UUE_SESSOES',previousElementSibling:label};let focus;
const context={document:{hidden:false,getElementById:id=>counter.id===id?counter:null},window:{addEventListener(name,fn){if(name==='focus')focus=fn}},setInterval(fn,ms){assert.equal(ms,30000)},setTimeout(){return 0},clearTimeout(){},AbortController,fetch:async()=>({ok:true,json:async()=>({users:3,sessions:4})})};
vm.runInNewContext(script,context);
assert.equal(label.textContent,'Usuários conectados: ');assert.equal(counter.textContent,'02 | Sessões: 03');assert.equal(context.document.getElementById('UUE_SESSOES'),null);
(async()=>{await focus();assert.equal(counter.textContent,'03 | Sessões: 04');console.log('PASS distinct-user display, total sessions, native SSE isolation and refresh');})().catch(e=>{console.error(e);process.exitCode=1;});
