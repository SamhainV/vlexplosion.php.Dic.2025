<?php
/** @var array $items */
/** @var App\Core\Paginator|null $p */
/** @var string $sort */
/** @var array $sortOptions */

$highlightId = \App\Core\Input::integer($_GET['highlight'] ?? 0);
$deleted = 0;
$flash = $_SESSION['_flash'] ?? null; unset($_SESSION['_flash']);

$sort = (string)($sort ?? ($_GET['sort'] ?? 'newest'));

$sortOptions = $sortOptions ?? \App\Models\Vinyl::allowedSorts();

if (!isset($sortOptions[$sort])) {
  $sort = 'newest';
}

$sortQ = urlencode($sort) . '&' . http_build_query(\App\Core\CollectionFilter::read());

$items = $items ?? [];
$p = $p ?? null;
$total = $p->total ?? count($items);
$currentPage = $p !== null ? (int)$p->page : max(1, (int)($_GET['page'] ?? 1));

$filters = $filters ?? \App\Core\CollectionFilter::read();
$hasFilters = ($filters['q'] ?? '') !== '' || !empty($filters['fav']) || !empty($filters['desired']);
$clearUrl = base_url('vinyls?sort=' . urlencode($sort));
$firstResult = $total > 0 ? (($p !== null ? $p->offset() : 0) + 1) : 0;
$lastResult = $firstResult > 0 ? $firstResult + count($items) - 1 : 0;
$coverUrl = [\App\Core\CoverStore::class, 'url'];
?>

<section class="collection" aria-labelledby="collection-title">
  <?php if ($flash): ?>
  <div id="delete-message" role="status" class="collection-flash"><?= e($flash) ?>
  </div>
  <?php endif; ?>
  <div class="collection-heading">
    <div>
      <p class="collection-eyebrow">
        <span>
        </span> TU ARCHIVO MUSICAL</p>
      <h1 id="collection-title">Mis discos<span class="collection-count"><?= (int)$total ?>
        </span>
      </h1>
      <p class="collection-intro"><?= $hasFilters ? 'Tu colección, con la selección que buscas.' : 'Todo lo que escuchas. Todo lo que merece quedarse.' ?>
      </p>
    </div>
    <div class="collection-add">
      <?php if(\App\Models\CoverArt::available()): ?><a class="collection-assisted" href="<?= e(base_url('covers')) ?>">Carátulas automáticas</a><?php endif; ?>
      <a class="collection-button primary" href="<?= e(base_url('vinyls/create?page=' . $currentPage . '&sort=' . $sortQ)) ?>">
        <span aria-hidden="true">＋</span> Añadir disco</a>
      <a class="collection-assisted" href="<?= e(base_url(\App\Core\Encyclopedia::enabled() ? 'encyclopedia/search' : 'vinyls/ai')) ?>"><?= \App\Core\Encyclopedia::enabled() ? 'Añadir mediante búsqueda musical' : (\App\Core\AiFeature::enabled() ? 'Añadir vinilo con IA' : 'Opciones para añadir vinilos') ?>
        <span aria-hidden="true"> ↗</span>
      </a>
    </div>
  </div>
  <form id="collection-filters" class="collection-search" method="GET" action="<?= e(base_url('vinyls')) ?>">
    <input type="hidden" name="page" value="1">
    <label for="search">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="10" cy="10" r="6.5"/>
        <path d="m15 15 5 5"/>
      </svg>
      <span class="collection-sr-only">Buscar</span>
    </label>
    <input id="search" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" maxlength="200" placeholder="Busca un título, artista o productor…">
    <button type="submit">Buscar <span aria-hidden="true">→</span>
    </button>
  </form>
  <div class="collection-layout">
    <aside class="collection-sidebar" aria-label="Filtros de la colección">
      <details id="collection-filter-panel" open>
        <summary>Filtros <span aria-hidden="true">⌄</span>
        </summary>
        <div class="collection-filter-content">
          <p class="collection-side-label">MI SELECCIÓN</p>
          <label class="collection-check">
            <input form="collection-filters" type="checkbox" name="fav" value="1" <?= !empty($filters['fav']) ? 'checked' : '' ?>>
            <span class="collection-filter-icon" aria-hidden="true">♡</span>
            <span>Favoritos</span>
          </label>
          <label class="collection-check">
            <input form="collection-filters" type="checkbox" name="desired" value="1" <?= !empty($filters['desired']) ? 'checked' : '' ?>>
            <span class="collection-filter-icon" aria-hidden="true">◇</span>
            <span>Deseados</span>
          </label>
          <button form="collection-filters" type="submit" class="collection-button filter-apply">Aplicar filtros <span aria-hidden="true">→</span>
          </button>
          <a class="collection-clear" href="<?= e($clearUrl) ?>">Limpiar selección</a>
          <div class="collection-side-note">
            <svg viewBox="0 0 48 48" aria-hidden="true">
              <circle cx="24" cy="24" r="21"/>
              <circle cx="24" cy="24" r="14"/>
              <circle cx="24" cy="24" r="5"/>
            </svg>
            <p>Una colección.<br><strong>Muchas historias.</strong>
            </p>
            <span>Cada disco tiene la suya. Descúbrela en su ficha musical.</span>
          </div>
        </div>
      </details>
    </aside>
    <div class="collection-results">
      <div class="collection-toolbar">
        <p><strong><?= (int)$total ?></strong> <?= $hasFilters ? 'resultados' : 'discos en tu colección' ?>
          <span><?= $total > 0 ? 'Mostrando ' . $firstResult . '–' . $lastResult : 'Sin coincidencias' ?>
          </span>
        </p>
        <div>
          <label for="collection-sort">Ordenar por</label>
          <select id="collection-sort" name="sort" form="collection-filters" onchange="this.form.submit()">
            <?php foreach ($sortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php if ($hasFilters): ?>
      <div class="collection-active" aria-label="Selección aplicada">
        <span>Selección:</span>
        <?php if (($filters['q'] ?? '') !== ''): ?>
        <span class="collection-chip">“<?= e($filters['q']) ?>”</span>
        <?php endif; ?>
        <?php if (!empty($filters['fav'])): ?>
        <span class="collection-chip">Favoritos</span>
        <?php endif; ?>
        <?php if (!empty($filters['desired'])): ?>
        <span class="collection-chip">Deseados</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (empty($items)): ?>
      <div class="collection-empty">
        <span aria-hidden="true">◎</span>
        <h2>No hay discos para esta selección</h2>
        <p>Prueba otra búsqueda o añade un disco a tu colección.</p>
        <a class="collection-button" href="<?= e($clearUrl) ?>">Ver mi colección</a>
      </div>
      <?php else: ?>
      <div class="collection-grid">
        <?php foreach ($items as $v): ?>
        <?php
        $id = (int)($v['Id'] ?? 0);
        $isHighlight = ($highlightId > 0 && $id === $highlightId);

        $title = (string)($v['Title'] ?? '');
        $author = (string)($v['author_name'] ?? '');
        $producer = (string)($v['Producer'] ?? '');
        $year = (string)($v['Release_date'] ?? '');
        $genre = (string)($v['genre_name'] ?? '');
        $format = (string)($v['format_name'] ?? '');
        $condition = (string)($v['condition_name'] ?? '');
        $label = (string)($v['record_label_name'] ?? '');
        $edition = (string)($v['edition_name'] ?? '');
        $imagePath = \App\Models\CoverArt::url($v);

        $modalData = [
        'id' => $id,
        'title' => $title,
        'author' => $author !== '' ? $author : '—',
        'producer' => $producer !== '' ? $producer : '—',
        'year' => $year !== '' ? $year : '—',
        'genre' => $genre !== '' ? $genre : '—',
        'format' => $format !== '' ? $format : '—',
        'condition' => $condition !== '' ? $condition : '—',
        'label' => $label !== '' ? $label : '—',
        'edition' => $edition !== '' ? $edition : '—',
        'image' => $imagePath,
        'coverOrigin' => \App\Models\CoverArt::display($v)['origin'],
        'favorite' => !empty($v['Is_Favorite']),
        'desired' => !empty($v['Is_Desired']),
        'showUrl' => base_url('vinyls/show?id=' . $id . '&return_page=' . $currentPage . '&return_sort=' . $sortQ),
        ];

        $modalJson = htmlspecialchars(
        json_encode($modalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
        ENT_QUOTES,
        'UTF-8'
        );
        ?>

        <article id="vinyl-<?= $id ?>" data-vinyl-id="<?= $id ?>" class="collection-card <?= $isHighlight ? 'is-highlighted' : '' ?>">
          <div class="collection-artwork">
            <button type="button" class="collection-cover" data-vinyl-modal="<?= $modalJson ?>" aria-label="Ver carátula e información de <?= e($title) ?>"><img decoding="async" src="<?= e($imagePath) ?>" data-fallback="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>" alt="Carátula de <?= e($title) ?>" loading="lazy">
              <span class="collection-cover-hint" aria-hidden="true">Vista rápida <span>↗</span>
              </span>
            </button>
            <div class="collection-indicators">
              <?php if (!empty($v['Is_Favorite'])): ?>
              <span title="Favorito" aria-label="Favorito">♥</span>
              <?php endif; ?>
              <?php if (!empty($v['Is_Desired'])): ?>
              <span title="Deseado" aria-label="Deseado">◇</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="collection-card-body">
            <p class="caa-origin"><?= e($modalData['coverOrigin']) ?></p>
            <h2>
              <a href="<?= e($modalData['showUrl']) ?>" title="<?= e($title) ?>"><?= e($title) ?>
              </a>
            </h2>
            <p class="collection-artist"><?= e($author !== '' ? $author : 'Autor no indicado') ?>
            </p>
            <div class="collection-card-meta">
              <?php if ($year !== ''): ?>
              <span><?= e($year) ?>
              </span>
              <?php endif; ?>
              <?php if ($format !== ''): ?>
              <span class="collection-format <?= strcasecmp($format, 'CD') === 0 ? 'is-cd' : '' ?>"><?= e($format) ?>
              </span>
              <?php endif; ?>
              <?php if ($genre !== ''): ?>
              <span class="collection-genre"><?= e($genre) ?>
              </span>
              <?php endif; ?>
            </div>
            <div class="collection-card-actions">
              <a class="collection-record-link" href="<?= e($modalData['showUrl']) ?>">Ver ficha <span aria-hidden="true">↗</span>
              </a>
              <div>
                <a class="collection-edit" href="<?= e(base_url('vinyls/edit?id=' . $id . '&return_page=' . $currentPage . '&return_sort=' . $sortQ)) ?>">Editar</a>
                <form method="POST" action="<?= e(base_url('vinyls/delete')) ?>" class="delete-vinyl-form" data-title="<?= e($title) ?>">
                  <?= csrf_field() ?><?= collection_hidden_fields() ?>
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="page" value="<?= $currentPage ?>">
                  <input type="hidden" name="sort" value="<?= e($sort) ?>">
                  <input type="hidden" name="return_page" value="<?= $currentPage ?>">
                  <input type="hidden" name="return_sort" value="<?= e($sort) ?>">
                  <button type="submit" data-delete-trigger disabled class="collection-delete" aria-label="Eliminar" title="Eliminar este disco">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7M14 10v7"/>
                    </svg>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php if ($p !== null && $p->pages > 1): ?>
      <?php require __DIR__.'/pagination.php'; ?>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<div
  id="cover-modal" role="dialog" aria-modal="true" aria-labelledby="cover-modal-title" tabindex="-1"
  class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
  aria-hidden="true">
  <div
    id="cover-modal-panel"
    class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl border border-zinc-700 bg-zinc-950 shadow-2xl shadow-black/60">

    <button
      type="button"
      id="cover-modal-close"
      class="absolute right-4 top-4 z-10 rounded-xl border border-zinc-700 bg-zinc-900/90 px-3 py-2 text-zinc-300 hover:bg-zinc-800 hover:text-white"
      aria-label="Cerrar ventana">
      ✕
    </button>

    <div class="grid gap-6 p-5 md:grid-cols-[320px_1fr] md:p-6">
      <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
        <img decoding="async"
          data-fallback="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>"
          id="cover-modal-image"
          src="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>"
          alt=""
          class="aspect-square w-full object-cover">
      </div>

      <div class="flex min-w-0 flex-col">
        <div class="mb-4">
          <p id="cover-modal-origin" class="caa-origin"></p>
          <div id="cover-modal-badges" class="mb-3 flex flex-wrap gap-2"></div>

          <h2 id="cover-modal-title" class="break-words text-2xl font-bold text-zinc-100"></h2>

          <p class="mt-2 text-sm text-zinc-400">
            Año:
            <span id="cover-modal-year" class="font-semibold text-zinc-200"></span>
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Autor</div>
            <div id="cover-modal-author" class="mt-1 text-zinc-100"></div>
          </div>

          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Productor</div>
            <div id="cover-modal-producer" class="mt-1 text-zinc-100"></div>
          </div>

          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Género</div>
            <div id="cover-modal-genre" class="mt-1 text-zinc-100"></div>
          </div>

          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Formato</div>
            <div id="cover-modal-format" class="mt-1 text-zinc-100"></div>
          </div>

          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Estado</div>
            <div id="cover-modal-condition" class="mt-1 text-zinc-100"></div>
          </div>

          <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Edición</div>
            <div id="cover-modal-edition" class="mt-1 text-zinc-100"></div>
          </div>
        </div>

        <div class="mt-3 rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
          <div class="text-xs uppercase tracking-wide text-zinc-500">Discográfica</div>
          <div id="cover-modal-label" class="mt-1 text-zinc-100"></div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-end gap-3 border-t border-zinc-800 pt-5">
          <button
            type="button"
            id="cover-modal-close-secondary"
            class="rounded-xl border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white">
            Cerrar
          </button>

          <a
            id="cover-modal-show-link"
            href="#"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
            Ver ficha completa
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('cover-modal');

    if (!modal) {
      return;
    }

    const panel = document.getElementById('cover-modal-panel');
    const closeButtons = [
      document.getElementById('cover-modal-close'),
      document.getElementById('cover-modal-close-secondary')
    ].filter(Boolean);

    const image = document.getElementById('cover-modal-image');
    const title = document.getElementById('cover-modal-title');
    const year = document.getElementById('cover-modal-year');
    const author = document.getElementById('cover-modal-author');
    const producer = document.getElementById('cover-modal-producer');
    const genre = document.getElementById('cover-modal-genre');
    const format = document.getElementById('cover-modal-format');
    const condition = document.getElementById('cover-modal-condition');
    const edition = document.getElementById('cover-modal-edition');
    const label = document.getElementById('cover-modal-label');
    const badges = document.getElementById('cover-modal-badges');
    const showLink = document.getElementById('cover-modal-show-link');

    function text(value) {
      return value && String(value).trim() !== '' ? String(value) : '—';
    }

    function badge(label, classes) {
      const span = document.createElement('span');
      span.className = classes;
      span.textContent = label;
      return span;
    }

    let previousFocus = null;
    function openModal(data) {
      previousFocus = document.activeElement;
      title.textContent = text(data.title);
      year.textContent = text(data.year);
      author.textContent = text(data.author);
      producer.textContent = text(data.producer);
      genre.textContent = text(data.genre);
      format.textContent = text(data.format);
      condition.textContent = text(data.condition);
      edition.textContent = text(data.edition);
      label.textContent = text(data.label);

      image.src = text(data.image);
      document.getElementById('cover-modal-origin').textContent = text(data.coverOrigin);
      image.dataset.coverTitle = text(data.title);
      image.alt = 'Carátula de ' + text(data.title);

      showLink.href = data.showUrl || '#';

      badges.innerHTML = '';

      if (data.favorite) {
        badges.appendChild(
          badge('★ Favorito', 'rounded-full border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-xs text-amber-300')
        );
      }

      if (data.desired) {
        badges.appendChild(
          badge('◆ Deseado', 'rounded-full border border-sky-500/40 bg-sky-500/10 px-3 py-1 text-xs text-sky-300')
        );
      }

      modal.classList.remove('hidden');
      modal.classList.add('flex');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('overflow-hidden');
      document.querySelector('header').inert = true;
      modal.previousElementSibling.inert = true;
      closeButtons[0].focus();
    }

    function closeModal() {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('overflow-hidden');
      document.querySelector('header').inert = false;
      modal.previousElementSibling.inert = false;
      if (previousFocus) previousFocus.focus();
    }

    document.querySelectorAll('[data-vinyl-modal]').forEach((button) => {
      button.addEventListener('click', () => {
        try {
          openModal(JSON.parse(button.dataset.vinylModal || '{}'));
        } catch (error) {
          console.error('No se pudo abrir el popup del vinilo:', error);
        }
      });
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    if (panel) {
      panel.addEventListener('click', (event) => event.stopPropagation());
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Tab' && !modal.classList.contains('hidden')) {
        const focusable = [...modal.querySelectorAll('button, a[href]')];
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
      if (event.key === 'Escape'  && !modal.classList.contains('hidden')) {
        closeModal();
      }
    });
  })();
</script>

<?php if ($highlightId > 0 || $deleted === 1): ?>
<script>
  (function () {
    const highlightId = <?= (int)$highlightId ?>;
    const deleted = <?= (int)$deleted ?>;

    if (highlightId > 0) {
      const el = document.getElementById('vinyl-' + highlightId);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
          el.classList.remove(
            'is-highlighted',
            'border-emerald-400',
            'bg-emerald-950/30',
            'ring-2',
            'ring-emerald-500/60'
          );
          el.classList.add('border-zinc-800', 'bg-gradient-to-br', 'from-zinc-900', 'via-zinc-900', 'to-zinc-950');
        }, 3000);
      }
    }

    if (deleted === 1) {
      const msg = document.getElementById('delete-message');
      if (msg) {
        setTimeout(() => msg.remove(), 3000);
      }
    }

    setTimeout(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('highlight');
      url.searchParams.delete('deleted');
      url.hash = '';
      window.history.replaceState({}, '', url.toString());
    }, 3200);
  })();
</script>
<?php endif; ?>

<noscript><p class="mx-auto max-w-lg p-4 text-zinc-300">Para confirmar la eliminación de forma segura, activa JavaScript en el navegador.</p></noscript>
<dialog id="delete-dialog" aria-labelledby="delete-title" aria-describedby="delete-description" class="w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-zinc-700 bg-zinc-950 p-6 text-zinc-100 shadow-2xl backdrop:bg-black/75">
  <h2 id="delete-title" class="text-xl font-semibold">Eliminar vinilo</h2>
  <p id="delete-description" class="mt-4 text-zinc-300">Vas a eliminar <strong id="delete-record-title" class="break-words text-white"></strong> de tu colección. Esta acción no se puede deshacer.</p>
  <div class="mt-6 flex flex-wrap justify-end gap-3">
    <button type="button" id="delete-cancel" autofocus class="rounded-xl border border-zinc-600 px-5 py-3 hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-400">Cancelar</button>
    <button type="button" id="delete-confirm" class="rounded-xl bg-red-700 px-5 py-3 font-semibold hover:bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-400 disabled:opacity-50">Eliminar definitivamente</button>
  </div>
</dialog>
<script src="<?= base_url('assets/js/delete-confirmation.js') ?>" defer></script>

<script src="<?= e(base_url('assets/js/collection.js')) ?>" defer></script>
