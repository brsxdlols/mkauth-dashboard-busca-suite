<?php
$mkaMapAvailable = is_file(__DIR__.'/../mapa-clientes/index.php') || is_file(__DIR__.'/../mapa-clientes/index.hhvm');
?>
<style>
.dashboard-attendance-grid .dashboard-stat-card{justify-content:flex-start!important}
@media(min-width:992px){.dashboard-attendance-grid .dashboard-stat-card{height:84px!important;min-height:84px!important}}
.dashboard-attendance-grid .dashboard-stat-value{position:absolute;top:50%;left:0;transform:translateY(-50%);margin:0!important;align-items:center;justify-content:center}
.dashboard-attendance-map{display:flex;align-items:center;justify-content:center;width:100%;box-sizing:border-box;margin-top:8px;padding:8px 12px;min-height:34px;border:0;border-radius:9px;background:#2563eb;color:#fff!important;text-decoration:none!important;font:600 12px/1.3 Arial,sans-serif;cursor:pointer;transition:background .18s,box-shadow .18s,transform .18s}
.dashboard-attendance-map:hover{background:#1d4ed8;box-shadow:0 4px 12px #2563eb33;transform:translateY(-1px)}
.dashboard-attendance-map:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}
</style>
<script src="../shared/compact_notice.js?v=20261008"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const grid=document.querySelector('.dashboard-attendance-grid');
    if(!grid)return;
    const available=<?= $mkaMapAvailable ? 'true' : 'false' ?>;
    const button=document.createElement(available?'a':'button');
    button.className='dashboard-attendance-map';button.textContent='MAPA DE CLIENTES';
    if(available)button.href='/admin/addons/mapa-clientes/';
    else{button.type='button';button.onclick=function(){mkaCompactNotice('Mapa de clientes não liberado','Este addon não está liberado nesta instalação. Entre em contato com a VPSCLOUD - Bruno Fontes para contratação.');};}
    grid.after(button);
});
</script>
