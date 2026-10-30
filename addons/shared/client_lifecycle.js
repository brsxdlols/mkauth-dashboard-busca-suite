(function () {
    'use strict';
    if (window.mkaClientLifecycleLoaded) return;
    window.mkaClientLifecycleLoaded = true;
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-client-lifecycle]');
        if (!button) return;
        event.preventDefault();
        var action = button.getAttribute('data-client-lifecycle');
        var login = button.getAttribute('data-client-login');
        var url = new URL(button.getAttribute('data-client-native-url'), window.location.href);
        var expected = action === 'disable' ? 'cliente_onoff' : action === 'delete' ? 'cliente_del' : '';
        if (!login || !expected || url.origin !== location.origin ||
            !new RegExp('^/admin/' + expected + '\\.(php|hhvm)$').test(url.pathname)) return;
        var name = button.getAttribute('data-client-name') || login;
        var warning = action === 'disable'
            ? 'Desativar impede o acesso do cliente. O financeiro permanece como está: títulos, valores e pagamentos existentes não são excluídos nem quitados. Informe o motivo e confirme com sua senha no formulário do MK-AUTH.'
            : 'Excluir remove o cadastro pelo MK-AUTH. Confira os títulos, vínculos e históricos antes de continuar. Para apenas interromper o acesso e manter o financeiro, use Desativar. A exclusão só será executada após a confirmação no formulário do MK-AUTH.';
        // POST only to the native preparation form inside a same-origin modal.
        var dialog = document.createElement('dialog');
        dialog.className = 'client-lifecycle-dialog';
        var header = document.createElement('header');
        var title = document.createElement('h2');
        title.textContent = (action === 'disable' ? 'Desativar cliente' : 'Excluir cliente') + ' — ' + name;
        var close = document.createElement('button'); close.type = 'button'; close.textContent = 'Fechar ×';
        header.append(title, close);
        var notice = document.createElement('p'); notice.className = 'client-lifecycle-notice'; notice.textContent = warning;
        var frame = document.createElement('iframe');
        var target = 'mka_client_action_' + Date.now();
        frame.name = target; frame.title = action === 'disable' ? 'Motivo da desativação' : 'Confirmação da exclusão';
        dialog.append(header, notice, frame); document.body.append(dialog);
        var refresh = false, stopWatch = null, observer, oldOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        close.addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('close', function () {
            if (stopWatch) stopWatch();
            if (observer) observer.disconnect();
            document.body.style.overflow = oldOverflow;
            dialog.remove(); button.focus();
            if (refresh) window.location.reload();
        });
        function watchResult() {
            if (stopWatch) return;
            stopWatch = window.mkaWatchClientAction(login, action === 'disable' ? 'inactive' : 'deleted', function () { window.location.reload(); }, function () {
                notice.textContent = 'Não foi possível confirmar o resultado. Confira a mensagem do MK-AUTH e o cadastro antes de repetir a operação.';
            });
        }
        frame.addEventListener('load', function () {
            try {
                var popup = frame.contentWindow, doc = frame.contentDocument;
                // Native success callbacks expect an opener and window.close().
                popup.opener = {reloadAqui: function () { refresh = true; }};
                popup.close = function () { dialog.close(); };
                if (popup.location.pathname !== url.pathname || !doc.body) return;
                var style = doc.createElement('style');
                style.textContent = 'html,body{margin:0!important;min-height:0!important;height:auto!important;overflow:hidden!important} .hero{display:none!important} .mka_window{padding:12px 18px!important;margin:0!important} .field{margin-bottom:12px!important}';
                doc.head.append(style);
                function resize() { frame.style.height = Math.max(300, doc.body.scrollHeight + 20) + 'px'; }
                resize();
                if (observer) observer.disconnect();
                if (window.ResizeObserver) { observer = new ResizeObserver(resize); observer.observe(doc.body); }
                var reason = doc.querySelector('[name="motivo"]');
                if (reason) {
                    reason.required = true;
                    reason.placeholder = 'Informe o motivo desta operação';
                    function validate(event) {
                        if (!reason.value.trim()) {
                            event.preventDefault(); event.stopImmediatePropagation();
                            reason.setCustomValidity('Informe o motivo para continuar.'); reason.reportValidity();
                        } else { watchResult(); }
                    }
                    reason.addEventListener('input', function () { reason.setCustomValidity(''); });
                    doc.addEventListener('submit', validate, true);
                    doc.addEventListener('click', function (event) {
                        if (event.target.closest('#btn_desativar, #btn_deletar')) validate(event);
                    }, true);
                }
            } catch (error) { frame.style.height = '480px'; }
        });
        dialog.showModal();
        var form = document.createElement('form');
        form.method = 'POST'; form.action = url.href; form.target = target;
        var selected = document.createElement('input');
        selected.type = 'hidden'; selected.name = 'login[]'; selected.value = login;
        form.append(selected); document.body.append(form);
        form.submit(); form.remove();
    });
})();
