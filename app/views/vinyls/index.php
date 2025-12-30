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
      <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
        <div class="flex items-start justify-between gap-3">
          <div>

            <a class="font-semibold hover:underline"
              href="/vinyls/show?id=<?= (int)$v['Id'] ?>">
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