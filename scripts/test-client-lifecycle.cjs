const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../addons/shared/client_lifecycle.js'),'utf8');
function fixture(action){
 let click,submitted=[],events={},reloaded=false;
 function node(tag){return {tag,style:{},children:[],events:{},append(...x){this.children.push(...x)},setAttribute(){},remove(){},focus(){},addEventListener(n,f){this.events[n]=f},showModal(){this.open=true},close(){this.open=false;this.events.close()},submit(){submitted.push(this)}}}
 const reason={value:'',setCustomValidity(v){this.invalid=v},reportValidity(){this.reported=true},addEventListener(n,fn){events[n]=fn}};
 const nativeDoc={body:{scrollHeight:340},head:{append(){}},createElement:node,querySelector(){return reason},addEventListener(n,f){events[n]=f}};
 let frame;
 const document={body:node('body'),addEventListener(n,f){click=f},createElement(t){const n=node(t);if(t==='iframe'){frame=n;n.contentWindow={location:{pathname:'/admin/'+(action==='disable'?'cliente_onoff':'cliente_del')+'.hhvm'}};n.contentDocument=nativeDoc;}return n;}};
 const window={mkaWatchClientAction(){return function(){}},location:{href:'https://example/admin/addons/busca_inteligente/index.php',reload(){reloaded=true}}};
 vm.runInNewContext(source,{window,document,location:{origin:'https://example'},URL,Date,RegExp});
 const attrs={'data-client-lifecycle':action,'data-client-login':'one-client','data-client-name':'One','data-client-native-url':'/admin/'+(action==='disable'?'cliente_onoff':'cliente_del')+'.hhvm'};
 click({preventDefault(){},target:{closest(){return {focus(){},getAttribute(k){return attrs[k]}}}}});
 return {submitted,frame,reason,events,document};
}
for(const action of ['disable','delete']){
 const f=fixture(action);const dialog=f.document.body.children[0];assert.equal(dialog.tag,'dialog');assert.equal(dialog.open,true);
 assert.equal(f.submitted.length,1);const form=f.submitted[0];assert.equal(form.method,'POST');assert.equal(form.target,f.frame.name);assert.equal(form.children.length,1);assert.equal(form.children[0].name,'login[]');assert.equal(form.children[0].value,'one-client');assert.ok(!form.action.includes('executar_'));
 f.frame.events.load();assert.equal(f.reason.required,true);let blocked=false;f.events.submit({preventDefault(){blocked=true},stopImmediatePropagation(){}});assert.equal(blocked,true);
 assert.match(dialog.children[1].textContent,action==='disable'?/financeiro permanece/:/Excluir remove/);
 f.reason.value='Requested cancellation';blocked=false;f.events.submit({preventDefault(){blocked=true},stopImmediatePropagation(){}});assert.equal(blocked,false);
 assert.equal(f.frame.style.height,'360px');dialog.close();assert.equal(f.document.body.style.overflow,undefined);
}
console.log('PASS modal, one-client native preparation, reason validation, financial notice, frame sizing and close. No client changed.');
