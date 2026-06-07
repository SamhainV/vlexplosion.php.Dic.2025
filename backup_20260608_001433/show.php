<?php
$vinyl = $vinyl ?? null;

$returnPage = max(1, (int)($return_page ?? ($_GET['return_page'] ?? 1)));
$returnSort = (string)($return_sort ?? ($_GET['return_sort'] ?? 'newest'));
$returnUrl = base_url('vinyls?page=' . $returnPage . '&sort=' . urlencode($returnSort));
?>

<?php if (!$vinyl): ?>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
    Vinilo no encontrado.
    <div class="mt-4">
      <a class="underline" href="<?= $returnUrl ?>">Volver</a>
    </div>
  </div>
<?php else: ?>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold"><?= e($vinyl['Title']) ?></h1>

    <a
      class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800"
      href="<?= $returnUrl ?>">
      Volver
    </a>
  </div>

  <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 space-y-2">
    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Autor:</span>
      <?= e((string)($vinyl['author_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Género:</span>
      <?= e((string)($vinyl['genre_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Formato:</span>
      <?= e((string)($vinyl['format_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Estado:</span>
      <?= e((string)($vinyl['condition_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Discográfica:</span>
      <?= e((string)($vinyl['record_label_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Edición:</span>
      <?= e((string)($vinyl['edition_name'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Productor:</span>
      <?= e((string)($vinyl['Producer'] ?? '')) ?>
    </div>

    <div class="text-sm text-zinc-300">
      <span class="text-zinc-500">Fecha:</span>
      <?= e((string)($vinyl['Release_date'] ?? '')) ?>
    </div>

    <div class="flex gap-2 pt-2">
      <?php if (!empty($vinyl['Is_Favorite'])): ?>
        <span class="px-2 py-0.5 rounded bg-amber-900/40 border border-amber-700 text-xs">Favorito</span>
      <?php endif; ?>

      <?php if (!empty($vinyl['Is_Desired'])): ?>
        <span class="px-2 py-0.5 rounded bg-sky-900/40 border border-sky-700 text-xs">Deseado</span>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
