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
var nativeEquipment=node('input');nativeEquipment.type='hidden';nativeEquipment.name='equipamento';nativeEquipment.value=original.name;
var nativeSerial=node('input');nativeSerial.type='hidden';nativeSerial.name='mac_serial';nativeSerial.value=original.serial;
var draft={legacy:false,id:0,product_id:0,name:'nenhum',serial:'',comodato:loan()};
var extra=node('div');extra.className='mka-equipment-extra mka-equipment-table-field field is-horizontal';serialRow.insertAdjacentElement('afterend',extra);
var statusRow=node('div');statusRow.className='field is-horizontal mka-equipment-feedback';statusRow.hidden=true;
var emptyLabel=node('div');emptyLabel.className=equipmentRow.querySelector('.field-label').className;
var statusBody=node('div');statusBody.className='field-body';var statusField=node('div');statusField.className='field';
var status=node('p');status.className='help';status.setAttribute('role','status');statusField.append(status);statusBody.append(statusField);statusRow.append(emptyLabel,statusBody);extra.insertAdjacentElement('afterend',statusRow);
var rows=[],baseline=[],catalog=[],revision=0,csrf='',dirty=false,busy=false,pending=null,ready=false,bypass=false;
var stockDialog,stockOpener,stockName,stockHeading,stockReason;
function explainStock(opener,name,available){stockOpener=opener;
var unknown=available===undefined||available===null;
var reason=unknown?'Este equipamento não tem uma quantidade de estoque cadastrada. Por isso aparece ? no lugar do saldo e não é possível adicioná-lo ao cliente.':'Não há saldo disponível deste equipamento para adicionar ao cliente. O saldo disponível nesta tela é '+Math.max(0,Number(available))+' unidade(s).';
message(reason+' Acesse Provedor → Controle de produtos e registre uma entrada no estoque.',true);
if(!stockDialog){stockDialog=node('dialog');stockDialog.className='mka-equipment-stock-dialog';stockDialog.setAttribute('aria-labelledby','mka-equipment-stock-title');
var header=node('header');header.className='modal-card-head';var heading=node('h2','Quantidade de estoque não cadastrada');heading.className='modal-card-title';heading.id='mka-equipment-stock-title';stockHeading=heading;var close=node('button');close.type='button';close.className='delete';close.setAttribute('aria-label','Fechar');close.onclick=function(){stockDialog.close();};header.append(heading,close);
var body=node('div');body.className='modal-card-body';stockName=node('p');stockName.className='has-text-weight-semibold';stockReason=node('p');stockReason.id='mka-equipment-stock-reason';stockDialog.setAttribute('aria-describedby',stockReason.id);var steps=node('ol');['Acesse Provedor → Controle de produtos.','Cadastre o produto correspondente, se necessário, e registre a entrada da quantidade disponível no estoque.','Volte a esta tela, confira o saldo e clique em + para adicionar o equipamento ao cliente.'].forEach(function(text){steps.append(node('li',text));});body.append(stockName,stockReason,steps);
var footer=node('footer');footer.className='modal-card-foot';var dismiss=node('button','Fechar');dismiss.type='button';dismiss.className='button';dismiss.onclick=function(){stockDialog.close();};var register=node('a','Abrir Controle de produtos');register.className='button is-primary';register.href='/admin/produtos.'+(location.pathname.endsWith('.hhvm')?'hhvm':'php');register.target='_blank';register.rel='noopener';register.onclick=function(){stockDialog.close();};footer.append(dismiss,register);stockDialog.append(header,body,footer);document.body.append(stockDialog);
stockDialog.addEventListener('close',function(){if(stockOpener&&stockOpener.isConnected)stockOpener.focus({preventScroll:true});});}
stockName.textContent=name;stockHeading.textContent=unknown?'Estoque não cadastrado — equipamento não adicionado':'Sem saldo no estoque — equipamento não adicionado';stockReason.textContent=reason;if(!stockDialog.open)stockDialog.showModal();}
function keyName(s){return String(s||'').trim().normalize('NFKC').toLocaleLowerCase('pt-BR');}
function product(name){var exact=catalog.filter(function(p){return keyName(p.name)===keyName(name);});if(exact.length===1)return exact[0];var canonical=function(s){return keyName(s).normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[_\s-]+/g,' ');};var matches=catalog.filter(function(p){return canonical(p.name)===canonical(name);});return matches.length===1?matches[0]:null;}
function loan(){var n=document.querySelector('[name="comodato"]:checked');return n?n.value:'nao';}
function legacyRow(name,value){return {legacy:true,id:0,product_id:0,name:name,serial:value,comodato:loan()};}
function message(text,error){status.textContent=text;status.className='help'+(error?' is-danger':'');statusRow.hidden=!text;}
function changed(){dirty=true;pending=null;message('');balances();}
function managed(){return rows.filter(function(r){return !r.legacy&&r.product_id;});}
function counts(items){var result={};items.forEach(function(r){if(!r.legacy&&!r.stale&&r.product_id)result[r.product_id]=(result[r.product_id]||0)+1;});return result;}
function balances(){var before=counts(baseline),after=counts(rows);document.querySelectorAll('[data-mka-stock]').forEach(function(n){var r=draft,selected=r.name&&r.name!=='nenhum',p=product(r.name);var known=p&&p.available!==null&&p.available!==undefined,value=known?Number(p.available)+(before[p.id]||0)-(after[p.id]||0):null;
n.textContent='Estoque: '+(!selected?'selecione':value===null?'?':value);n.title=!selected?'Selecione o equipamento.':value===null?'Sem quantidade cadastrada. Clique no ? para saber como registrar a entrada no estoque.':'Disponível após as alterações desta tela: '+value+'. A movimentação será confirmada ao gravar.';
n.className='button '+(selected&&value===null?'is-light':'is-static');if(selected&&value===null){n.setAttribute('aria-label','Estoque não cadastrado. Clique para entender.');n.setAttribute('aria-haspopup','dialog');n.onclick=function(){explainStock(n,r.name);};}else{n.setAttribute('aria-label',n.textContent);n.removeAttribute('aria-haspopup');n.onclick=null;}n.classList.toggle('has-text-danger',value!==null&&value<0);});}
function lock(value){busy=value;document.querySelectorAll('[data-mka-equipment-control]').forEach(function(n){n.disabled=value||n.dataset.mkaFixed==='1';});}
function addOption(select,value,label){if(!Array.from(select.options).some(function(o){return o.value===value;})){var option=node('option',label||value);option.value=value;select.append(option);}}
function controls(eqRow,macRow,r,index,first){
var select=eqRow.querySelector('select'),input=macRow.querySelector('input');
if(!first){select.removeAttribute('id');select.name='mka_equipment_name_'+index;input.removeAttribute('id');input.name='mka_equipment_serial_'+index;input.dataset.mkaSerial='1';}
catalog.forEach(function(p){if(p.active||p.id===r.product_id)addOption(select,p.name,p.name+(p.active?'':' (inativo)'));});
addOption(select,r.name||'nenhum');select.value=r.name||'nenhum';input.value=r.serial||'';
select.dataset.mkaEquipmentControl='1';input.dataset.mkaEquipmentControl='1';select.disabled=!first;select.dataset.mkaFixed=first?'0':'1';
select.onchange=function(){if(!first)return;r.name=select.value;var p=product(r.name);if(first&&r.legacy&&!p){r.product_id=0;balances();return;}r.legacy=false;r.product_id=p?p.id:0;balances();};
input.oninput=function(){r.serial=input.value;if(!first)changed();};
var addons=select.closest('.field.has-addons');if(!addons)return;
addons.classList.add('mka-equipment-native-addons');
addons.querySelectorAll('button,[data-mka-stock-control],[data-mka-loan-control]').forEach(function(n){var control=n.closest('.control');if(control&&control!==select.closest('.control'))control.remove();else n.remove();});
if(first){var stockControl=node('div');stockControl.className='control';stockControl.dataset.mkaStockControl='1';var stock=node('button');stock.type='button';stock.className='button is-static';stock.dataset.mkaStock=String(index);stockControl.append(stock);addons.append(stockControl);}
var loanControl=node('div');loanControl.className='control';loanControl.dataset.mkaLoanControl='1';var loanWrapper=node('div');loanWrapper.className='select';var perLoan=node('select');perLoan.name=first?'mka_equipment_add_loan':'mka_equipment_loan_'+index;perLoan.dataset.mkaEquipmentControl='1';perLoan.dataset.mkaLoan=first?'draft':String(index);perLoan.setAttribute('aria-label','Comodato do equipamento '+(index+1));
['sim','nao'].forEach(function(v){var option=node('option','Comodato: '+(v==='sim'?'Sim':'Não'));option.value=v;perLoan.append(option);});perLoan.value=r.comodato;
perLoan.onchange=function(){r.comodato=perLoan.value;if(first)document.querySelectorAll('[name="comodato"]').forEach(function(n){n.checked=n.value===r.comodato;});if(!first){if(r.legacy)document.querySelectorAll('[name="comodato"]').forEach(function(n){n.checked=n.value===r.comodato;});changed();}};loanWrapper.append(perLoan);loanControl.append(loanWrapper);addons.append(loanControl);

function action(symbol,title,classes,fn){var control=node('div');control.className='control';var b=node('button');b.type='button';b.className=classes;b.title=title;b.setAttribute('aria-label',title);b.dataset.mkaEquipmentControl='1';var icon=node('i');icon.className=symbol;icon.setAttribute('aria-hidden','true');b.append(icon);b.onclick=function(e){e.preventDefault();e.stopPropagation();if(!busy)fn();};control.append(b);addons.append(control);}
if(first)action('bi-plus','Adicionar equipamento','button is-primary',function(){
if(rows.length>=100)return;
var p=product(draft.name);if(!draft.name||draft.name==='nenhum'){message('Selecione o equipamento para adicionar.',true);return;}
if(!p||p.available===null||p.available===undefined){explainStock(document.querySelector('[data-mka-stock]'),draft.name);return;}
var before=counts(baseline),after=counts(rows),available=Number(p.available)+(before[p.id]||0)-(after[p.id]||0);
if(available<1){explainStock(document.querySelector('[aria-label="Adicionar equipamento"]'),draft.name,available);return;}
rows.push({legacy:false,id:0,product_id:p.id,name:p.name,serial:draft.serial,comodato:draft.comodato});draft.serial='';changed();render();});
else action('bi-dash','Remover equipamento','button is-danger is-outlined',function(){rows.splice(index,1);changed();render();});
}
function dateLabel(value){if(!value)return 'Ao gravar';var m=String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+'/'+m[2]+'/'+m[1]:String(value);}
function render(){
extra.replaceChildren();controls(equipmentRow,serialRow,draft,0,true);
var label=node('div');label.className=emptyLabel.className;
var body=node('div');body.className='field-body';var field=node('div');field.className='field';var container=node('div');container.className='table-container';
var table=node('table');table.className='table is-fullwidth mka-equipment-table';table.setAttribute('aria-label','Equipamentos vinculados ao cliente');
var thead=node('thead'),head=node('tr');['EQUIPAMENTO','QTN','INSERIDO','COMODATO'].forEach(function(text){var th=node('th',text);th.scope='col';head.append(th);});thead.append(head);table.append(thead);var tbody=node('tbody');table.append(tbody);
var groups=new Map();rows.forEach(function(r){var key=(r.legacy?'legacy:':r.product_id+':')+r.comodato;var g=groups.get(key);if(!g){g={name:r.name,loan:r.comodato,units:[],legacy:r.legacy};groups.set(key,g);}g.units.push(r);});
function action(label,title,fn){var b=node('button',label);b.type='button';b.className='button is-small is-danger is-outlined';b.title=title;b.setAttribute('aria-label',title);b.dataset.mkaEquipmentControl='1';b.onclick=function(){if(!busy)fn();};return b;}
groups.forEach(function(g){
var tr=node('tr');tr.dataset.mkaEquipmentGroup='1';var name=node('td');var remove=action('','Remover uma unidade de '+g.name,function(){var r=g.units[g.units.length-1];rows.splice(rows.indexOf(r),1);changed();render();});remove.className='mka-equipment-remove';remove.dataset.mkaRemoveOne='1';var icon=node('i');icon.className='bi-trash';icon.setAttribute('aria-hidden','true');remove.append(icon);name.append(remove,node('span',g.name));
name.title=g.units.map(function(r){return r.serial||'';}).filter(Boolean).join(', ');
var qty=node('td',String(g.units.length).padStart(3,'0'));qty.dataset.mkaQuantity='1';var dates=Array.from(new Set(g.units.map(function(r){return r.legacy?'—':dateLabel(r.inserted_at);})));var inserted=node('td',dates.length===1?dates[0]:'Várias datas');inserted.title=dates.join(', ');
var loanCell=node('td'),wrap=node('div');wrap.className='select is-small';var select=node('select');select.dataset.mkaEquipmentControl='1';select.dataset.mkaLoan='group';select.setAttribute('aria-label','Comodato de '+g.name);['sim','nao'].forEach(function(value){var option=node('option',value==='sim'?'Sim':'Não');option.value=value;select.append(option);});select.value=g.loan;select.onchange=function(){g.units.forEach(function(r){r.comodato=select.value;if(r.legacy)document.querySelectorAll('[name="comodato"]').forEach(function(n){n.checked=n.value===r.comodato;});});changed();render();};wrap.append(select);loanCell.append(wrap);
tr.append(name,qty,inserted,loanCell);if(g.units.some(function(r){return r.stale;})){tr.classList.add('has-text-danger');name.append(node('small','Vínculo alterado no estoque; remova a unidade desatualizada.'));}tbody.append(tr);
});
if(!tbody.children.length){var tr=node('tr'),td=node('td','Nada cadastrado.');td.colSpan=4;tr.append(td);tbody.append(tr);}
container.append(table);field.append(container);body.append(field);extra.append(label,body);balances();lock(busy);
var primary=rows[0];nativeEquipment.value=primary?primary.name:'nenhum';nativeSerial.value=primary?primary.serial:'';
}
function apply(data,preserve){var first=preserve||rows.find(function(r){return r.legacy;});
catalog=data.products;baseline=data.items.map(function(r){return Object.assign({legacy:false,name:r.name},r);});
if(!ready){form.append(nativeEquipment,nativeSerial);equipment.name='mka_equipment_add';serial.name='mka_equipment_add_serial';var primaryProduct=product(original.name);var matched=baseline.some(function(r){return (keyName(r.name)===keyName(original.name)||(primaryProduct&&primaryProduct.id===r.product_id))&&r.serial===original.serial;});
first=!matched&&original.name&&original.name!=='nenhum'?legacyRow(original.name,original.serial):null;}
rows=baseline.map(function(r){return Object.assign({},r);});if(first)rows.unshift(first);
revision=data.revision;csrf=data.csrf;ready=true;dirty=false;pending=null;render();}

async function request(options){var response=await fetch('/admin/addons/client_equipment/api.php?uuid='+encodeURIComponent(uuid),Object.assign({credentials:'same-origin',cache:'no-store'},options||{}));var data;
try{data=await response.json();}catch(e){throw Error('Não foi possível consultar o estoque. Atualize a página.');}if(!response.ok||!data.ok)throw Error(data.error||'Não foi possível consultar o estoque.');return data;}
async function save(){
if(rows.some(function(r){return !r.legacy&&r.name!=='nenhum'&&!r.product_id;}))throw Error('Cadastre o equipamento selecionado e sua quantidade no estoque antes de gravar.');
if(rows.some(function(r){return r.stale;}))throw Error('Um vínculo foi alterado no estoque. Remova a linha desatualizada antes de gravar.');
if(!pending){var bytes=new Uint8Array(16);crypto.getRandomValues(bytes);pending={key:Array.from(bytes,function(x){return x.toString(16).padStart(2,'0');}).join(''),items:JSON.stringify(managed().map(function(r){return {id:r.id,product_id:r.product_id,serial:r.serial,comodato:r.comodato};})),revision:revision};}
var legacy=rows.find(function(r){return r.legacy;});var keep=legacy?Object.assign({},legacy):null;
var body=new URLSearchParams({csrf:csrf,revision:String(pending.revision),request_key:pending.key,items:pending.items});apply(await request({method:'POST',body:body}),keep);
}
function syncNative(){if(!ready)return;var p=rows[0];nativeEquipment.value=p?p.name:'nenhum';nativeSerial.value=p?p.serial:'';document.querySelectorAll('[name="comodato"]').forEach(function(n){n.checked=n.value===(p?p.comodato:'nao');});}
async function continueSave(resume){if(busy)return;try{
if(typeof window.verifica_form==='function'&&window.verifica_form(form)!==true)return;
if(!form.checkValidity()){form.reportValidity();return;}
lock(true);message('');await save();syncNative();lock(false);bypass=true;try{resume();}finally{bypass=false;}
}catch(e){lock(false);message(e.message,true);statusRow.scrollIntoView({block:'nearest'});}}
// Use the original native Gravar action; no parallel save button or replacement form.
document.addEventListener('click',function(e){var b=e.target.closest('#btn_gravar');if(!b||b.form!==form||bypass)return;if(!dirty&&!busy){syncNative();return;}e.preventDefault();e.stopImmediatePropagation();continueSave(function(){b.click();});},true);
form.addEventListener('submit',function(e){if(bypass)return;if(!dirty&&!busy){syncNative();return;}e.preventDefault();e.stopImmediatePropagation();continueSave(function(){form.requestSubmit(e.submitter||undefined);});},true);
document.querySelectorAll('[name="comodato"]').forEach(function(n){n.addEventListener('change',function(){draft.comodato=loan();var primary=document.querySelector('[data-mka-loan="draft"]');if(primary)primary.value=draft.comodato;});});
window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue='';}});
// Until the authenticated read finishes, leave every native field and native action intact.
request().then(function(data){original={name:equipment.value,serial:serial.value};apply(data);}).catch(function(e){message(e.message,true);});
var refreshing=false;window.addEventListener('focus',async function(){if(!ready||busy||refreshing)return;refreshing=true;try{var data=await request();catalog=data.products;csrf=data.csrf;rows.forEach(function(r){var p=product(r.name);if(!r.legacy&&!r.product_id&&p)r.product_id=p.id;});document.querySelectorAll('select[data-mka-equipment-control]:not([data-mka-loan])').forEach(function(s){catalog.forEach(function(p){if(p.active)addOption(s,p.name);});});render();}catch(e){/* Keep local edits; server validates stock again on Gravar. */}finally{refreshing=false;}});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();


