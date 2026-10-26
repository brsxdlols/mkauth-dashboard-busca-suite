const fs=require('fs'),vm=require('vm'),assert=require('assert');
let handler,submitted=0,created=[];let allow=false;
const button={getAttribute:k=>({'data-activate-client':'one@example.com','data-activate-name':'One','data-activate-url':'../../executar_ativacao.hhvm?acao=ativacao'})[k]};
const c={window:{confirm:()=>allow},document:{addEventListener:(t,h)=>handler=h,createElement:tag=>({tag,children:[],append(x){this.children.push(x)},submit(){submitted++;assert.equal(this.method,'POST');assert.equal(this.children.length,1);assert.equal(this.children[0].name,'login[]');assert.equal(this.children[0].value,'one@example.com')}}),body:{append:f=>created.push(f)}}};
vm.runInNewContext(fs.readFileSync('addons/shared/client_activation.js','utf8'),c);
const event={target:{closest:()=>button},preventDefault(){}};
handler(event);assert.equal(submitted,0);allow=true;handler(event);assert.equal(submitted,1);assert.equal(created.length,1);
console.log('Activation confirmation and isolated single-login native POST passed.');
