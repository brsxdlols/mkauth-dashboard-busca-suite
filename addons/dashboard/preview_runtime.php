<?php if(empty($dashboardPreviewToken))return; ?>
<style>#dashboard-session-toast-stack,#dashboard-client-state-toast-stack,#mka-server-time-alerts{display:none!important}</style>
<script>
(function(){var token=<?=json_encode($dashboardPreviewToken)?>,original=window.fetch;
window.fetch=function(input,options){var value=typeof input==='string'?input:input.url,url=new URL(value,location.href);if(url.origin===location.origin&&url.pathname.indexOf('/admin/addons/dashboard/')===0){if(options&&options.method&&options.method.toUpperCase()!=='GET')return Promise.reject(Error('Pré-visualização somente leitura'));url.searchParams.set('preview',token);input=url.href;}return original.call(this,input,options);};
document.addEventListener('click',function(e){e.preventDefault();e.stopImmediatePropagation();},true);
document.addEventListener('submit',function(e){e.preventDefault();e.stopImmediatePropagation();},true);
})();
</script>
