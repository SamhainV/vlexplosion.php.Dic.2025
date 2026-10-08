/* Shared cover fallback. Retains its listener when the popup displays another cover. */
(() => {
  document.addEventListener('error', (event) => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.dataset.fallback) return;
    const fallback = new URL(image.dataset.fallback, document.baseURI).href;
    const current = new URL(image.getAttribute('src') || '', document.baseURI).href;
    // If the fallback itself fails, do nothing: no retry and no error loop.
    if (current === fallback) return;
    image.src = image.dataset.fallback;
    image.alt = image.dataset.coverTitle ? 'Carátula predeterminada de ' + image.dataset.coverTitle : 'Carátula predeterminada';
  }, true);
})();
