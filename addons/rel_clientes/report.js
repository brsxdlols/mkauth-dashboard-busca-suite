(function(){'use strict';var print=document.getElementById('rc-print');if(print)print.addEventListener('click',function(){window.print();});
var selected=new Set(),activePanel=null,gesture=null;
function payload(item){try{return JSON.parse(item.getAttribute('data-rc-pie'));}catch(e){return null;}}
function key(item){return item.getAttribute('data-rc-pie');}
function money(cents){return (cents/100).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});}
function render(){var plans=new Set();selected.forEach(function(raw){try{JSON.parse(raw).rows.forEach(function(row){plans.add(row.plan);});}catch(e){}});
 document.querySelectorAll('[data-rc-pie]').forEach(function(item){item.classList.toggle('rc-pie-selected',item.closest('.rc-panel')===activePanel&&selected.has(key(item)));});
 document.querySelectorAll('[data-rc-selection]').forEach(function(note){note.textContent=note.closest('.rc-panel')===activePanel&&selected.size?selected.size+' seleção(ões) · '+plans.size+' plano(s). Clique novamente para retirar; Esc limpa a seleção.':'Clique ou arraste nas fatias para filtrar um ou vários planos. Clique no nome do plano para abrir a Busca.';});
 document.querySelectorAll('.rc-plan-tables table').forEach(function(table){var count=0,amount=0;table.querySelectorAll('tbody tr[data-rc-plan]').forEach(function(row){row.hidden=selected.size>0&&!plans.has(row.getAttribute('data-rc-plan'));if(!row.hidden){count+=Number(row.getAttribute('data-rc-count'));amount+=Number(row.getAttribute('data-rc-amount'));}});var foot=table.tFoot;if(foot){foot.rows[0].cells[2].textContent=count;foot.rows[0].cells[3].textContent=money(amount);}});
}
function activate(item,add){var panel=item.closest('.rc-panel');if(panel!==activePanel){selected.clear();activePanel=panel;}var raw=key(item);if(add)selected.add(raw);else if(selected.has(raw))selected.delete(raw);else selected.add(raw);render();}
document.querySelectorAll('.rc-pie').forEach(function(svg){
 svg.addEventListener('pointerdown',function(event){if(event.button!==0)return;var item=event.target.closest('[data-rc-pie]');if(!item)return;gesture={id:event.pointerId,svg:svg,start:item,x:event.clientX,y:event.clientY,drag:false};svg.setPointerCapture(event.pointerId);event.preventDefault();});
 svg.addEventListener('pointermove',function(event){if(!gesture||gesture.id!==event.pointerId)return;if(!gesture.drag&&Math.hypot(event.clientX-gesture.x,event.clientY-gesture.y)>5){gesture.drag=true;selected.clear();activePanel=gesture.start.closest('.rc-panel');activate(gesture.start,true);}if(!gesture.drag)return;var hit=document.elementFromPoint(event.clientX,event.clientY),item=hit&&hit.closest('[data-rc-pie]');if(item&&item.closest('svg')===svg)activate(item,true);});
 svg.addEventListener('pointerup',function(event){if(!gesture||gesture.id!==event.pointerId)return;var current=gesture;gesture=null;if(!current.drag)activate(current.start,false);if(svg.hasPointerCapture(event.pointerId))svg.releasePointerCapture(event.pointerId);});
 svg.addEventListener('pointercancel',function(){gesture=null;});
 svg.addEventListener('lostpointercapture',function(){gesture=null;});
});
document.addEventListener('click',function(event){var item=event.target.closest('[data-rc-pie]');if(!item||item.hasAttribute('data-rc-direct'))return;event.preventDefault();if(!item.closest('svg')||event.detail===0)activate(item,false);});
document.addEventListener('keydown',function(event){if(event.key==='Escape'&&selected.size){selected.clear();render();}});
})();
