const fs=require('fs'),path=require('path'),os=require('os'),{spawn}=require('child_process'),assert=require('assert');
(async()=>{
 const root=path.resolve(__dirname,'..'),source=fs.readFileSync(path.join(root,'addons/shared/ramal_sync_help.js'),'utf8'),css=fs.readFileSync(path.join(root,'addons/shared/ramal_sync_help.css'),'utf8');
 const profile=fs.mkdtempSync(path.join(os.tmpdir(),'ramal-help-browser-')),chrome=spawn(process.env.CHROME_BIN||(process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':'google-chrome'),['--headless=new','--no-sandbox','--disable-gpu','--disable-extensions','--no-first-run','--disable-background-networking','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:['ignore','ignore','pipe']});let ws;
 try{
  const portFile=path.join(profile,'DevToolsActivePort');for(let i=0;!fs.existsSync(portFile)&&i<150;i++)await new Promise(r=>setTimeout(r,100));
  const port=fs.readFileSync(portFile,'utf8').split('\n')[0],tabs=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();ws=new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl);await new Promise(r=>ws.addEventListener('open',r,{once:true}));let next=0,pending=new Map();
  ws.addEventListener('message',event=>{const message=JSON.parse(event.data);if(pending.has(message.id)){const entry=pending.get(message.id);pending.delete(message.id);message.error?entry.reject(message.error):entry.resolve(message.result);}});
  const call=(method,params={})=>new Promise((resolve,reject)=>{const id=++next;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
  const evaluate=async expression=>{const result=await call('Runtime.evaluate',{expression,returnByValue:true});if(result.exceptionDetails)throw Error(JSON.stringify(result.exceptionDetails));return result.result.value;};
  for(const width of [1360,390]){
   await call('Page.navigate',{url:'about:blank?ramal='+width});await new Promise(r=>setTimeout(r,60));await call('Emulation.setDeviceMetricsOverride',{width,height:800,deviceScaleFactor:1,mobile:width<500});
   const frame=(await call('Page.getFrameTree')).frameTree.frame.id;
   await call('Page.setDocumentContent',{frameId:frame,html:'<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+css+'</style><h1>Sincronização de Ramal precisa de atenção</h1><div id="results"></div>'});
   await evaluate('window.networkCalls=0;window.fetch=()=>{networkCalls++;throw Error("Tutorial must not request any change");};');await evaluate(source);await evaluate(source);
   const rows=[{name:'RAMAL CENTRAL',router:'172.16.0.1',reason:'Usuário ou senha da API inválidos',ok:false},{name:'RAMAL PORTA',router:'172.16.0.2',reason:'Porta 8728 fechada ou indisponível',ok:false},{name:'RAMAL VPN',router:'172.16.0.3',reason:'Sem ping ou sem rota até o IP',ok:false},{name:'<img src=x onerror="window.evil=1">',router:'<script>bad</script>',reason:'Falha <script>window.evil=1</script>',ok:false},{name:'OK',router:'172.16.0.4',reason:'Acesso à API validado',ok:true}];
   await evaluate('document.getElementById("results").innerHTML='+JSON.stringify(rows)+'.map(r=>mkaRamalSyncHelp.reasonHtml(r)).join("<hr>")');
   assert.equal(await evaluate('document.querySelectorAll("[data-mka-ramal-help]").length'),4);
   assert(await evaluate('document.querySelector("[data-mka-ramal-help=credentials]").title.includes("System → Users")'));
   await evaluate('document.querySelector("[data-mka-ramal-help=credentials]").click()');
   let data=await evaluate('({text:document.getElementById("mka-ramal-help-dialog").textContent,steps:document.querySelectorAll("#mka-ramal-help-dialog li").length,width:document.getElementById("mka-ramal-help-dialog").getBoundingClientRect().width,viewport:innerWidth,networkCalls})');
   for(const expected of ['RAMAL CENTRAL','172.16.0.1','Provedor → Ramais','System → Users','8728','Repetir teste'])assert(data.text.includes(expected),expected);
   assert.equal(data.steps,5);assert(data.width<=data.viewport);assert.equal(data.networkCalls,0);
   if(process.env.MKA_RAMAL_SCREENSHOT_DIR){fs.mkdirSync(process.env.MKA_RAMAL_SCREENSHOT_DIR,{recursive:true});const screenshot=await call('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(process.env.MKA_RAMAL_SCREENSHOT_DIR,'ramal-help-'+width+'.png'),Buffer.from(screenshot.data,'base64'));}
   await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await new Promise(r=>setTimeout(r,50));
   assert(await evaluate('!document.getElementById("mka-ramal-help-dialog")&&document.activeElement.matches("[data-mka-ramal-help=credentials]")'));
   for(const kind of ['port','route','general']){await evaluate('document.querySelector("[data-mka-ramal-help='+kind+']").click()');const actual=await evaluate('({text:document.getElementById("mka-ramal-help-dialog").textContent,unsafe:!!document.querySelector("#mka-ramal-help-dialog img,#mka-ramal-help-dialog script"),evil:!!window.evil})');assert(!actual.unsafe&&!actual.evil);if(kind==='route')assert(actual.text.includes('VPN'));await evaluate('document.querySelector(".mka-ramal-help-done").click()');await new Promise(r=>setTimeout(r,30));}
   console.log('PASS hover guidance, contextual dialogs, escaping, Escape/focus and no network calls',width);
  }
 }finally{if(ws)ws.close();chrome.kill();}
})().catch(error=>{console.error(error);process.exitCode=1;});
