(function () {
    'use strict';
    var previousFocus, modal;
            var style = document.createElement('style');
            style.textContent = '.mka-performance-modal{width:min(1100px,96vw);height:90vh;max-width:96vw;max-height:94vh;padding:0;border:1px solid #dbe3ee;border-radius:16px;background:#f5f7fb;color:#20334b;box-shadow:0 24px 80px #0004}.mka-performance-modal::backdrop{background:#14243c88}.mka-performance-modal header{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:white;border-bottom:1px solid #dbe3ee;font:600 16px system-ui}.mka-performance-modal button{border:0;border-radius:8px;background:#edf2f7;color:#20334b;padding:8px 12px;cursor:pointer}.mka-performance-modal iframe{display:block;width:100%;height:calc(100% - 61px);border:0}.mka-performance-launch{display:inline-flex;align-items:center;padding:9px 12px;margin:6px;border-radius:9px;background:#2563eb;color:white!important;text-decoration:none;font:600 13px system-ui}';
            document.head.appendChild(style);

    function closeModal() {
        if (!modal) return;
        modal.close();
        modal.querySelector('iframe').src = 'about:blank';
        if (previousFocus) previousFocus.focus();
    }
    function openModal() {
        previousFocus = document.activeElement;
        if (!modal) {
            modal = document.createElement('dialog');
            modal.className = 'mka-performance-modal';
            modal.setAttribute('aria-label', 'Desempenho da Busca');
            modal.innerHTML = '<header><span>Desempenho da Busca</span><button type="button" aria-label="Fechar análise">Fechar</button></header><iframe title="Análise de desempenho Online e Offline"></iframe>';
            document.body.appendChild(modal);
            modal.querySelector('button').onclick = closeModal;
            modal.addEventListener('cancel', function (event) {event.preventDefault(); closeModal();});
        }
        modal.querySelector('iframe').src = '../busca_inteligente/performance.php?t=' + Date.now();
        modal.showModal();
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-mka-performance]');
        if (!button) return;
        event.preventDefault();
        openModal();
    });
}());
