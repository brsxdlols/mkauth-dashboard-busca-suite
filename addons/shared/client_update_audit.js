(function () {
    'use strict';
    var form = document.querySelector('form[action*="executar_cliente"][action*="acao=alt.cliente"]');
    if (!form || form.dataset.mkaClientAuditBound === '1') return;
    form.dataset.mkaClientAuditBound = '1';
    form.addEventListener('submit', function () {
        var data = new FormData();
        var uuid = form.querySelector('[name="uuid_cliente"]');
        var login = form.querySelector('[name="login"]');
        data.append('mka_client_audit', 'prepare');
        data.append('uuid_cliente', uuid ? uuid.value : '');
        data.append('login', login ? login.value : '');
        navigator.sendBeacon('/admin/addons/shared/client_update_audit.php', data);
    });
}());

