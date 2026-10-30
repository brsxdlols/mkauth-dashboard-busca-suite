(function(){
'use strict';
window.mkaWatchClientAction=function(login,expected,confirmed,unconfirmed){
 var stopped=false,timer,controller,deadline=Date.now()+30000;
 async function check(){
  if(stopped)return;
  controller=new AbortController();var timeout=setTimeout(function(){controller.abort();},4000);
  try{
   var response=await fetch('/admin/addons/busca_inteligente/client_action_status.php?login='+encodeURIComponent(login),{credentials:'same-origin',cache:'no-store',signal:controller.signal});
   if(!response.ok)throw Error('status');var state=await response.json();
   var done=expected==='deleted'?state.exists===false:state.exists===true&&state.active===(expected==='active');
   if(!stopped&&done){stopped=true;confirmed();return;}
  }catch(error){}finally{clearTimeout(timeout);}
  if(stopped)return;
  if(Date.now()>=deadline){stopped=true;if(unconfirmed)unconfirmed();return;}
  timer=setTimeout(check,800);
 }
 timer=setTimeout(check,400);
 return function(){stopped=true;clearTimeout(timer);if(controller)controller.abort();};
};
})();
