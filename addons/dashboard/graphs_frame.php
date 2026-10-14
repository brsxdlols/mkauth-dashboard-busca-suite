<?php
$graphsQuery = http_build_query(array_intersect_key($_GET, array('assunto'=>true,'tecnico'=>true)));
$graphsUrl = '/admin/addons/dashboard/graphs.php'.($graphsQuery!==''?'?'.$graphsQuery:'');
?>
<section id="dashboard-graphs" aria-label="Gráficos do Dashboard">
<p class="text-muted" role="status">Carregando gráficos…</p>
</section>
<script>
(function(){
    const host=document.getElementById('dashboard-graphs');
    let frame;
    function start(){
        frame=document.createElement('iframe');
        frame.title='Gráficos do Dashboard';
        frame.style.cssText='display:block;width:100%;height:360px;border:0';
        frame.src=<?= json_encode($graphsUrl,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
        frame.addEventListener('load',function(){
            try {
                if(!frame.contentDocument.querySelector('[data-mka-graphs-ready]'))host.querySelector('[role=status]').textContent='Não foi possível carregar os gráficos. Atualize a página para tentar novamente.';
            }catch(e){host.querySelector('[role=status]').textContent='Não foi possível carregar os gráficos.';}
        });
        host.append(frame);
    }
    window.addEventListener('message',function(event){
        if(!frame || event.origin!==location.origin || event.source!==frame.contentWindow || !event.data || event.data.type!=='mka-dashboard-graphs')return;
        const height=Number(event.data.height);
        if(!Number.isFinite(height) || height<0 || height>50000)return;
        host.querySelector('[role=status]').hidden=true;
        if(height===0)host.hidden=true;
        else frame.style.height=Math.ceil(height)+'px';
    });
    // Start only after the main page has loaded, so historical queries cannot
    // postpone its load event or the native header initialization.
    if(document.readyState==='complete')start();
    else window.addEventListener('load',start,{once:true});
})();
</script>
