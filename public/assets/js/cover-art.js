/* Progressive enhancement: POST/redirect and the session confirm the result. */
(() => {
  const root = document.querySelector('.caa-panel');
  if (!root) return;
  const activity = root.querySelector('[data-caa-activity]');
  const panel = root.querySelector('[data-caa-batch]');
  let busy = false;
  let stopped = true;
  const saved = new Map();
  const lock = (value) => {
    busy = value;
    // Keep the live status outside busy regions so it is announced immediately.
    root.querySelectorAll('form[data-caa-action]').forEach(form => form.setAttribute('aria-busy', String(value)));
    root.querySelectorAll('button:not([data-caa-stop])').forEach(button => {
      if (value) { if (!saved.has(button)) saved.set(button, button.disabled); button.disabled = true; }
      else if (saved.has(button)) { button.disabled = saved.get(button); saved.delete(button); }
    });
  };
  const say = text => { activity.hidden = false; activity.textContent = text; };
  const pending = {'confirm-source':'Guardando confirmación de edición…', 'remove-source':'Retirando fuente visual…', fetch:'Buscando carátula…', refresh:'Actualizando carátula…', 'fetch-group':'Buscando alternativa de grupo…', auto:'Guardando selección…', manual:'Guardando selección…', group:'Guardando selección…', start:'Preparando lote…', step:'Comprobando la siguiente carátula…', pause:'Guardando pausa del lote…'};
  root.querySelectorAll('form[data-caa-action]').forEach(form => {
    form.addEventListener('submit', event => {
      if (busy) { event.preventDefault(); return; }
      const submitter = event.submitter || form.querySelector('button[name="action"]');
      // Disabled submit buttons are omitted by browsers: preserve the actual action.
      if (submitter?.name) {
        let field = form.querySelector('[data-caa-submitter]');
        if (!field) { field = document.createElement('input'); field.type = 'hidden'; field.dataset.caaSubmitter = ''; form.append(field); }
        field.name = submitter.name; field.value = submitter.value;
      }
      say(pending[submitter?.value] || 'Procesando operación…');
      lock(true);
    });
  });
  window.addEventListener('pageshow', () => { lock(false); activity.hidden = true; });
  if (!panel) return;
  const run = panel.querySelector('[data-caa-run]');
  const stop = panel.querySelector('[data-caa-stop]');
  const status = panel.querySelector('[data-caa-status]');
  stop.addEventListener('click', () => {
    stopped = true;
    status.textContent = 'Pausando: esperamos al disco en curso y conservamos el progreso.';
    stop.disabled = true;
  });
  run.addEventListener('click', async () => {
    if (busy) return;
    stopped = false; lock(true); stop.hidden = false; stop.disabled = false;
    let lastCounts = '';
    try {
      for (let step = 0; step < 5 && !stopped; step++) {
        say('Procesando lote: comprobando la siguiente carátula…');
        status.textContent = lastCounts || 'Procesando lote…';
        const body = new URLSearchParams({_csrf: panel.dataset.csrf, action: 'step', id: '0', response: 'json'});
        const response = await fetch(panel.dataset.endpoint, {method: 'POST', body, credentials: 'same-origin', signal: AbortSignal.timeout(30000)});
        if (!response.ok || response.redirected) throw new Error('request');
        const result = await response.json();
        const batch = result.batch;
        if (!batch || result.ok !== true) throw new Error('state');
        lastCounts = `Revisados ${batch.reviewed}/${batch.total} · con carátula ${batch.downloaded} · sin imagen ${batch.missing} · omitidos ${batch.skipped} · errores ${batch.errors}`;
        const finishing = batch.state === 'complete' || batch.retry_at > Date.now()/1000 || stopped || step === 4;
        status.textContent = (finishing ? result.message : 'Paso guardado. El lote continúa.') + ' ' + lastCounts;
        if (batch.state === 'complete' || batch.retry_at > Date.now()/1000) break;
        if (!stopped && step < 4) await new Promise(resolve => setTimeout(resolve, 1200));
      }
      // The server stores the confirmed result in the session before this reload.
      location.reload();
    } catch (_) {
      say('Lote detenido: no se pudo confirmar la última operación.');
      status.textContent = (lastCounts ? lastCounts + '. ' : '') + 'Recarga para consultar el progreso guardado antes de reanudar. No iniciaremos más peticiones.';
    } finally {
      stopped = true; lock(false); stop.hidden = true; stop.disabled = false;
    }
  });
})();
