(function () {
  'use strict';
  var root = document.getElementById('offline-report');
  if (!root) return;
  var form = root.querySelector('.off-filters');
  var search = document.getElementById('pesquisar');
  var period = document.getElementById('off-period');
  var notice = document.getElementById('off-live-status');
  var timer, pending, generation = 0, loadedKey, mapRequest, mapGeneration = 0;
  function filterKey() { var data = new URLSearchParams(new FormData(form)); data.delete('status'); data.sort(); return data.toString(); }
  function visibleChecks() { return Array.from(root.querySelectorAll('.checkbox1')).filter(function (check) { return !check.closest('.linha_resultados').hidden; }); }
  function applyStatus() {
    var status = form.elements.status.value, count = 0;
    root.querySelectorAll('.linha_resultados').forEach(function (row) { row.hidden = status !== '%' && row.getAttribute('data-off-client-status') !== status; if (!row.hidden) count++; row.querySelectorAll('.checkbox1').forEach(function (check) { check.checked = false; check.disabled = row.hidden; }); });
    root.querySelectorAll('[data-off-status]').forEach(function (card) { var active = card.getAttribute('data-off-status') === status; card.classList.toggle('is-selected',active); card.setAttribute('aria-current',String(active)); var query = new URLSearchParams(new FormData(form)); query.set('status',card.getAttribute('data-off-status')); card.href = location.pathname+'?'+query.toString(); });
    var empty = document.getElementById('off-status-empty');
    if (!empty) { empty = document.createElement('p'); empty.id = 'off-status-empty'; empty.className = 'off-empty'; empty.textContent = 'Nenhum cliente encontrado com estes filtros.'; document.getElementById('off-results').appendChild(empty); }
    empty.hidden = count > 0 || !!root.querySelector('.off-empty:not(#off-status-empty)');
    notice.textContent = count + ' clientes encontrados.';
    var url = new URL(location.href); url.search = new URLSearchParams(new FormData(form)).toString(); history.replaceState(null,'',url.pathname+url.search);
    selection();
  }
  function changeStatus() {
    if (filterKey() !== loadedKey) { refresh(); return; }
    clearTimeout(timer); if (pending) pending.abort(); generation++; busy(false); applyStatus();
  }
  function visibility() {
    [['[data-off-hours]', /^(ultimas|mais)$/.test(period.value)], ['[data-off-days]', /^(ultimos_dias|mais_dias)$/.test(period.value)], ['[data-off-range]', period.value === 'intervalo']].forEach(function (group) {
      root.querySelectorAll(group[0]).forEach(function (label) {
        label.hidden = !group[1];
        label.querySelectorAll('input').forEach(function (input) { input.disabled = !group[1]; input.required = group[1]; });
      });
    });
  }
  function selection() {
    var checks = visibleChecks();
    var selected = checks.filter(function (check) { return check.checked; });
    var mapped = selected.filter(function (check) { return check.getAttribute('data-off-has-coords') === '1'; });
    var all = root.querySelector('#selecctall'), toggle = root.querySelector('[data-off-select-all]');
    var map = root.querySelector('[data-off-map-selected]'), caption = root.querySelector('[data-off-selection]');
    if (all) { all.checked = checks.length > 0 && selected.length === checks.length; all.indeterminate = selected.length > 0 && selected.length < checks.length; }
    if (toggle) { toggle.disabled = !checks.length; toggle.textContent = all && all.checked ? 'Desmarcar todos' : 'Selecionar todos'; }
    if (caption) caption.textContent = selected.length + ' selecionados · ' + mapped.length + ' com coordenadas' + (selected.length > mapped.length ? ' · ' + (selected.length - mapped.length) + ' sem coordenadas' : '');
    if (map) map.disabled = !selected.length || document.getElementById('off-results').getAttribute('aria-busy') === 'true';
  }
  function showIndividualMap(button) {
    if (document.getElementById('off-results').getAttribute('aria-busy') === 'true') return;
    var lat = Number(button.getAttribute('data-off-map-lat')), lon = Number(button.getAttribute('data-off-map-lon'));
    if (!Number.isFinite(lat) || !Number.isFinite(lon) || Math.abs(lat) > 90 || Math.abs(lon) > 180 || (lat === 0 && lon === 0)) return;
    var dialog = document.getElementById('off-map-modal'), frame = document.getElementById('off-map-frame');
    document.getElementById('off-map-title').textContent = button.getAttribute('data-off-map-name') || 'Localização do cliente';
    var external = document.getElementById('off-map-external'); external.hidden = false;
    external.href = 'https://www.openstreetmap.org/?mlat='+lat+'&mlon='+lon+'#map=17/'+lat+'/'+lon;
    showMap([button.getAttribute('data-off-map-login')], true);
  }
  async function showMap(logins, individual) {
    if (!logins.length || document.getElementById('off-results').getAttribute('aria-busy') === 'true') return;
    var map = root.querySelector('[data-off-map-action]');
    if (!individual) {
      var post = document.createElement('form');post.method='POST';post.action=map.getAttribute('data-off-map-action');post.target='_self';
      logins.forEach(function(login){var input=document.createElement('input');input.type='hidden';input.name='login[]';input.value=login;post.appendChild(input);});
      document.body.appendChild(post);post.submit();return;
    }
    var dialog = document.getElementById('off-map-modal'), frame = document.getElementById('off-map-frame');
    if (!individual) { document.getElementById('off-map-title').textContent = 'Clientes selecionados no mapa'; document.getElementById('off-map-external').hidden = true; }
    if (mapRequest) mapRequest.abort();
    mapRequest = new AbortController(); var mapTicket = ++mapGeneration;
    frame.removeAttribute('src'); frame.srcdoc = '<p style="font:14px Arial;padding:20px">Carregando mapa…</p>';
    dialog.showModal();
    var target = new URL(map.getAttribute('data-off-map-action'), location.href); target.searchParams.set('embed','1');
    var data = new URLSearchParams(); logins.forEach(function(login){data.append('login[]',login);});
    try {
      var response = await fetch(target.href,{method:'POST',body:data,credentials:'same-origin',cache:'no-store',signal:mapRequest.signal});
      if (!response.ok) throw new Error('map');
      var html = await response.text();
      if (mapTicket !== mapGeneration || !dialog.open) return;
      frame.srcdoc = html.replace('<head>', '<head><base href="'+new URL('./',target.href).href+'">');
    } catch (error) {
      if (error.name !== 'AbortError' && mapTicket === mapGeneration) frame.srcdoc = '<p style="font:14px Arial;padding:20px">Não foi possível carregar o mapa. Feche e tente novamente.</p>';
    }
  }
  function busy(value) {
    var results = document.getElementById('off-results');
    results.setAttribute('aria-busy', String(value));
    results.querySelectorAll('[data-off-map-login]').forEach(function (button) { button.disabled = value; });
    selection();
    results.querySelectorAll('input[type=image], button[type=submit]').forEach(function (button) { button.disabled = value; });
  }
  async function refresh() {
    clearTimeout(timer);
    if (pending) pending.abort();
    var ticket = ++generation;
    if (!form.reportValidity()) { busy(false); notice.textContent = ''; return; }
    var url = new URL(form.action || location.href, location.href);
    url.search = new URLSearchParams(new FormData(form)).toString();
    var queryKey = filterKey(); var requestUrl = new URL(url.href); requestUrl.searchParams.set('_fragment','1');
    pending = new AbortController(); var controller = pending; var timedOut = false; var timeout = setTimeout(function(){timedOut=true;controller.abort();},12000); busy(true); notice.textContent = 'Buscando clientes…';
    try {
      var response = await fetch(requestUrl.href, {credentials: 'same-origin', cache: 'no-store', signal: pending.signal});
      if (!response.ok) throw new Error('response');
      var doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      if (ticket !== generation) return;
      var results = doc.querySelector('#off-results'), cards = doc.querySelector('.off-cards');
      if (!results || !cards) throw new Error('session');
      root.querySelector('.off-cards').replaceWith(cards);
      document.getElementById('off-results').replaceWith(results);
      document.getElementById('off-query-error').replaceWith(doc.getElementById('off-query-error'));
      loadedKey = queryKey; applyStatus();
      var error = root.querySelector('#off-query-error [role=alert]');
      notice.textContent = error ? error.textContent : root.querySelectorAll('#off-results .linha_resultados:not([hidden])').length + ' clientes encontrados.';
    } catch (error) {
      if (timedOut && ticket === generation) notice.textContent = 'A consulta demorou mais que o esperado. Tente novamente.';
      else if (error.name !== 'AbortError' && ticket === generation) notice.textContent = error.message === 'session' ? 'Não foi possível carregar o relatório. Recarregue a página e verifique sua sessão.' : 'Não foi possível atualizar a pesquisa. Tente novamente.';
    } finally { clearTimeout(timeout); if (ticket === generation) busy(false); }
  }
  var analysisDialog = document.getElementById('off-analysis-modal');
  analysisDialog.addEventListener('click', function(event) { if (event.target === analysisDialog || event.target.closest('[data-off-analysis-close]')) analysisDialog.close(); });
  analysisDialog.addEventListener('close', function() { document.getElementById('off-analysis-body').replaceChildren(); });
  var mapDialog = document.getElementById('off-map-modal');
  mapDialog.addEventListener('close', function () { mapGeneration++; if(mapRequest)mapRequest.abort(); var frame=document.getElementById('off-map-frame'); frame.removeAttribute('srcdoc'); frame.src='about:blank'; });
  mapDialog.addEventListener('click', function (event) { if (event.target === mapDialog || event.target.closest('[data-off-map-close]')) mapDialog.close(); });
  visibility(); loadedKey = filterKey(); applyStatus();
  form.addEventListener('submit', function (event) { event.preventDefault(); refresh(); });
  period.addEventListener('change', visibility);
  form.addEventListener('change', function (event) { if (event.target.name === 'status') changeStatus(); else if (['organizar','reconexoes','chamados'].includes(event.target.name)) refresh(); });
  search.addEventListener('input', function () { clearTimeout(timer); if (pending) pending.abort(); generation++; busy(false); timer = setTimeout(refresh, 350); });
  root.addEventListener('click', function (event) {
    var ticket = event.target.closest('[data-off-ticket-modal]');
    if (ticket) { event.preventDefault(); var ticketDialog = document.getElementById('off-analysis-modal'); document.getElementById('off-analysis-title').textContent = 'Chamados antes da desconexão'; ticketDialog.querySelector('[data-off-analysis-client]').textContent = ticket.getAttribute('data-off-ticket-name'); var ticketBody = document.getElementById('off-analysis-body'); ticketBody.replaceChildren(ticket.closest('.off-ticket-compact').querySelector('template').content.cloneNode(true)); ticketBody.querySelectorAll('details').forEach(function(detail){detail.open=true;}); ticketDialog.showModal(); return; }
    var analysis = event.target.closest('[data-off-analysis]');
    if (analysis) { event.preventDefault(); document.getElementById('off-analysis-title').textContent = 'Análise das desconexões'; var dialog = document.getElementById('off-analysis-modal'); var body = document.getElementById('off-analysis-body'); body.replaceChildren(analysis.closest('.off-analysis-entry').querySelector('template').content.cloneNode(true)); dialog.querySelector('[data-off-analysis-client]').textContent = analysis.getAttribute('data-off-analysis-name'); dialog.showModal(); return; }
    var one = event.target.closest('[data-off-map-login]');
    if (one) { event.preventDefault(); showIndividualMap(one); return; }
    if (event.target.closest('[data-off-map-selected]')) { event.preventDefault(); showMap(Array.from(root.querySelectorAll('.checkbox1:checked')).map(function (check) { return check.value; })); return; }
    if (event.target.closest('[data-off-select-all]')) { event.preventDefault(); var all = root.querySelector('#selecctall'); if (all) { all.checked = !all.checked; all.dispatchEvent(new Event('change', {bubbles:true})); } return; }
    var card = event.target.closest('[data-off-status]');
    if (!card || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault(); form.elements.status.value = card.getAttribute('data-off-status'); changeStatus();
  });
  root.addEventListener('change', function (event) {
    var checks = visibleChecks(), all = root.querySelector('#selecctall');
    if (!all) return;
    if (event.target === all) checks.forEach(function (check) { check.checked = all.checked; });
    if (event.target === all || event.target.classList.contains('checkbox1')) selection();
  });
})();
