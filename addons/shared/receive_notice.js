(function(){
 'use strict';
 if(!/\/receber\.(?:hhvm|php)$/.test(location.pathname))return;
 function removeNotice(){
  for(const node of document.querySelectorAll('.notification,.message,.alert,.hero,section')){
   const text=node.textContent.replace(/\s+/g,' ').trim();
   if(/\bPassou\s+\d+\s+dias?\s+bloquead[oa]/i.test(text)&&/valor\s+em\s+R\$/i.test(text)&&!node.querySelector('input,select,textarea,button,form'))node.remove();
  }
 }
 removeNotice();
 const observer=new MutationObserver(removeNotice);
 observer.observe(document.body,{childList:true,subtree:true});
 window.addEventListener('pagehide',()=>observer.disconnect(),{once:true});
})();
