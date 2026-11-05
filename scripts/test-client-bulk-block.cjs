const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../addons/shared/client_bulk_block.js'),'utf8');
function fixture(action='bloqueio',options={}){
 const listeners={},requests=[],states=new Map(),nodes=[];let reloads=0;
 class Node{
  constructor(tag,text=''){this.tag=tag;this.textContent=text;this.children=[];this.attrs={};this.events={};this.style={};this.value='';this.disabled=false;this.checked=false;nodes.push(this);}
  append(...n){this.children.push(...n);}setAttribute(k,v){this.attrs[k]=v;}getAttribute(k){return this.attrs[k];}
  addEventListener(k,f){this.events[k]=f;}focus(){}reportValidity(){return false;}showModal(){this.open=true;}close(){this.open=false;if(this.events.close)this.events.close();}remove(){}
  closest(selector){return this.attrs[selector.slice(1,-1)]!==undefined?this:null;}
 }
 const boxes=(options.boxes||[false,true]).map((blocked,i)=>{
  const n=new Node('input');n.checked=true;n.value='client'+i;n.attrs={'data-client-uuid':String(i).padStart(36,'0'),'data-client-name':'Client '+i,'data-client-blocked':blocked?'sim':'nao'};states.set(n.attrs['data-client-uuid'],blocked);return n;
 });
 const body=new Node('body'),counter=new Node('span'),master=new Node('input'),trigger=new Node('button');
 trigger.attrs={'data-bulk-client-block':action,'data-native-block-url':'../../executar_bloqueio.'+(options.extension||'hhvm')};
 const location={href:'https://example/admin/addons/busca_inteligente/index.php?busca=bloq&pagina=2',origin:'https://example',reload(){reloads++;}};
 const document={body,querySelectorAll(s){return s.startsWith('.login_select')?boxes:s==='[data-bulk-selected]'?[counter]:[];},getElementById(){return master;},createElement:t=>new Node(t),addEventListener(k,f){listeners[k]=f;}};
 const fetch=async(url,opts={})=>{
  url=String(url);requests.push([url,opts]);
  if(url.includes('executar_bloqueio')){
   const uuid=new URL(url).searchParams.get('uuid_cliente');
   if(options.failNative===uuid)return {ok:false,text:async()=>''};
   states.set(uuid,action==='bloqueio');return {ok:true,text:async()=>''};
  }
  if(!opts.method){
   const uuid=new URL(url,'https://example/').searchParams.get('uuid');
   return {ok:!options.denied,json:async()=>options.denied?{ok:false,message:'Acesso negado'}:{ok:true,csrf:'fixture',blocked:states.get(uuid)}};
  }
  const p=new URLSearchParams(opts.body);
  if(p.get('phase')==='confirm')assert.equal(states.get(p.get('uuid')),action==='bloqueio');
  assert.equal(p.get('csrf'),'fixture');assert.equal(p.get('reason'),'Motivo de teste');
  return {ok:true,json:async()=>({ok:true})};
 };
 vm.runInNewContext(source,{document,location,fetch,URL,URLSearchParams,Set,Array,encodeURIComponent});
 function click(n){listeners.click({target:n,preventDefault(){}});}
 function open(){click(trigger);return body.children.at(-1);}
 function controls(){return {confirm:nodes.find(n=>n.className==='mka-bulk-confirm'),reason:nodes.find(n=>n.id==='mka-bulk-reason'),cancel:nodes.find(n=>n.tag==='button'&&n.textContent==='Cancelar')};}
 return {requests,boxes,open,controls,click,Node,nodes,reloads:()=>reloads,counter};
}
(async()=>{
 let f=fixture();f.open();assert.equal(f.requests.length,0);f.controls().cancel.events.click();assert.equal(f.requests.length,0);
 for(const action of ['bloqueio','desbloqueio']){
  f=fixture(action,{extension:action==='bloqueio'?'hhvm':'php'});f.open();let c=f.controls();c.reason.value='Motivo de teste';const pending=c.confirm.events.click();c.confirm.events.click();await pending;
  const native=f.requests.filter(([u])=>u.includes('executar_bloqueio'));
  assert.equal(native.length,1);assert.equal(new URL(native[0][0]).searchParams.get('acao'),action);assert.equal(f.reloads(),1);
  assert.equal(f.requests.filter(([,o])=>new URLSearchParams(o.body).get('phase')==='confirm').length,1);
 }
 f=fixture('bloqueio',{boxes:[false,false,false],failNative:String(1).padStart(36,'0')});f.open();f.controls().reason.value='Motivo de teste';await f.controls().confirm.events.click();
 assert.equal(f.requests.filter(([u])=>u.includes('executar_bloqueio')).length,2);assert.equal(f.reloads(),0);assert(f.controls().confirm.hidden);assert(f.nodes.some(n=>n.textContent.includes('Não houve repetição automática')));
 f=fixture('bloqueio',{denied:true});f.open();f.controls().reason.value='Motivo de teste';await f.controls().confirm.events.click();assert(!f.requests.some(([u])=>u.includes('executar_bloqueio')));
 f=fixture();f.boxes.forEach(b=>b.checked=false);let select=new f.Node('button');select.attrs['data-bulk-select']='';f.click(select);assert(f.boxes.every(b=>b.checked));assert.equal(f.counter.textContent,'2 selecionados');
 console.log('PASS explicit selection/confirmation; native PHP/HHVM; CSRF audit; skip existing state; stop on error without retry; cancel makes no requests');
})().catch(e=>{console.error(e);process.exitCode=1;});
