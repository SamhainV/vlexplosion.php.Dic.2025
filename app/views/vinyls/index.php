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

$coverUrl = [\App\Core\CoverStore::class, 'url'];
?>

<section class="space-y-6">

  <?php if ($flash): ?>
    <div id="delete-message" role="status" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
      <?= e($flash) ?>
    </div>
  <?php endif; ?>

  <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs text-emerald-300 mb-3">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        Colección activa
      </div>

      <h1 class="text-3xl font-bold tracking-tight text-zinc-100">Mis vinilos</h1>

      <p class="mt-2 text-sm text-zinc-400">
        Total:
        <span class="font-semibold text-zinc-100"><?= (int)$total ?></span>
        discos registrados
      </p>
    </div>

    <a
      class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/40 hover:bg-emerald-500 transition"
      href="<?= base_url('vinyls/create?page=' . $currentPage . '&sort=' . $sortQ) ?>">
      <span class="text-lg leading-none">+</span>
      Añadir vinilo
    </a>
  </div>

  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4 shadow-lg shadow-black/20">
    <form method="GET" action="<?= base_url('vinyls') ?>" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <input type="hidden" name="page" value="1">
      <div class="flex flex-wrap items-center gap-3">
        <label for="search">Buscar</label><input id="search" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" maxlength="200" placeholder="Título, autor o productor" class="rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2">
        <label><input type="checkbox" name="fav" value="1" <?= !empty($filters['fav']) ? 'checked' : '' ?>> Favoritos</label>
        <label><input type="checkbox" name="desired" value="1" <?= !empty($filters['desired']) ? 'checked' : '' ?>> Deseados</label>
        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2">Aplicar</button>
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wide text-zinc-500 mb-1">Ordenar colección</label>

        <select
          name="sort"
          onchange="this.form.submit()"
          class="w-full sm:w-72 rounded-xl bg-zinc-950 text-zinc-100 border border-zinc-700 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600">
          <?php foreach ($sortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($sort === $key) ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="text-xs text-zinc-500">
        Página actual:
        <span class="text-zinc-300">
          <?= $p !== null ? (int)$p->page . ' / ' . (int)$p->pages : '1 / 1' ?>
        </span>
      </div>
    </form>
  </div>

  <?php if (empty($items)): ?>

    <div class="rounded-2xl border border-dashed border-zinc-700 bg-zinc-900/60 p-8 text-center">
      <div class="text-4xl mb-3">🎵</div>
      <h2 class="text-lg font-semibold text-zinc-100">No hay vinilos para esta selección</h2>
      <p class="mt-1 text-sm text-zinc-400">Prueba otra búsqueda o añade un vinilo.</p>
    </div>

  <?php else: ?>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
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
        $imagePath = $coverUrl($v['Image_Path'] ?? null);

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

        <article
          id="vinyl-<?= $id ?>"
          data-vinyl-id="<?= $id ?>"
          class="group relative overflow-hidden rounded-3xl border p-5 shadow-xl shadow-black/20 transition-all duration-200 hover:-translate-y-1 hover:shadow-emerald-950/20
          <?= $isHighlight
            ? 'border-emerald-400 bg-emerald-950/30 ring-2 ring-emerald-500/60'
            : 'border-zinc-800 bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-950 hover:border-emerald-500/50'
          ?>">

          <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500/80 via-sky-500/60 to-fuchsia-500/60 opacity-60"></div>

          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <a
                href="<?= base_url('vinyls/show?id=' . $id . '&return_page=' . $currentPage . '&return_sort=' . $sortQ) ?>"
                class="block truncate text-lg font-bold text-zinc-100 hover:text-emerald-300 transition"
                title="<?= e($title) ?>">
                <?= e($title) ?>
              </a>

              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <?php if (!empty($v['Is_Favorite'])): ?>
                  <span class="rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-amber-300">★ Favorito</span>
                <?php endif; ?>

                <?php if (!empty($v['Is_Desired'])): ?>
                  <span class="rounded-full border border-sky-500/40 bg-sky-500/10 px-2 py-0.5 text-sky-300">◆ Deseado</span>
                <?php endif; ?>

                <?php if ($year !== ''): ?>
                  <span class="rounded-full border border-zinc-700 bg-zinc-950/70 px-2 py-0.5 text-zinc-300"><?= e($year) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <button
              type="button"
              class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-zinc-700 bg-zinc-950 shadow-inner transition hover:scale-105 hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              data-vinyl-modal='<?= $modalJson ?>'
              aria-label="Ver carátula e información de <?= e($title !== '' ? $title : 'vinilo') ?>">
              <img decoding="async"
          data-fallback="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>"
                src="<?= e($imagePath) ?>"
                alt="Carátula de <?= e($title !== '' ? $title : 'vinilo') ?>"
                class="h-full w-full object-cover"
                loading="lazy">
            </button>
          </div>

          <div class="mt-5 grid gap-2 text-sm">
            <div class="flex items-start gap-2">
              <span class="mt-0.5 w-5 text-center text-emerald-400">🎙</span>
              <div>
                <div class="text-xs text-zinc-500">Autor</div>
                <div class="text-zinc-200"><?= e($author !== '' ? $author : '—') ?></div>
              </div>
            </div>

            <div class="flex items-start gap-2">
              <span class="mt-0.5 w-5 text-center text-purple-400">🎚</span>
              <div>
                <div class="text-xs text-zinc-500">Productor</div>
                <div class="text-zinc-200"><?= e($producer !== '' ? $producer : '—') ?></div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2">
              <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
                <div class="text-xs text-zinc-500">Género</div>
                <div class="truncate text-sm text-zinc-200" title="<?= e($genre) ?>"><?= e($genre !== '' ? $genre : '—') ?></div>
              </div>

              <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
                <div class="text-xs text-zinc-500">Formato</div>
                <div class="truncate text-sm text-zinc-200"><?= e($format !== '' ? $format : '—') ?></div>
              </div>

              <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
                <div class="text-xs text-zinc-500">Estado</div>
                <div class="truncate text-sm text-zinc-200"><?= e($condition !== '' ? $condition : '—') ?></div>
              </div>

              <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
                <div class="text-xs text-zinc-500">Edición</div>
                <div class="truncate text-sm text-zinc-200"><?= e($edition !== '' ? $edition : '—') ?></div>
              </div>
            </div>

            <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
              <div class="text-xs text-zinc-500">Discográfica</div>
              <div class="truncate text-sm text-zinc-200" title="<?= e($label) ?>"><?= e($label !== '' ? $label : '—') ?></div>
            </div>
          </div>

          <div class="mt-5 flex items-center justify-between gap-3 border-t border-zinc-800 pt-4">
            <a
              href="<?= base_url('vinyls/show?id=' . $id . '&return_page=' . $currentPage . '&return_sort=' . $sortQ) ?>"
              class="rounded-xl border border-zinc-700 px-3 py-2 text-xs font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white transition">
              Ver ficha
            </a>

            <div class="flex items-center gap-2">
              <a
                href="<?= base_url('vinyls/edit?id=' . $id . '&return_page=' . $currentPage . '&return_sort=' . $sortQ) ?>"
                class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 transition">
                Editar
              </a>

              <form
                method="POST"
                action="<?= base_url('vinyls/delete') ?>"
                class="delete-vinyl-form" data-title="<?= e($v['Title']) ?>">
      <?= csrf_field() ?>
      <?= collection_hidden_fields() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="page" value="<?= $currentPage ?>">
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <input type="hidden" name="return_page" value="<?= $currentPage ?>">
                <input type="hidden" name="return_sort" value="<?= e($sort) ?>">

                <button
                  type="submit" data-delete-trigger disabled
                  class="rounded-xl bg-red-600/90 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500 transition">
                  Eliminar
                </button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($p !== null && $p->pages > 1): ?>
      <div class="mt-8 flex items-center justify-center gap-3 text-sm">
        <?php
        $prev = max(1, $p->page - 1);
        $next = min($p->pages, $p->page + 1);
        ?>

        <a
          class="rounded-xl border border-zinc-700 px-4 py-2 text-zinc-300 hover:bg-zinc-800 hover:text-white transition <?= $p->page === 1 ? 'opacity-40 pointer-events-none' : '' ?>"
          href="<?= base_url('vinyls?page=' . $prev . '&sort=' . $sortQ) ?>">
          Anterior
        </a>

        <span class="rounded-xl border border-zinc-800 bg-zinc-900/70 px-4 py-2 text-zinc-300">
          Página <?= (int)$p->page ?> / <?= (int)$p->pages ?>
        </span>

        <a
          class="rounded-xl border border-zinc-700 px-4 py-2 text-zinc-300 hover:bg-zinc-800 hover:text-white transition <?= $p->page === $p->pages ? 'opacity-40 pointer-events-none' : '' ?>"
          href="<?= base_url('vinyls?page=' . $next . '&sort=' . $sortQ) ?>">
          Siguiente
        </a>
      </div>
    <?php endif; ?>

  <?php endif; ?>
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
