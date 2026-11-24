const fs=require('fs'),path=require('path'),os=require('os'),{spawn}=require('child_process');
const repo=path.resolve(__dirname,'..');if(!process.env.MKA_MENU_FIXTURE)throw Error('Set MKA_MENU_FIXTURE to authenticated native HTML with styles inlined and scripts removed.');
(async()=>{let profile=fs.mkdtempSync(path.join(os.tmpdir(),'mka-menu-test-'));let chrome=spawn(process.env.CHROME_BIN||(process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':'chromium'),['--headless=new','--no-sandbox','--disable-gpu','--disable-extensions','--no-first-run','--disable-background-networking','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:['ignore','ignore','pipe']});chrome.stderr.on('data',d=>process.stderr.write(d));let ws;
try{let portfile=path.join(profile,'DevToolsActivePort');for(let i=0;!fs.existsSync(portfile)&&i<600;i++)await new Promise(r=>setTimeout(r,100));await new Promise(r=>setTimeout(r,350));let port=fs.readFileSync(portfile,'utf8').split('\n')[0];let tabs=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();ws=new WebSocket(tabs.find(t=>t.type==='page'&&t.url==='about:blank').webSocketDebuggerUrl);await new Promise(r=>ws.addEventListener('open',r,{once:true}));let next=0,pending=new Map();ws.addEventListener('message',e=>{let m=JSON.parse(e.data);if(pending.has(m.id)){let f=pending.get(m.id);pending.delete(m.id);m.error?f.reject(m.error):f.resolve(m.result);}});let call=(method,params={})=>new Promise((resolve,reject)=>{let id=++next;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});













const root=process.env.MKA_MENU_SOURCE||repo, fixture=fs.readFileSync(process.env.MKA_MENU_FIXTURE,'utf8');
for(const width of [320,390,768,1024,1360])for(const version of ['nova','legada']){
await call('Page.navigate',{url:'about:blank?'+width+'-'+version});await new Promise(r=>setTimeout(r,35));await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<1024});let tree=await call('Page.getFrameTree');
await call('Page.setDocumentContent',{frameId:tree.frameTree.frame.id,html:fixture.replace('</head>','<style>'+fs.readFileSync(root+'/addons/shared/admin_mobile.css','utf8')+'</style></head>')});
let setup="document.querySelectorAll('.is-invisible').forEach(n=>n.classList.remove('is-invisible'));window.cacheValue="+JSON.stringify(version==='nova'?'legada':'nova')+";Object.defineProperty(window,'localStorage',{value:{getItem:()=>cacheValue,setItem:(k,v)=>{cacheValue=v;}}});window.fetch=async()=>({ok:true,json:async()=>({version:"+JSON.stringify(version)+"})});";
let s=await call('Runtime.evaluate',{expression:setup+fs.readFileSync(root+'/addons/shared/admin_mobile.js','utf8')});if(s.exceptionDetails)throw Error(JSON.stringify(s.exceptionDetails));await new Promise(r=>setTimeout(r,100));
let ev=async expression=>(await call('Runtime.evaluate',{expression,returnByValue:true})).result.value;
let nav=await ev("(()=>{let n=[...document.querySelectorAll('nav.navbar.is-fixed-top')].find(n=>getComputedStyle(n).display!=='none');window.auditNav=n;window.auditOpener=n.querySelector('#navbar-mka,.navbar-burger');window.auditMenu=n.querySelector('.navbar-menu');window.auditGroups=[...n.querySelectorAll('.has-dropdown>.navbar-dropdown')];return {groups:auditGroups.length,version:document.documentElement.classList.contains('mka-mobile-menu-legacy'),page:document.documentElement.scrollWidth,viewport:document.documentElement.clientWidth};})()");
if(!nav.groups||nav.version!==(version==='legada')||nav.page>nav.viewport+1)throw Error(JSON.stringify(nav));
if(width<1024){
await call('Runtime.evaluate',{expression:"auditOpener.click()"});
let initial=await ev("({open:auditMenu.classList.contains('is-active'),visible:auditGroups.filter(n=>getComputedStyle(n).display!=='none').length,theme:getComputedStyle(auditNav).backgroundColor,svg:!!auditOpener.querySelector('svg')})");
if(!initial.open||!initial.svg||initial.visible!==(version==='legada'?nav.groups:0))throw Error('Initial groups: '+JSON.stringify(initial));
await call('Runtime.evaluate',{expression:"auditNav.querySelector('.navbar-link').click()"});
let expanded=await ev("auditGroups.filter(n=>getComputedStyle(n).display!=='none').length");
if(expanded!==(version==='legada'?nav.groups:1))throw Error('Wrong group toggle');
await call('Runtime.evaluate',{expression:"auditNav.querySelector('.navbar-link').click()"});
if(await ev("auditGroups.filter(n=>getComputedStyle(n).display!=='none').length")!==(version==='legada'?nav.groups:0))throw Error('Wrong group close behavior');
await call('Runtime.evaluate',{expression:"auditOpener.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}))"});
if(!await ev("!auditMenu.classList.contains('is-active')&&auditOpener.getAttribute('aria-expanded')==='false'"))throw Error('Escape failed');
await call('Runtime.evaluate',{expression:"auditOpener.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}))"});
if(!await ev("auditMenu.classList.contains('is-active')"))throw Error('Keyboard open failed');
let scrolled=await ev("(()=>{auditMenu.scrollTop=250;return {scroll:auditMenu.scrollTop,content:auditMenu.scrollHeight,viewport:auditMenu.clientHeight,page:document.documentElement.scrollWidth,width:document.documentElement.clientWidth};})()");
if(scrolled.page>scrolled.width+1||(scrolled.content>scrolled.viewport&&scrolled.scroll===0))throw Error('Scroll or page overflow: '+JSON.stringify(scrolled));
if(width===390){let shot=await call('Page.captureScreenshot',{format:'png'});if(process.env.MKA_MENU_SCREENSHOTS){fs.mkdirSync(process.env.MKA_MENU_SCREENSHOTS,{recursive:true});fs.writeFileSync(path.join(process.env.MKA_MENU_SCREENSHOTS,'mobile-menu-'+version+'.png'),Buffer.from(shot.data,'base64'));}}
}else{
let before=await ev("auditGroups.map(n=>getComputedStyle(n).display).join(',')");
await call('Runtime.evaluate',{expression:"auditNav.querySelector('.navbar-link').click()"});
if(await ev("auditGroups.map(n=>getComputedStyle(n).display).join(',')")!==before)throw Error('Desktop modified');
}
console.log('PASS',version,width,'mobile menu, header widths, toggle and scroll');
}
// Draft menu choices must not overwrite the browser's saved server preference.
await call('Page.navigate',{url:'about:blank?draft'});await call('Emulation.setDeviceMetricsOverride',{width:390,height:900,deviceScaleFactor:1,mobile:true});await new Promise(r=>setTimeout(r,35));let tree=await call('Page.getFrameTree');await call('Page.setDocumentContent',{frameId:tree.frameTree.frame.id,html:fixture});
let draftSource=fs.readFileSync(root+'/addons/shared/admin_mobile.js','utf8').replace("new URLSearchParams(location.search).get('preview')","'0123456789abcdef0123456789abcdef'");
const previewRuntime=fs.readFileSync(root+'/addons/dashboard/preview_runtime.php','utf8').match(/<script>([\s\S]*?)<\/script>/)[1].replace('<?=json_encode($dashboardPreviewToken)?>',JSON.stringify('0123456789abcdef0123456789abcdef')).replaceAll('location.href',JSON.stringify('http://example.test/admin/addons/dashboard/')).replaceAll('location.origin',JSON.stringify('http://example.test'));
await call('Runtime.evaluate',{expression:"window.cacheValue='nova';window.preferenceURL='';Object.defineProperty(window,'localStorage',{value:{getItem:()=>cacheValue,setItem:(k,v)=>cacheValue=v}});window.fetch=async url=>{preferenceURL=url;return {ok:true,json:async()=>({version:'legada'})};};"+previewRuntime+draftSource});await new Promise(r=>setTimeout(r,100));
let draft=await call('Runtime.evaluate',{expression:"document.documentElement.classList.contains('mka-mobile-menu-legacy')&&cacheValue==='nova'&&preferenceURL.includes('preview=0123456789abcdef0123456789abcdef')",returnByValue:true});if(!draft.result.value)throw Error('Draft leaks into saved mode');await call('Runtime.evaluate',{expression:"document.querySelector('#navbar-mka').click();window.previewActionRun=0;var forbidden=document.createElement('button');forbidden.type='button';forbidden.onclick=()=>previewActionRun++;document.body.append(forbidden);forbidden.click();"});
let interactive=await call('Runtime.evaluate',{expression:"document.querySelector('.navbar-menu').classList.contains('is-active')&&previewActionRun===0",returnByValue:true});if(!interactive.result.value)throw Error('Preview menu blocked or unsafe actions permitted');
console.log('PASS preview menu without persistent browser preference changes');

await call('Page.navigate',{url:'about:blank?old-cache'});await new Promise(r=>setTimeout(r,35));await call('Emulation.setDeviceMetricsOverride',{width:390,height:900,deviceScaleFactor:1,mobile:true});tree=await call('Page.getFrameTree');await call('Page.setDocumentContent',{frameId:tree.frameTree.frame.id,html:fixture.replace('</head>','<style>'+fs.readFileSync(root+'/addons/shared/admin_mobile.css','utf8')+'</style></head>')});
const legacySetup="window.mkaAdminMobileLoaded=true;window.oldHandlerCalls=0;Object.defineProperty(window,'localStorage',{value:{getItem:()=> 'nova',setItem:()=>{}}});var oldNav=document.querySelector('nav.navbar.is-fixed-top');oldNav.dataset.mkaMobileBound='1';var oldButton=oldNav.querySelector('#navbar-mka');oldButton.append(document.createElementNS('http://www.w3.org/2000/svg','svg'));oldNav.addEventListener('click',function(){oldHandlerCalls++;},true);oldNav.addEventListener('keydown',function(){oldHandlerCalls++;});var oldCSS=document.createElement('link');oldCSS.id='mka-admin-mobile-css';oldCSS.href='/admin/addons/shared/admin_mobile.css?v=old';document.head.append(oldCSS);window.fetch=async()=>({ok:true,json:async()=>({version:'legada'})});";
await call('Runtime.evaluate',{expression:legacySetup+fs.readFileSync(root+'/addons/shared/admin_mobile.js','utf8')});await new Promise(r=>setTimeout(r,100));await call('Runtime.evaluate',{expression:"oldButton.click();oldButton.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));"});
let upgraded=await call('Runtime.evaluate',{expression:"document.documentElement.classList.contains('mka-mobile-menu-legacy')&&oldHandlerCalls===0&&oldButton.querySelectorAll('svg').length===1&&!oldNav.querySelector('.navbar-menu').classList.contains('is-active')&&document.getElementById('mka-admin-mobile-css').getAttribute('href').includes('20261124-1')",returnByValue:true});if(!upgraded.result.value)throw Error('Cached mobile controller not upgraded safely');console.log('PASS upgrade of cached controller, CSS and handlers');
}finally{if(ws)ws.close();chrome.kill();}})().catch(e=>{console.error(e);process.exitCode=1});
