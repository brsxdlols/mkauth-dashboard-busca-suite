const fs=require('fs'),path=require('path'),os=require('os'),{spawn}=require('child_process');
const repo=path.resolve(__dirname,'..');
if(!process.env.MKA_NOVA_FIXTURE||!process.env.MKA_MULTI_FIXTURE)throw Error('Set MKA_NOVA_FIXTURE and MKA_MULTI_FIXTURE to authenticated dashboard HTML with styles inlined and scripts removed.');
(async()=>{let profile=fs.mkdtempSync(path.join(os.tmpdir(),'mka-mobile-layout-'));let chrome=spawn(process.env.CHROME_BIN||(process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':'chromium'),['--headless=new','--no-sandbox','--disable-gpu','--disable-extensions','--no-first-run','--disable-background-networking','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:['ignore','ignore','pipe']});chrome.stderr.on('data',d=>process.stderr.write(d));let ws;
try{let portfile=path.join(profile,'DevToolsActivePort');for(let i=0;!fs.existsSync(portfile)&&i<600;i++)await new Promise(r=>setTimeout(r,100));await new Promise(r=>setTimeout(r,350));let port=fs.readFileSync(portfile,'utf8').split('\n')[0];let tabs=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();ws=new WebSocket(tabs.find(t=>t.type==='page'&&t.url==='about:blank').webSocketDebuggerUrl);await new Promise(r=>ws.addEventListener('open',r,{once:true}));let next=0,pending=new Map();ws.addEventListener('message',e=>{let m=JSON.parse(e.data);if(pending.has(m.id)){let f=pending.get(m.id);pending.delete(m.id);m.error?f.reject(m.error):f.resolve(m.result);}});let call=(method,params={})=>new Promise((resolve,reject)=>{let id=++next;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});













const sourceRoot=process.env.MKA_DASHBOARD_SOURCE||path.join(repo,'addons/dashboard');
const indexSource=fs.readFileSync(path.join(sourceRoot,'index.php'),'utf8'),multiSource=fs.readFileSync(path.join(sourceRoot,'multi.php'),'utf8');
const critical=fs.readFileSync(path.join(sourceRoot,'mobile_layout.php'),'utf8').match(/<style[^>]*>([\s\S]*?)<\/style>/)[1];
const novaCSS=indexSource.match(/<style>([\s\S]*?)<\/style>/)[1];
for(const source of [indexSource,multiSource]){
if(!source.includes("hash_file('sha256',__DIR__.'/dashboard_density.css')")||!source.includes("include __DIR__.'/mobile_layout.php'"))throw Error('Missing content hash or critical layout include');
}
if(indexSource.includes('left: -15px')||indexSource.includes('width: calc(100vw - 16px)'))throw Error('Obsolete fixed offset remains');
for(const mode of ['nova','multi'])for(const cache of ['fresh','stale'])for(const width of [320,369,375,390,400,600,767,768,1024,1360]){
await call('Page.navigate',{url:'about:blank'});await new Promise(r=>setTimeout(r,35));await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<900});let tree=await call('Page.getFrameTree');
let html=fs.readFileSync(mode==='nova'?process.env.MKA_NOVA_FIXTURE:process.env.MKA_MULTI_FIXTURE,'utf8');
await call('Page.setDocumentContent',{frameId:tree.frameTree.frame.id,html});
const setup="document.documentElement.classList.add('mka-mobile-shell','has-navbar-fixed-top');document.querySelectorAll('.is-invisible').forEach(e=>e.classList.remove('is-invisible'));document.querySelectorAll('[data-finance-due],[data-finance-cash]').forEach(e=>e.hidden=false);";
let alteration=mode==='nova'?"[...document.querySelectorAll('style')].filter(s=>s.textContent.includes('.dashboard-stat-grid')&&s.textContent.includes('.dashboard-session-toast')).forEach(s=>s.textContent="+JSON.stringify(novaCSS)+");":"";
if(cache==='stale')alteration+="[...document.querySelectorAll('style')].filter(s=>s.textContent.includes('.mka-overview-pair')).forEach(s=>{let pos=s.textContent.indexOf(' body.mka-suite-dashboard-page #sistema-corpo2{width:100%');if(pos>=0)s.textContent=s.textContent.slice(0,s.textContent.lastIndexOf('@media(',pos));});";
let setupResult=await call('Runtime.evaluate',{expression:setup+alteration+"var fixedStyle=document.createElement('style');fixedStyle.textContent="+JSON.stringify(critical)+";document.head.append(fixedStyle);"+fs.readFileSync(path.join(repo,'addons/dashboard/dashboard_density.js'),'utf8')});
if(setupResult.exceptionDetails)throw Error(JSON.stringify(setupResult.exceptionDetails));
let result=await call('Runtime.evaluate',{expression:"(()=>{let viewport=document.documentElement.clientWidth;let selectors=['.dashboard-portal-search-row','.multi-portal-search-row','[data-finance-today]','.mka-operation-card','.mka-growth','.mka-overview-pair'];let cards=selectors.flatMap(s=>[...document.querySelectorAll(s)]).filter(n=>n.getBoundingClientRect().width>0);let bounds=cards.map(n=>{let r=n.getBoundingClientRect();return {name:n.className,left:r.left,right:r.right,balance:Math.abs(r.left-(viewport-r.right))};});let table=document.querySelector('.mka-operation-table');let tableScroll=null;if(table){table.scrollLeft=80;tableScroll={scroll:table.scrollLeft,width:table.clientWidth,content:table.scrollWidth};}return {mode:"+JSON.stringify(mode)+",cache:"+JSON.stringify(cache)+",width:innerWidth,viewport,page:document.documentElement.scrollWidth,bounds,tableScroll};})()",returnByValue:true});
if(result.exceptionDetails)throw Error(JSON.stringify(result.exceptionDetails));let value=result.result.value;
if(value.page>value.viewport+1){let details=await call('Runtime.evaluate',{expression: "['html','body','#container-sistema','#sistema-corpo2','#principal','.mka-suite-dashboard-start'].map(s=>{let n=document.querySelector(s),c=getComputedStyle(n),r=n.getBoundingClientRect();return {s,width:r.width,csswidth:c.width,min:c.minWidth,max:c.maxWidth,grid:c.gridTemplateColumns}})",returnByValue:true});throw Error('Page overflow: '+JSON.stringify(value)+' '+JSON.stringify(details.result.value));}
if(width<768&&value.bounds.some(r=>r.balance>1||r.left<0||r.right>value.viewport+1))throw Error('Unbalanced mobile layout: '+JSON.stringify(value));
if(width<768&&value.tableScroll&&value.tableScroll.content>value.tableScroll.width&&value.tableScroll.scroll<1)throw Error('Table cannot scroll independently');
if(width===390&&cache==='stale'){let shot=await call('Page.captureScreenshot',{format:'png'});if(process.env.MKA_MOBILE_SCREENSHOTS){fs.mkdirSync(process.env.MKA_MOBILE_SCREENSHOTS,{recursive:true});fs.writeFileSync(path.join(process.env.MKA_MOBILE_SCREENSHOTS,'dashboard-centered-'+mode+'.png'),Buffer.from(shot.data,'base64'));}}
console.log('PASS',mode,cache,width,'page',value.page,'max imbalance',Math.max(...value.bounds.map(r=>r.balance)));
}
}finally{if(ws)ws.close();chrome.kill();}})().catch(e=>{console.error(e);process.exitCode=1});
