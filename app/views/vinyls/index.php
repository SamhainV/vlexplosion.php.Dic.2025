<?php
$highlightId = (int)($_GET['highlight'] ?? 0);
?>

<a class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm mb-4"
   href="/vinyls/create">
  + Añadir vinilo
</a>

<h1 class="text-2xl font-semibold mb-6">Mis vinilos</h1>

<div class="mb-4 text-sm text-zinc-400">
  Total: <span class="text-zinc-200 font-medium"><?= (int)$p->total ?></span>
</div>

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
               href="/vinyls/show?id=<?= $id ?>">
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

  <!-- Paginación -->
  <?php if ($p->pages > 1): ?>
    <div class="mt-8 flex items-center justify-center gap-2 text-sm">
      <?php
      $prev = max(1, $p->page - 1);
      $next = min($p->pages, $p->page + 1);
      ?>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === 1 ? 'opacity-40 pointer-events-none' : '' ?>"
         href="/vinyls?page=<?= $prev ?>">Anterior</a>

      <span class="px-3 py-1 text-zinc-300">
        Página <?= (int)$p->page ?> / <?= (int)$p->pages ?>
      </span>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === $p->pages ? 'opacity-40 pointer-events-none' : '' ?>"
         href="/vinyls?page=<?= $next ?>">Siguiente</a>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php if ($highlightId > 0): ?>
<script>
  (function () {
    const id = <?= (int)$highlightId ?>;
    const el = document.getElementById('vinyl-' + id);
    if (!el) return;

    // Scroll suave por si el navegador no lo hace (a veces con layout tarda)
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Quitar highlight después de 3s
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

      // Limpiar URL: quitar highlight y hash, manteniendo page
      const url = new URL(window.location.href);
      url.searchParams.delete('highlight');
      url.hash = '';
      window.history.replaceState({}, '', url.toString());
    }, 3000);
  })();
</script>
<?php endif; ?>
