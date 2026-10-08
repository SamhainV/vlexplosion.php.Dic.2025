<?php
$vinyl = $vinyl ?? null;
$returnPage = max(1, (int)($return_page ?? ($_GET['return_page'] ?? 1)));
$returnSort = (string)($return_sort ?? ($_GET['return_sort'] ?? 'newest'));
$returnUrl = base_url('vinyls?' . collection_query($returnPage, $returnSort));
$fallback = \App\Core\CoverStore::fallbackUrl();
$imagePath = $vinyl ? \App\Core\CoverStore::url($vinyl['Image_Path'] ?? null) : $fallback;
$title = (string)($vinyl['Title'] ?? '');
$editUrl = $vinyl ? base_url('vinyls/edit?id=' . (int)$vinyl['Id'] . '&return_page=' . $returnPage . '&return_sort=' . urlencode($returnSort) . '&' . http_build_query(\App\Core\CollectionFilter::read())) : '';
$metadata = [
    'Año de publicación' => $vinyl['Release_date'] ?? '',
    'Género' => $vinyl['genre_name'] ?? '',
    'Formato' => $vinyl['format_name'] ?? '',
    'Estado de conservación' => $vinyl['condition_name'] ?? '',
    'Discográfica' => $vinyl['record_label_name'] ?? '',
    'Edición' => $vinyl['edition_name'] ?? '',
    'Productor' => $vinyl['Producer'] ?? '',
];
?>

<?php if (!$vinyl): ?>
  <section class="mx-auto max-w-[1040px] rounded-2xl border border-zinc-800 bg-zinc-950/90 p-8" aria-labelledby="missing-vinyl-title">
    <h1 id="missing-vinyl-title" class="text-xl font-semibold">Vinilo no encontrado</h1>
    <a class="mt-5 inline-flex rounded-xl border border-zinc-700 px-4 py-2 text-sm hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-400" href="<?= e($returnUrl) ?>">Volver a la colección</a>
  </section>
<?php else: ?>
  <article id="vinyl-detail" class="mx-auto w-full max-w-[1040px] overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-950/95 shadow-2xl shadow-black/40" aria-labelledby="vinyl-title">
    <div class="flex items-center justify-between gap-4 border-b border-zinc-800 px-5 py-4 sm:px-8">
      <p class="text-xs font-medium uppercase tracking-[0.18em] text-emerald-400">Ficha de la colección</p>
      <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true"></span>
    </div>
    <div class="grid gap-7 p-5 sm:gap-8 sm:p-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
      <figure id="vinyl-artwork" class="min-w-0 self-start">
        <div class="overflow-hidden rounded-2xl border border-zinc-700/70 bg-zinc-900 shadow-xl shadow-black/40">
          <img id="vinyl-cover" decoding="async" fetchpriority="high" width="640" height="640"
            data-fallback="<?= e($fallback) ?>" data-cover-title="<?= e($title) ?>"
            src="<?= e($imagePath) ?>"
            alt="<?= e(($imagePath === $fallback ? 'Carátula predeterminada de ' : 'Carátula de ') . $title) ?>"
            class="aspect-square h-auto w-full object-cover">
        </div>
        <figcaption class="sr-only">Carátula de <?= e($title) ?></figcaption>
      </figure>
      <div id="vinyl-information" class="flex min-w-0 flex-col">
        <div class="mb-6">
          <div class="mb-3 flex flex-wrap gap-2">
            <?php if (!empty($vinyl['Is_Favorite'])): ?>
              <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-200">★ Favorito</span>
            <?php endif; ?>
            <?php if (!empty($vinyl['Is_Desired'])): ?>
              <span class="rounded-full border border-sky-500/30 bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-200">◆ Deseado</span>
            <?php endif; ?>
          </div>
          <h1 id="vinyl-title" class="break-words text-3xl font-bold leading-tight tracking-tight text-zinc-50 [overflow-wrap:anywhere] lg:text-4xl"><?= e($title) ?></h1>
          <p class="mt-3 break-words text-lg leading-relaxed text-emerald-300 [overflow-wrap:anywhere]"><span class="sr-only">Autores: </span><?= e((string)($vinyl['author_name'] ?? '')) ?></p>
        </div>
        <dl class="grid grid-cols-1 gap-x-6 gap-y-5 border-t border-zinc-800 pt-6 sm:grid-cols-2">
          <?php foreach ($metadata as $label => $value): ?>
            <div class="min-w-0">
              <dt class="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-400"><?= e($label) ?></dt>
              <dd class="break-words text-sm leading-relaxed text-zinc-200 [overflow-wrap:anywhere]"><?= e((string)$value) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
        <nav aria-label="Acciones del vinilo" class="mt-7 flex flex-wrap gap-3 border-t border-zinc-800 pt-5">
          <a id="vinyl-edit-link" href="<?= e($editUrl) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-400">Editar</a>
          <a id="vinyl-return-link" href="<?= e($returnUrl) ?>" class="inline-flex items-center justify-center rounded-xl border border-zinc-700 bg-zinc-900 px-5 py-2.5 text-sm font-medium text-zinc-200 transition hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-400">Volver</a>
        </nav>
      </div>
    </div>
  </article>
<?php endif; ?>
