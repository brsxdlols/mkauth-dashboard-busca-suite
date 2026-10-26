(function(){
'use strict';
if(window.mkaClientActivationLoaded)return;
window.mkaClientActivationLoaded=true;
document.addEventListener('click',function(event){
    var button=event.target.closest('[data-activate-client]');
    if(!button)return;
    event.preventDefault();
    var login=button.getAttribute('data-activate-client');
    if(!login || !window.confirm('Deseja ativar o cliente '+(button.getAttribute('data-activate-name')||login)+'?'))return;
    // Match the native activation flow with one explicitly selected login.
    // A separate form prevents other checked customers from being submitted.
    var form=document.createElement('form');
    form.method='POST';form.action=button.getAttribute('data-activate-url');
    var input=document.createElement('input');
    input.type='hidden';input.name='login[]';input.value=login;
    form.append(input);document.body.append(form);
    form.submit();
});
})();
