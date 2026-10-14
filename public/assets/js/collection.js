/* Only the collection filter drawer. Forms and navigation remain native HTML. */
(() => {
  const panel = document.getElementById('collection-filter-panel');
  if (!panel) return;
  const mobile = window.matchMedia('(max-width: 760px)');
  const adapt = () => { panel.open = !mobile.matches; };
  adapt();
  mobile.addEventListener('change', adapt);
})();
