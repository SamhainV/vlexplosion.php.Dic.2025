<?php
$highlightId = (int)($_GET['highlight'] ?? 0);

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
?>

<a class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm mb-4"
   href="<?= base_url('vinyls/create?sort=' . $sortQ) ?>">
  + Añadir vinilo
</a>

<h1 class="text-2xl font-semibold mb-6">Mis vinilos</h1>

<div class="mb-4 text-sm text-zinc-400">
  Total: <span class="text-zinc-200 font-medium"><?= (int)$total ?></span>
</div>

<form method="GET" action="<?= base_url('vinyls') ?>" class="mb-4 flex items-center gap-3">
  <input type="hidden" name="page" value="1">
  <label class="text-sm text-zinc-400">Orden:</label>

  <select
    name="sort"
    onchange="this.form.submit()"
    class="rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-zinc-700"
  >
    <?php foreach ($sortOptions as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= ($sort === $key) ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($items)): ?>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
    No tienes vinilos aún.
  </div>
<?php else: ?>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($items as $v): ?>
      <?php
        $id = (int)($v['Id'] ?? 0);
        $isHighlight = ($highlightId > 0 && $id === $highlightId);
      ?>

      <div
        id="vinyl-<?= $id ?>"
        data-vinyl-id="<?= $id ?>"
        class="rounded-2xl border p-4 transition
          <?= $isHighlight
            ? 'border-emerald-500 bg-emerald-900/20 ring-2 ring-emerald-500/60 shadow-lg shadow-emerald-500/10'
            : 'border-zinc-800 bg-zinc-900'
          ?>"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <a class="font-semibold hover:underline"
               href="<?= base_url('vinyls/show?id=' . $id) ?>">
              <?= e($v['Title'] ?? '') ?>
            </a>

            <div class="text-xs text-zinc-400 mt-1">
              <?= e((string)($v['Producer'] ?? '')) ?> · <?= e((string)($v['Release_date'] ?? '')) ?>
            </div>
          </div>

          <div class="text-xs text-zinc-300 flex flex-col items-end gap-1">
            <?php if (!empty($v['Is_Favorite'])): ?>
              <span class="px-2 py-0.5 rounded bg-amber-900/40 border border-amber-700">Fav</span>
            <?php endif; ?>

            <?php if (!empty($v['Is_Desired'])): ?>
              <span class="px-2 py-0.5 rounded bg-sky-900/40 border border-sky-700">Wish</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($p !== null && $p->pages > 1): ?>
    <div class="mt-8 flex items-center justify-center gap-2 text-sm">
      <?php
      $prev = max(1, $p->page - 1);
      $next = min($p->pages, $p->page + 1);
      ?>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === 1 ? 'opacity-40 pointer-events-none' : '' ?>"
         href="<?= base_url('vinyls?page=' . $prev . '&sort=' . $sortQ) ?>">
        Anterior
      </a>

      <span class="px-3 py-1 text-zinc-300">
        Página <?= (int)$p->page ?> / <?= (int)$p->pages ?>
      </span>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === $p->pages ? 'opacity-40 pointer-events-none' : '' ?>"
         href="<?= base_url('vinyls?page=' . $next . '&sort=' . $sortQ) ?>">
        Siguiente
      </a>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php if ($highlightId > 0): ?>
<script>
  (function () {
    const id = <?= (int)$highlightId ?>;
    const el = document.getElementById('vinyl-' + id);
    if (!el) return;

    el.scrollIntoView({ behavior: 'smooth', block: 'center' });

    setTimeout(() => {
      el.classList.remove(
        'border-emerald-500',
        'bg-emerald-900/20',
        'ring-2',
        'ring-emerald-500/60',
        'shadow-lg',
        'shadow-emerald-500/10'
      );

      el.classList.add('border-zinc-800', 'bg-zinc-900');

      const url = new URL(window.location.href);
      url.searchParams.delete('highlight');
      url.hash = '';
      window.history.replaceState({}, '', url.toString());
    }, 3000);
  })();
</script>
<?php endif; ?>