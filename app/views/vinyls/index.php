<?php
/** @var array $items */
/** @var App\Core\Paginator|null $p */
/** @var string $sort */
/** @var array $sortOptions */

$highlightId = (int)($_GET['highlight'] ?? 0);
$deleted = (int)($_GET['deleted'] ?? 0);

$sort = (string)($sort ?? ($_GET['sort'] ?? 'newest'));

$sortOptions = $sortOptions ?? [
  'newest' => 'Nuevos primero',
  'oldest' => 'Antiguos primero',
  'title_asc' => 'Título A → Z',
  'title_desc' => 'Título Z → A',
  'year_asc' => 'Año ↑',
  'year_desc' => 'Año ↓',
  'producer_asc' => 'Producer A → Z',
  'producer_desc' => 'Producer Z → A',
  'fav_first' => 'Favoritos primero',
  'desired_first' => 'Deseados primero',
  'fav_then_title' => 'Fav primero + A→Z',
  'desired_then_title' => 'Deseado primero + A→Z',
];

if (!isset($sortOptions[$sort])) {
  $sort = 'newest';
}

$sortQ = urlencode($sort);

$items = $items ?? [];
$p = $p ?? null;
$total = $p->total ?? count($items);
$currentPage = $p !== null ? (int)$p->page : max(1, (int)($_GET['page'] ?? 1));
?>

<section class="space-y-6">

  <?php if ($deleted === 1): ?>
    <div id="delete-message" class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
      Vinilo eliminado correctamente.
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
      href="<?= base_url('vinyls/create?sort=' . $sortQ) ?>">
      <span class="text-lg leading-none">+</span>
      Añadir vinilo
    </a>
  </div>

  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4 shadow-lg shadow-black/20">
    <form method="GET" action="<?= base_url('vinyls') ?>" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <input type="hidden" name="page" value="1">

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
      <h2 class="text-lg font-semibold text-zinc-100">No tienes vinilos aún</h2>
      <p class="mt-1 text-sm text-zinc-400">Añade el primero y empieza tu colección.</p>
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
                href="<?= base_url('vinyls/show?id=' . $id) ?>"
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

            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-700 bg-zinc-950 text-lg font-black text-emerald-400 shadow-inner">
              <?= e(mb_strtoupper(mb_substr($title !== '' ? $title : '?', 0, 1))) ?>
            </div>
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
              href="<?= base_url('vinyls/show?id=' . $id) ?>"
              class="rounded-xl border border-zinc-700 px-3 py-2 text-xs font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white transition">
              Ver ficha
            </a>

            <div class="flex items-center gap-2">
              <a
                href="<?= base_url('vinyls/edit?id=' . $id) ?>"
                class="rounded-xl bg-blue-600/90 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 transition">
                Editar
              </a>

              <form
                method="POST"
                action="<?= base_url('vinyls/delete') ?>"
                onsubmit="return confirm('¿Seguro que quieres eliminar este vinilo?');">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="page" value="<?= $currentPage ?>">
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <button
                  type="submit"
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
