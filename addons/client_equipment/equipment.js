(function(){'use strict';
if(window.mkaEquipmentLoaded)return;window.mkaEquipmentLoaded=true;
if(!/\/cliente_(alt|ins)\.(hhvm|php)$/.test(location.pathname))return;
function el(tag,text){var n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}
function start(){
var nativeSelect=document.querySelector('select[name="equipamento"]'),serial=document.querySelector('[name="mac_serial"]');if(!nativeSelect||!serial)return;
var uuid=new URLSearchParams(location.search).get('uuid'),box=el('section');box.className='mka-equipment';box.setAttribute('aria-label','Equipamentos do cliente');
box.append(el('h3','Equipamentos do cliente'));
var nativeRow=serial.closest('.field.is-horizontal')||serial.parentElement;nativeRow.insertAdjacentElement('afterend',box);
if(!uuid){box.append(el('p','Grave o cadastro do cliente e abra a edição para vincular equipamentos do estoque.'));return;}
box.append(el('p','Cada linha representa uma unidade. Salvar equipamentos baixa as novas unidades e devolve as removidas ao estoque.'));
box.append(el('p','No contrato, use %equipamentos% para listar equipamento, MAC/serial e comodato.'));
var equipmentRow=nativeSelect.closest('.field.is-horizontal');
if(equipmentRow&&nativeRow!==equipmentRow){var legacy=el('details');legacy.append(el('summary','Equipamento do cadastro anterior (sem baixa retroativa)'));legacy.append(el('p','Este cadastro é preservado. Para controlar unidades pelo estoque, adicione os produtos na lista abaixo.'));legacy.append(equipmentRow,nativeRow);box.append(legacy);}
var list=el('div');list.className='mka-equipment-list';var status=el('p');status.className='mka-equipment-status';status.setAttribute('role','status');
var emptyCatalog=el('p');emptyCatalog.hidden=true;emptyCatalog.append(el('span','Nenhum produto cadastrado no estoque. '));var stockLink=el('a','Cadastrar produtos e quantidades');stockLink.href='/admin/produtos.'+(location.pathname.endsWith('.hhvm')?'hhvm':'php');emptyCatalog.append(stockLink);box.append(emptyCatalog);
var actions=el('div');actions.className='mka-equipment-actions';
function button(text,fn){var n=el('button',text);n.type='button';n.addEventListener('click',fn);actions.append(n);return n;}
var add=button('+ Adicionar equipamento',function(){rows.push({id:0,product_id:0,serial:'',comodato:document.querySelector('[name="comodato"]:checked')?.value||'nao'});changed();render();});
var save=button('Salvar equipamentos',function(){submit();});save.className='mka-equipment-save';
var reload=button('Atualizar lista e saldo',function(){if(!dirty||window.confirm('Descartar as alterações não salvas dos equipamentos?'))load();});
box.append(list,actions,status);var rows=[],baseline=[],catalog=[],revision=0,csrf='',dirty=false,busy=false,pending=null;
function message(s,error){status.textContent=s;status.classList.toggle('error',!!error);}
function lock(value){busy=value;box.querySelectorAll('button,input,select').forEach(function(n){n.disabled=value;});if(!value){add.disabled=!csrf||rows.length>=100||!catalog.some(function(p){return p.active&&p.available!==null;});save.disabled=!csrf;}}
function changed(){dirty=true;pending=null;message('Alterações de equipamentos ainda não salvas.');updateBalances();}
function counts(items){var m={};items.forEach(function(r){if(r.product_id&&!r.stale)m[r.product_id]=(m[r.product_id]||0)+1;});return m;}
function updateBalances(){var old=counts(baseline),next=counts(rows);list.querySelectorAll('[data-stock]').forEach(function(n){var id=Number(n.dataset.stock),p=catalog.find(function(x){return x.id===id;});if(!p){n.textContent='Selecione um produto do estoque.';return;}var available=p.available,after=available===null?null:available+(old[id]||0)-(next[id]||0);n.textContent=available===null?'Saldo indisponível: revise o estoque do produto.':'Disponível em estoque: '+available+' · Após salvar: '+after;n.classList.toggle('stock-low',after===null||after<0);});}
function render(){list.replaceChildren();rows.forEach(function(r,index){var line=el('div');line.className='mka-equipment-row';
var productLabel=el('label');productLabel.append(el('span','Equipamento '+(index+1)));var select=el('select');select.setAttribute('aria-label','Equipamento '+(index+1));var empty=el('option','Selecione um equipamento');empty.value='0';select.append(empty);
catalog.forEach(function(p){if(!p.active&&p.id!==r.product_id)return;var opt=el('option',p.name+(p.active?'':' (inativo)'));opt.value=String(p.id);select.append(opt);});
if(r.product_id&&!catalog.some(function(p){return p.id===r.product_id;})){var missing=el('option',r.name||'Produto indisponível');missing.value=String(r.product_id);select.append(missing);}select.value=String(r.product_id);select.onchange=function(){r.product_id=Number(select.value);changed();stock.dataset.stock=String(r.product_id);updateBalances();};
var stock=el('small');stock.dataset.stock=String(r.product_id);productLabel.append(select,stock);if(r.stale)productLabel.append(el('small','Vínculo alterado no estoque nativo. Remova esta linha e salve antes de adicionar novamente.'));
var serialLabel=el('label');serialLabel.append(el('span','MAC/Serial'));var input=el('input');input.type='text';input.maxLength=255;input.value=r.serial;input.placeholder='MAC ou número de série';input.oninput=function(){r.serial=input.value;changed();};serialLabel.append(input);
var loanLabel=el('label');loanLabel.append(el('span','Comodato'));var loan=el('select');['nao','sim'].forEach(function(v){var o=el('option',v==='sim'?'Sim':'Não');o.value=v;loan.append(o);});loan.value=r.comodato;loan.onchange=function(){r.comodato=loan.value;changed();};loanLabel.append(loan);
var remove=el('button','Remover');remove.type='button';remove.className='mka-equipment-remove';remove.setAttribute('aria-label','Remover equipamento '+(index+1));remove.onclick=function(){rows.splice(index,1);changed();render();};line.append(productLabel,serialLabel,loanLabel,remove);list.append(line);});
if(!rows.length)list.append(el('p','Nenhum equipamento vinculado por esta lista.'));add.disabled=busy||rows.length>=100||!catalog.some(function(p){return p.active&&p.available!==null;});updateBalances();}
function apply(data){rows=data.items.map(function(r){return Object.assign({},r);});baseline=data.items.map(function(r){return Object.assign({},r);});catalog=data.products;emptyCatalog.hidden=catalog.length!==0;revision=data.revision;csrf=data.csrf;dirty=false;pending=null;render();}
async function request(options){var response=await fetch('/admin/addons/client_equipment/api.php?uuid='+encodeURIComponent(uuid),Object.assign({credentials:'same-origin',cache:'no-store'},options||{}));var data;try{data=await response.json();}catch(e){throw Error('Sessão ou serviço indisponível. Atualize a página antes de tentar novamente.');}if(!response.ok||!data.ok)throw Error(data.error||'Não foi possível consultar os equipamentos.');return data;}
async function load(){lock(true);message('Consultando equipamentos e estoque…');try{apply(await request());message('Estoque atualizado. A baixa ocorre ao salvar equipamentos.');}catch(e){message(e.message,true);}finally{lock(false);}}
async function submit(){if(busy)return;if(rows.some(function(r){return !r.product_id;})){message('Selecione o produto de cada linha.',true);return;}lock(true);message('Salvando equipamentos…');try{
if(!pending){var bytes=new Uint8Array(16);crypto.getRandomValues(bytes);pending={key:Array.from(bytes,function(x){return x.toString(16).padStart(2,'0');}).join(''),items:JSON.stringify(rows.map(function(r){return {id:r.id,product_id:r.product_id,serial:r.serial,comodato:r.comodato};})),revision:revision};}
var form=new URLSearchParams({csrf:csrf,revision:String(pending.revision),request_key:pending.key,items:pending.items});apply(await request({method:'POST',body:form}));message('Equipamentos salvos. Estoque atualizado.');
}catch(e){message(e.message+' Se a conexão falhou, tente salvar novamente: a mesma gravação não baixa duas vezes.',true);}finally{lock(false);}}
// These allocations have their own explicit save. Do not silently lose edits when the native form submits.
var form=nativeSelect.closest('form');if(form)form.addEventListener('submit',function(e){if(dirty||busy){e.preventDefault();e.stopImmediatePropagation();message('Salve os equipamentos antes de gravar o cadastro do cliente.',true);box.scrollIntoView({block:'center'});}},true);
window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue='';}});
load();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
