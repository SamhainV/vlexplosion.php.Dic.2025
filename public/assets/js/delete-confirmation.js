(() => {
  const dialog = document.getElementById('delete-dialog');
  let pending = null, origin = null;
  const cancel = () => dialog.close();
  document.getElementById('delete-cancel').addEventListener('click', cancel);
  dialog.addEventListener('close', () => { pending = null; if (origin) origin.focus(); });
  document.querySelectorAll('.delete-vinyl-form').forEach(form => {
    form.querySelector('[data-delete-trigger]').disabled = false;
    form.addEventListener('submit', event => {
      if (form.dataset.confirmed === 'true') {
        if (form.dataset.submitted === 'true') { event.preventDefault(); return; }
        form.dataset.submitted = 'true';
        form.querySelector('button[type="submit"]').disabled = true;
        return;
      }
      event.preventDefault();
      if (typeof dialog.showModal !== 'function') {
        if (window.confirm('¿Eliminar «' + form.dataset.title + '»?')) { form.dataset.confirmed = 'true'; form.requestSubmit(); }
        return;
      }
      pending = form; origin = form.querySelector('button[type="submit"]');
      document.getElementById('delete-record-title').textContent = form.dataset.title;
      document.getElementById('delete-confirm').disabled = false;
      dialog.showModal(); document.getElementById('delete-cancel').focus();
    });
  });
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('.delete-vinyl-form').forEach(form => { delete form.dataset.confirmed; delete form.dataset.submitted; form.querySelector('[data-delete-trigger]').disabled = false; });
    if (dialog.open) dialog.close();
  });
  document.getElementById('delete-confirm').addEventListener('click', event => {
    if (!pending || event.currentTarget.disabled) return;
    event.currentTarget.disabled = true;
    pending.dataset.confirmed = 'true'; pending.requestSubmit();
  });
})();
