import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const native=fs.readFileSync(process.argv[2] || '/opt/mk-auth/admin/scripts/mk-auth.js','utf8');
const cpf=native.slice(native.indexOf('const valida_cpfcnpj ='),native.indexOf('const valida_mac ='));
const verify=native.slice(native.indexOf('function verifica_form(form)'),native.indexOf('// ALERTA TOPO DO SISTEMA'));
const patch=fs.readFileSync(new URL('../policy.js', import.meta.url),'utf8');
function run(allow,doc,name){
 const field=(n,v,valid)=>({value:v,attrs:{name:n,type:'text',df_verificar:'sim',df_validar:valid},classList:{remove(){}},getAttribute(n){return this.attrs[n]??null},setAttribute(n,v){this.attrs[n]=v},removeAttribute(n){delete this.attrs[n]}});
 const cpfInput=field('cpf_cnpj',doc,'receita'),nameInput=field('nome',name,'nao');
 const form=[nameInput,cpfInput];form.nome=nameInput;form.cpf_cnpj=cpfInput;form.querySelector=()=>cpfInput;
 const context={console,document:{readyState:'complete',getElementById:id=>id==='cpf_cnpj'?cpfInput:null},mkaToast:{toast(){}},nnCpfPolicy:{mode:'cliente_alt',allowEmpty:allow},jQuery:()=>({removeClass(){},addClass(){},focus(){}})};context.window=context;vm.createContext(context);
 vm.runInContext(cpf+verify,context);vm.runInContext(patch,context);
 const result=context.verifica_form(form);
 assert.equal(cpfInput.attrs.df_verificar,'sim','Restore native validation attribute');
 return result;
}
assert.equal(run(true,'','Cliente'),true);
assert.equal(run(true,'   ','Cliente'),true);
assert.equal(run(false,'','Cliente'),false);
assert.equal(run(true,'11111111111','Cliente'),false);
assert.equal(run(true,'11144477735','Cliente'),true);
assert.equal(run(true,'',''),false);
console.log('FRONTEND_APROVADO: vazio permitido; vazio bloqueado; documento invalido rejeitado; documento valido aceito; demais obrigatorios preservados.');
