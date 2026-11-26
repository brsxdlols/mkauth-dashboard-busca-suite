(function(){'use strict';
 function update(){
  document.querySelectorAll('.mka-suite-dashboard-start table.table,.multi-main table.table,[data-dashboard-activities] table').forEach(function(table){
   if(!table.tHead||!table.tHead.rows.length)return;
   var headers=Array.from(table.tHead.rows[table.tHead.rows.length-1].cells).map(function(cell){return cell.textContent.trim();});
   if(!headers.length)return;
   table.classList.add('mka-responsive-table');table.setAttribute('role','table');table.tHead.setAttribute('role','rowgroup');
   Array.from(table.tBodies).forEach(function(body){body.setAttribute('role','rowgroup');Array.from(body.rows).forEach(function(row){if(!row.hasAttribute('role'))row.setAttribute('role','row');var index=0;Array.from(row.cells).forEach(function(cell){
    var label=cell.colSpan===1?headers[index]||'':'';index+=cell.colSpan;
    if(cell.dataset.mkaMobileCell==='1')return;
    cell.dataset.mkaMobileCell='1';cell.setAttribute('role',cell.tagName==='TH'?'rowheader':'cell');
    var value=document.createElement('div');value.className='mka-table-value';while(cell.firstChild)value.appendChild(cell.firstChild);cell.appendChild(value);
    if(label){var title=document.createElement('span');title.className='mka-table-label';title.dataset.label=label;title.setAttribute('aria-hidden','true');cell.insertBefore(title,value);}else cell.classList.add('mka-table-full');
   });});});
  });
 }
 function start(){update();var queued=false,root=document.querySelector('.mka-suite-dashboard-start,.multi-main');if(root)new MutationObserver(function(){if(queued)return;queued=true;requestAnimationFrame(function(){queued=false;update();});}).observe(root,{childList:true,subtree:true});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
