(function () {
  'use strict';
  var policy = window.nnCpfPolicy;
  if (!policy) return;
  function init() {
    if (policy.mode === 'conf_opcoes') {
      var anchor = document.querySelector('input[name="validar_cpf"]');
      var form = anchor && anchor.form;
      if (!form || document.getElementById('nn-cpf-policy-field')) return;
      var block = document.createElement('div');
      block.className = 'field is-horizontal';
      block.id = 'nn-cpf-policy-field';
      block.innerHTML = '<div class="field-label is-size-7"><label class="label">Permitir cliente sem CPF/CNPJ:</label></div><div class="field-body"><div class="field"><div class="control"><input class="is-checkradio" type="radio" name="nn_permitir_sem_cpf" id="nn_cpf_sim" value="sim"><label for="nn_cpf_sim">Sim</label> <input class="is-checkradio" type="radio" name="nn_permitir_sem_cpf" id="nn_cpf_nao" value="nao"><label for="nn_cpf_nao">N&atilde;o</label></div><p class="help">Permite cadastrar e alterar clientes com o CPF/CNPJ em branco. Documentos preenchidos seguem a op&ccedil;&atilde;o &ldquo;Validar CPF/CNPJ&rdquo;.</p></div></div>';
      var row = anchor.closest('.field.is-horizontal');
      if (!row) return;
      row.parentNode.insertBefore(block, row.nextSibling);
      block.querySelector(policy.allowEmpty ? '#nn_cpf_sim' : '#nn_cpf_nao').checked = true;
      var token = document.createElement('input');
      token.type = 'hidden'; token.name = 'nn_cpf_token'; token.value = policy.csrf; form.appendChild(token);
      return;
    }
    var field = document.getElementById('cpf_cnpj');
    if (!field || typeof window.verifica_form !== 'function') return;
    var nativeVerify = window.verifica_form;
    window.verifica_form = function (form) {
      var target = form.querySelector('input[name="cpf_cnpj"]');
      if (!policy.allowEmpty || !target || target.value.trim() !== '') return nativeVerify.apply(this, arguments);
      var original = target.getAttribute('df_verificar');
      target.setAttribute('df_verificar', 'nao');
      target.classList.remove('is-danger');
      try { return nativeVerify.apply(this, arguments); }
      finally {
        if (original === null) target.removeAttribute('df_verificar');
        else target.setAttribute('df_verificar', original);
      }
    };
    if (policy.allowEmpty) {
      field.removeAttribute('required'); field.setAttribute('aria-required', 'false');
      var help = document.getElementById('msg_cpf');
      if (help) help.textContent = 'Opcional: pode ficar em branco. Se informado, segue a validacao de CPF/CNPJ configurada.';
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
