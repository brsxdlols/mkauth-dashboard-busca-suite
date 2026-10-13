// Run with Node. Exercise the actual modal script with a small DOM and HTTP fixture.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, '../addons/busca_inteligente/index.php'), 'utf8');
const script = source.match(/<script>\s*(\(function\s*\(\)\s*\{\s*var modal=document.getElementById\('manualBlockModal'\)[\s\S]*?)<\/script>/)[1].replace(/<\?= \$ext_mk; \?>/g, '.hhvm');
async function run(action, failure=false) {
  const nodes = {};
  function node(id) { return nodes[id] ||= {value:'', hidden:true, style:{}, classList:{toggle(){}}, addEventListener(event,fn){this[event]=fn;}, focus(){}, querySelector(selector){return node(selector);}}; }
  const requests=[]; let reloads=0;
  const context = {URLSearchParams, document:{getElementById:node, body:{style:{}}, createElement(){return {set textContent(v){this.innerHTML=v;}};}, addEventListener(){}}, window:{location:{reload(){reloads++;}}}, fetch:async (url,options={})=>{
    requests.push([url,options]);
    if(url.includes('executar_bloqueio')) return {ok:!failure,text:async()=>''};
    return {ok:true,json:async()=>({ok:true,csrf:'fixture'})};
  }};
  vm.runInNewContext(script,context);
  context.window.mkaRunClientBlock('01234567-0123-0123-0123-012345678901','Cliente teste',action);
  assert.equal(node('manualBlockModalTitle').textContent,action==='bloqueio'?'Bloquear cliente':'Desbloquear cliente');
  assert.match(node('manualBlockNote').textContent,action==='bloqueio'?/Recursos de sistema/:/desbloqueio de confiança/);
  node('manualBlockReason').value='Teste da operação';
  node('manualBlockForm').submit({preventDefault(){}});
  for(let n=0;n<8;n++) await new Promise(resolve=>setImmediate(resolve));
  const native=requests.filter(([url])=>url.includes('executar_bloqueio'));
  assert.equal(native.length,1);
  assert.ok(native[0][0].includes('acao='+action));
  const confirms=requests.filter(([,options])=>options.body && new URLSearchParams(options.body).get('phase')==='confirm');
  assert.equal(confirms.length,failure?0:1);
  assert.equal(reloads,failure?0:1);
  if(failure) assert.equal(node('manualBlockSubmit').disabled,false);
  console.log('PASS',action,failure?'native failure':'native success');
}
(async()=>{await run('bloqueio');await run('desbloqueio');await run('bloqueio',true);})().catch(error=>{console.error(error);process.exitCode=1;});
