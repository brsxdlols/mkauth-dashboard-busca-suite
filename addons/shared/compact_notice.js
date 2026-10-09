(function () {
    window.mkaCompactNotice = function (title, message) {
        const previous = document.activeElement;
        const dialog = document.createElement('dialog');
        dialog.setAttribute('aria-label', title);
        dialog.style.cssText = 'box-sizing:border-box;width:min(420px,90vw);padding:24px;border:1px solid #dce5f1;border-radius:16px;background:#fff;color:#18324a;box-shadow:0 24px 70px #0004;font:14px/1.5 Arial,sans-serif';
        const heading = document.createElement('h3');
        heading.style.cssText = 'margin:0 0 10px;font-size:18px';
        heading.textContent = title;
        const text = document.createElement('p');
        text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';
        close.textContent = 'Entendi';
        close.style.cssText = 'float:right;border:0;border-radius:9px;padding:10px 18px;background:#2563eb;color:#fff;cursor:pointer;font-weight:600';
        close.onclick = function () { dialog.close(); };
        dialog.append(heading, text, close);
        document.body.append(dialog);
        dialog.addEventListener('close', function () { dialog.remove(); if (previous) previous.focus(); });
        dialog.showModal();
    };
})();
