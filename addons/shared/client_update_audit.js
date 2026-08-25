(function () {
    'use strict';
    var form = document.querySelector('form[action*="executar_cliente"][action*="acao=alt.cliente"]');
    if (!form || form.dataset.mkaClientAuditBound === '1') return;
    var ignored = /(^|_)(senha|password|token|csrf|uuid|uuid_cliente|login_atend)($|_)/i;

    function snapshot() {
        var values = {};
        Array.prototype.forEach.call(form.elements, function (field) {
            if (!field.name || field.disabled || ignored.test(field.name) || /^(submit|button|reset|file)$/i.test(field.type)) return;
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
            values[field.name] = String(field.value || '').trim();
        });
        return values;
    }

    function fieldLabel(name) {
        var field = form.elements[name];
        if (field && field.length && !field.tagName) field = field[0];
        var label = field && field.id ? document.querySelector('label[for="' + field.id + '"]') : null;
        if (!label && field && field.closest) {
            var container = field.closest('.field, .field-body, .control');
            label = container ? container.querySelector('label') : null;
        }
        var text = label ? label.textContent.replace(/\s+/g, ' ').trim() : name.replace(/_/g, ' ');
        return text.replace(/:$/, '') || name;
    }

    var initial = snapshot();
    form.dataset.mkaClientAuditBound = '1';
    form.addEventListener('submit', function () {
        var current = snapshot();
        var changes = [];
        Object.keys(current).forEach(function (name) {
            var before = Object.prototype.hasOwnProperty.call(initial, name) ? initial[name] : '';
            if (before !== current[name]) {
                changes.push(fieldLabel(name) + ': ' + (before || '(vazio)') + ' → ' + (current[name] || '(vazio)'));
            }
        });
        var data = new FormData();
        var uuid = form.querySelector('[name="uuid_cliente"]');
        var login = form.querySelector('[name="login"]');
        data.append('mka_client_audit', 'prepare');
        data.append('uuid_cliente', uuid ? uuid.value : '');
        data.append('login', login ? login.value : '');
        data.append('detalhes', changes.length ? changes.join('\n') : 'Dados do cliente gravados sem alteração de campo identificável.');
        navigator.sendBeacon('/admin/addons/shared/client_update_audit.php', data);
    });
}());
