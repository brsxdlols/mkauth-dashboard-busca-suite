(function(){
 'use strict';
 var observer,timer,finished=false;
 function ready(){
  if(finished||!document.querySelector('body.monitor-client > .leaflet-popup .traffic-mini'))return;
  finished=true;observer.disconnect();clearTimeout(timer);
  // Wait for the selected panel's layout before exposing it.
  requestAnimationFrame(function(){requestAnimationFrame(function(){document.documentElement.classList.add('mka-monitor-ready');});});
 }
 observer=new MutationObserver(ready);
 observer.observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
 timer=setTimeout(function(){
  if(finished)return;observer.disconnect();
  var panel=document.createElement('div');panel.id='mka-monitor-loading';
  var text=document.createElement('p');text.textContent='Não foi possível abrir o monitor deste cliente. Ele pode ter desconectado ou a consulta pode estar indisponível.';
  var retry=document.createElement('button');retry.type='button';retry.textContent='Tentar novamente';retry.addEventListener('click',function(){location.reload();});
  panel.append(text,retry);document.body.append(panel);document.documentElement.classList.add('mka-monitor-message');
 },20000);
 ready();
}());
