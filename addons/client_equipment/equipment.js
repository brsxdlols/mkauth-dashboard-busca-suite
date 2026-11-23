(function(){'use strict';
if(window.mkaEquipmentLoaded)return;window.mkaEquipmentLoaded=true;
if(!/\/cliente_(alt|ins)\.(hhvm|php)$/.test(location.pathname))return;
function node(tag,text){var n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}
function start(){
var equipment=document.querySelector('select[name="equipamento"]'),serial=document.querySelector('[name="mac_serial"]');
if(!equipment||!serial)return;
var equipmentRow=equipment.closest('.field.is-horizontal'),serialRow=serial.closest('.field.is-horizontal'),form=equipment.form;
if(!equipmentRow||!serialRow||!form)return;
var uuid=new URLSearchParams(location.search).get('uuid');if(!uuid)return;
var equipmentTemplate=equipmentRow.cloneNode(true),serialTemplate=serialRow.cloneNode(true),original={name:equipment.value,serial:serial.value};
var extra=node('div');extra.className='mka-equipment-extra';serialRow.insertAdjacentElement('afterend',extra);
var statusRow=node('div');statusRow.className='field is-horizontal mka-equipment-feedback';statusRow.hidden=true;
var emptyLabel=node('div');emptyLabel.className=equipmentRow.querySelector('.field-label').className;
var statusBody=node('div');statusBody.className='field-body';var statusField=node('div');statusField.className='field';
var status=node('p');status.className='help';status.setAttribute('role','status');statusField.append(status);statusBody.append(statusField);statusRow.append(emptyLabel,statusBody);extra.insertAdjacentElement('afterend',statusRow);
var rows=[],baseline=[],catalog=[],revision=0,csrf='',dirty=false,busy=false,pending=null,ready=false,bypass=false;
function keyName(s){return String(s||'').trim().normalize('NFKC').toLocaleLowerCase('pt-BR');}
function product(name){return catalog.find(function(p){return keyName(p.name)===keyName(name);});}
function loan(){var n=document.querySelector('[name="comodato"]:checked');return n?n.value:'nao';}
function legacyRow(name,value){return {legacy:true,id:0,product_id:0,name:name,serial:value,comodato:loan()};}
function message(text,error){status.textContent=text;status.className='help'+(error?' is-danger':'');statusRow.hidden=!text;}
function changed(){dirty=true;pending=null;message('');balances();}
function managed(){return rows.filter(function(r){return !r.legacy&&r.product_id;});}
function counts(items){var result={};items.forEach(function(r){if(!r.legacy&&!r.stale&&r.product_id)result[r.product_id]=(result[r.product_id]||0)+1;});return result;}
function balances(){var before=counts(baseline),after=counts(rows);document.querySelectorAll('[data-mka-stock]').forEach(function(n){var r=rows[Number(n.dataset.mkaStock)],p=r&&product(r.name);var value=p&&p.available!==null?p.available:null;n.textContent='Estoque: '+(value===null?'—':value);n.title=value===null?'Equipamento sem saldo cadastrado no estoque.':'Disponível: '+value+'. Após gravar: '+(value+(before[p.id]||0)-(after[p.id]||0))+'.';n.classList.toggle('has-text-danger',value!==null&&value+(before[p.id]||0)-(after[p.id]||0)<0);});}
function lock(value){busy=value;document.querySelectorAll('[data-mka-equipment-control]').forEach(function(n){n.disabled=value;});}
function addOption(select,value,label){if(!Array.from(select.options).some(function(o){return o.value===value;})){var option=node('option',label||value);option.value=value;select.append(option);}}
function controls(eqRow,macRow,r,index,first){
var select=eqRow.querySelector('select'),input=macRow.querySelector('input[name="mac_serial"],input[data-mka-serial]');
if(!first){select.removeAttribute('id');select.name='mka_equipment_name_'+index;input.removeAttribute('id');input.name='mka_equipment_serial_'+index;input.dataset.mkaSerial='1';}
catalog.forEach(function(p){if(p.active||p.id===r.product_id)addOption(select,p.name,p.name+(p.active?'':' (inativo)'));});
addOption(select,r.name||'nenhum');select.value=r.name||'nenhum';input.value=r.serial||'';
select.dataset.mkaEquipmentControl='1';input.dataset.mkaEquipmentControl='1';
select.onchange=function(){r.name=select.value;var p=product(r.name);if(first&&r.legacy&&!p){r.product_id=0;balances();return;}r.legacy=false;r.product_id=p?p.id:0;changed();};
input.oninput=function(){r.serial=input.value;if(!r.legacy)changed();};
var addons=select.closest('.field.has-addons');if(!addons)return;
addons.classList.add('mka-equipment-native-addons');
addons.querySelectorAll('button,[data-mka-stock-control]').forEach(function(n){var control=n.closest('.control');if(control&&control!==select.closest('.control'))control.remove();else n.remove();});
var stockControl=node('div');stockControl.className='control';stockControl.dataset.mkaStockControl='1';var stock=node('span');stock.className='button is-static';stock.dataset.mkaStock=String(index);stockControl.append(stock);addons.append(stockControl);
function action(symbol,title,classes,fn){var control=node('div');control.className='control';var b=node('button');b.type='button';b.className=classes;b.title=title;b.setAttribute('aria-label',title);b.dataset.mkaEquipmentControl='1';var icon=node('i');icon.className=symbol;icon.setAttribute('aria-hidden','true');b.append(icon);b.onclick=function(e){e.preventDefault();e.stopPropagation();if(!busy)fn();};control.append(b);addons.append(control);}
action('bi-plus','Adicionar equipamento','button is-primary',function(){if(rows.length>=100)return;rows.push({legacy:false,id:0,product_id:0,name:'nenhum',serial:'',comodato:loan()});changed();render();var last=extra.querySelectorAll('select');if(last.length)last[last.length-1].focus();});
if(!first||!r.legacy)action('bi-dash','Remover equipamento','button is-danger is-outlined',function(){rows.splice(index,1);if(!rows.length)rows.push(legacyRow('nenhum',''));changed();render();});
}
function render(){extra.replaceChildren();if(!rows.length)rows.push(legacyRow(equipment.value,serial.value));
rows.forEach(function(r,index){if(index===0){controls(equipmentRow,serialRow,r,index,true);return;}
var eq=equipmentTemplate.cloneNode(true),mac=serialTemplate.cloneNode(true);eq.dataset.mkaEquipmentRow='1';mac.dataset.mkaEquipmentRow='1';
eq.querySelectorAll('[id]').forEach(function(n){n.removeAttribute('id');});mac.querySelectorAll('[id]').forEach(function(n){n.removeAttribute('id');});
controls(eq,mac,r,index,false);extra.append(eq,mac);});balances();}
function apply(data,preserve){var first=preserve||(rows[0]&&rows[0].legacy?Object.assign({},rows[0]):null);
catalog=data.products;baseline=data.items.map(function(r){return Object.assign({legacy:false,name:r.name},r);});
if(!ready){var matched=baseline.findIndex(function(r){return keyName(r.name)===keyName(original.name)&&r.serial===original.serial;});
if(matched>=0){baseline.unshift(baseline.splice(matched,1)[0]);first=null;}else first=legacyRow(original.name,original.serial);}
if(first&&(!first.name||first.name==='nenhum')&&baseline.length)first=null;
rows=baseline.map(function(r){return Object.assign({},r);});if(first)rows.unshift(first);if(!rows.length)rows=[legacyRow('nenhum','')];
revision=data.revision;csrf=data.csrf;ready=true;dirty=false;pending=null;render();}
async function request(options){var response=await fetch('/admin/addons/client_equipment/api.php?uuid='+encodeURIComponent(uuid),Object.assign({credentials:'same-origin',cache:'no-store'},options||{}));var data;
try{data=await response.json();}catch(e){throw Error('Não foi possível consultar o estoque. Atualize a página.');}if(!response.ok||!data.ok)throw Error(data.error||'Não foi possível consultar o estoque.');return data;}
async function save(){
if(rows.some(function(r){return !r.legacy&&r.name!=='nenhum'&&!r.product_id;}))throw Error('Cadastre o equipamento selecionado e sua quantidade no estoque antes de gravar.');
if(rows.some(function(r){return r.stale;}))throw Error('Um vínculo foi alterado no estoque. Remova a linha desatualizada antes de gravar.');
if(!pending){var bytes=new Uint8Array(16);crypto.getRandomValues(bytes);pending={key:Array.from(bytes,function(x){return x.toString(16).padStart(2,'0');}).join(''),items:JSON.stringify(managed().map(function(r){return {id:r.id,product_id:r.product_id,serial:r.serial,comodato:r.comodato};})),revision:revision};}
var keep=rows[0]&&rows[0].legacy?Object.assign({},rows[0]):null;
var body=new URLSearchParams({csrf:csrf,revision:String(pending.revision),request_key:pending.key,items:pending.items});apply(await request({method:'POST',body:body}),keep);
}
async function continueSave(resume){if(busy)return;try{
if(typeof window.verifica_form==='function'&&window.verifica_form(form)!==true)return;
if(!form.checkValidity()){form.reportValidity();return;}
lock(true);message('');await save();lock(false);bypass=true;try{resume();}finally{bypass=false;}
}catch(e){lock(false);message(e.message,true);statusRow.scrollIntoView({block:'nearest'});}}
// Use the original native Gravar action; no parallel save button or replacement form.
document.addEventListener('click',function(e){var b=e.target.closest('#btn_gravar');if(!b||b.form!==form||bypass||(!dirty&&!busy))return;e.preventDefault();e.stopImmediatePropagation();continueSave(function(){b.click();});},true);
form.addEventListener('submit',function(e){if(bypass||(!dirty&&!busy))return;e.preventDefault();e.stopImmediatePropagation();continueSave(function(){form.requestSubmit(e.submitter||undefined);});},true);
document.querySelectorAll('[name="comodato"]').forEach(function(n){n.addEventListener('change',function(){rows.forEach(function(r){r.comodato=loan();});if(managed().length)changed();});});
window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue='';}});
// Until the authenticated read finishes, leave every native field and native action intact.
request().then(function(data){original={name:equipment.value,serial:serial.value};apply(data);}).catch(function(e){message(e.message,true);});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
