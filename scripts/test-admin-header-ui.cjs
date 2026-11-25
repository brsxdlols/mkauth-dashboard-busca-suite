const fs=require('fs'),path=require('path'),{spawn}=require('child_process');
(async()=>{let profile=path.resolve('work/chrome-global-header-'+Date.now());let chrome=spawn(process.env.CHROME_BIN||(process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':'chromium'),['--headless=new','--no-sandbox','--disable-gpu','--disable-extensions','--no-first-run','--disable-background-networking','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:['ignore','ignore','pipe']});chrome.stderr.on('data',d=>process.stderr.write(d));let ws;
try{let portfile=path.join(profile,'DevToolsActivePort');for(let i=0;!fs.existsSync(portfile)&&i<600;i++)await new Promise(r=>setTimeout(r,100));await new Promise(r=>setTimeout(r,350));let port=fs.readFileSync(portfile,'utf8').split('\n')[0];let tabs=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();ws=new WebSocket(tabs.find(t=>t.type==='page'&&t.url==='about:blank').webSocketDebuggerUrl);await new Promise(r=>ws.addEventListener('open',r,{once:true}));let next=0,pending=new Map();ws.addEventListener('message',e=>{let m=JSON.parse(e.data);if(pending.has(m.id)){let f=pending.get(m.id);pending.delete(m.id);m.error?f.reject(m.error):f.resolve(m.result);}});let call=(method,params={})=>new Promise((resolve,reject)=>{let id=++next;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});














const root=path.resolve(__dirname,'..');
const nativeCss=fs.readFileSync(process.env.MKA_NATIVE_CSS,'utf8'),css=fs.readFileSync(path.join(root,'addons/shared/admin_header.css'),'utf8'),source=fs.readFileSync(path.join(root,'addons/shared/admin_header.js'),'utf8');
let evaluate=async expression=>{let r=await call('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
for(const width of [1360,1024,390]){
await call('Page.navigate',{url:'about:blank?header='+width});await new Promise(r=>setTimeout(r,75));await call('Emulation.setDeviceMetricsOverride',{width,height:650,deviceScaleFactor:1,mobile:width<600});let frame=await call('Page.getFrameTree');
const html='<style>'+nativeCss+'\n'+css+'</style><body style="padding:24px"><div class="level is-mobile" id="systopo"><div class="level-left"><div class="level-item"><div style="width:90px;height:70px">Logo</div></div></div><div class="level-item"><div><p><span class="is-size-7 has-text-weight-bold" data-label="provider">MK-AUTH 26.03 # TUX 6.12</span><br><span class="subtitle is-6" id="UUE_NProvedor">PROVEDOR DE TELECOMUNICACAO LTDA</span><br><span class="is-size-7">RAM: 40% CPU: 10%</span></p></div></div><div class="level-item is-hidden-mobile"><div><p><span class="is-size-7 has-text-weight-bold" data-label="user">USUÁRIO</span><br><span class="subtitle is-6">bruno</span><br><span class="is-size-7" id="run_open_sessoes"><i class="bi-pc-display-horizontal"></i><span class="has-text-weight-bold">Sessões:</span><span id="UUE_SESSOES">01</span></span></p></div></div><div class="level-item is-hidden-mobile" id="access-column"><div><p><span class="is-size-7 has-text-weight-bold" data-label="access">ACESSO ATUAL # ANTERIOR</span><br><span class="subtitle is-6" id="UUE_Data">25/11 09:53 # 25/11 09:21</span><br><span class="is-size-7">IP: 192.0.2.1 NET: ONLINE</span></p></div></div></div></body>';
await call('Page.setDocumentContent',{frameId:frame.frameTree.frame.id,html});
await evaluate("window.opened=[];window.open=(url)=>opened.push(url);window.gets=0;window.mkaAdminHeaderInitialCounts={users:3,my_sessions:1,session_url:'/admin/sessoes.php'};window.fetch=async()=>{gets++;return {ok:true,json:async()=>({users:3,my_sessions:1,session_url:'/admin/sessoes.php'})}};");
await evaluate(source);await evaluate(source);await new Promise(r=>setTimeout(r,60));
let result=await evaluate("({rows:document.querySelectorAll('.mka-admin-session-row').length,users:document.getElementById('mkaDashboardWebSessions')?.textContent,mine:document.getElementById('mkaDashboardWebSessionsTotal')?.textContent,native:!!document.getElementById('UUE_SESSOES'),gets})");
if(result.rows!==2||result.users!=='03'||result.mine!=='01'||result.native||result.gets!==1)throw Error(JSON.stringify(result));
await evaluate("document.querySelector('[data-mka-session-view=users]').click();document.querySelector('[data-mka-session-view=mine]').click()");let urls=await evaluate('opened');if(!urls[0].includes('/admin/sessoes.php?scope=all')||!urls[1].includes('/admin/sessoes.php?scope=mine'))throw Error(JSON.stringify(urls));
await evaluate("let native=document.getElementById('UUE_SESSOES');if(native)native.textContent='09';var col=document.createElement('div');col.className='level-item is-hidden-mobile';col.id='mka-server-clock-column';col.innerHTML='<div style=\"white-space:nowrap\"><span class=\"is-size-7 has-text-weight-bold\" data-label=\"clock\">HORÁRIO DO SERVIDOR</span><span class=\"subtitle is-6\" style=\"display:block;margin:0;white-space:nowrap\">25/11/2026, 09:54:09</span></div>';document.getElementById('access-column').before(col);");
if(width>=769){const tops=await evaluate("[...document.querySelectorAll('[data-label]')].map(n=>n.getBoundingClientRect().top)");if(Math.max(...tops)-Math.min(...tops)>1)throw Error(JSON.stringify(tops));}
if(!await evaluate("document.getElementById('mkaDashboardWebSessions').textContent==='03'&&document.getElementById('mkaDashboardWebSessionsTotal').textContent==='01'"))throw Error('Native event changed shared counters');
console.log('PASS shared native header, unique loader, distinct counters/routes, SSE isolation and top alignment',width);
}
}finally{if(ws)ws.close();chrome.kill();}})().catch(e=>{console.error(e);process.exitCode=1;});
