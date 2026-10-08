<?php
// Variables esperadas desde el controller:
// $error, $old, $genres, $formats, $conditions, $labels, $editions
$old = $old ?? [];
$returnPage = max(1, (int)($return_page ?? ($_GET['page'] ?? 1)));
$returnSort = (string)($return_sort ?? ($_GET['sort'] ?? 'newest'));
$returnUrl = base_url('vinyls?' . collection_query($returnPage, $returnSort));
?>

<article id="vinyl-editor" class="mx-auto my-8 w-full max-w-[1040px] rounded-2xl border border-zinc-800 bg-zinc-950/95 shadow-2xl">
  <div class="border-b border-zinc-800 p-5 sm:p-8">
    <p class="text-xs uppercase tracking-widest text-emerald-400">Tu colección</p>
    <h1 id="vinyl-form-title" class="mt-2 text-3xl sm:text-4xl font-semibold text-zinc-100"><?= !empty($editing) ? 'Editar vinilo' : 'Añadir vinilo' ?></h1>
    <p class="mt-3 text-sm text-zinc-400">Datos del disco, autores y carátula. Los campos del disco son obligatorios.</p>
    <?php if (!empty($error)): ?><div role="alert" class="mt-5 rounded-xl border border-red-800 bg-red-900/40 p-4 text-sm"><?= e($error) ?></div><?php endif; ?>
  </div>
    <form method="POST" action="<?= base_url(!empty($editing) ? 'vinyls/update' : 'vinyls/store') ?>" enctype="multipart/form-data" id="vinyl-form" class="p-5 sm:p-8">
      <?= csrf_field() ?>
      <?= collection_hidden_fields() ?>
      <?php if (!empty($editing)): ?><input type="hidden" name="id" value="<?= (int)$vinylId ?>"><?php endif; ?>
      <input type="hidden" name="return_page" value="<?= $returnPage ?>">
      <input type="hidden" name="return_sort" value="<?= e($returnSort) ?>">

      <div id="vinyl-fields" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
      <div class="md:col-span-2 space-y-2"><label for="title" class="block text-sm">Título</label>
      <input
        id="title" autofocus required maxlength="65"
        name="title"
        placeholder="Título"
        value="<?= e((string)($old['title'] ?? '')) ?>"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 placeholder-zinc-500 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500" /></div>

      <div class="space-y-2"><label for="genre_id" class="block text-sm">Género</label>
      <select
        id="genre_id" required
        name="genre_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <option value="">Género musical</option>
        <?php foreach (($genres ?? []) as $g): ?>
          <option value="<?= (int)$g['id'] ?>" <?= ((string)($old['genre_id'] ?? '') === (string)$g['id']) ? 'selected' : '' ?>>
            <?= e($g['name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>

      <div class="md:col-span-2"><fieldset id="authors-fields" class="space-y-2">
        <legend class="text-sm">Autores</legend>
        <?php foreach ((($old['authors'] ?? []) ?: [$old['author'] ?? '']) as $i => $author): ?>
          <div class="flex gap-2"><input aria-label="Autor <?= (int)$i + 1 ?>" name="authors[]" maxlength="50" required value="<?= e((string)$author) ?>" class="min-w-0 w-full rounded-lg bg-zinc-900 border border-zinc-800 px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none"><button type="button" class="remove-author rounded-lg border border-zinc-700 px-3 text-zinc-300 hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-400" aria-label="Quitar este autor">✕</button></div>
        <?php endforeach; ?>
      </fieldset>
      <button type="button" id="add-author" class="text-sm text-emerald-300">Añadir otro autor</button></div>
      <script>
      document.addEventListener('DOMContentLoaded', () => {
        const fields = document.getElementById('authors-fields');
        document.getElementById('add-author').addEventListener('click', () => {
          if (fields.querySelectorAll('input').length >= 20) return;
          const row = fields.querySelector('div').cloneNode(true);
          const input = row.querySelector('input'); input.value = ''; input.setAttribute('aria-label', 'Otro autor');
          fields.appendChild(row); input.focus();
        });
        fields.addEventListener('click', (event) => {
          if (event.target.closest('.remove-author') && fields.querySelectorAll('input').length > 1) event.target.closest('div').remove();
        });
      });
      </script>

      <div class="space-y-2"><label for="format_id" class="block text-sm">Formato</label>
      <select
        id="format_id" required
        name="format_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <option value="">Formato</option>
        <?php foreach (($formats ?? []) as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ((string)($old['format_id'] ?? '') === (string)$f['id']) ? 'selected' : '' ?>>
            <?= e($f['name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>

      <div class="space-y-2"><label for="condition_id" class="block text-sm">Estado</label>
      <select
        id="condition_id" required
        name="condition_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <option value="">Estado de conservación</option>
        <?php foreach (($conditions ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((string)($old['condition_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>

      <div class="space-y-2"><label for="record_label_id" class="block text-sm">Discográfica</label>
      <select
        id="record_label_id" required
        name="record_label_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <option value="">Discográfica</option>
        <?php foreach (($labels ?? []) as $l): ?>
          <option value="<?= (int)$l['id'] ?>" <?= ((string)($old['record_label_id'] ?? '') === (string)$l['id']) ? 'selected' : '' ?>>
            <?= e($l['name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>

      <div class="space-y-2"><label for="producer" class="block text-sm">Productor</label>
      <input
        id="producer" required maxlength="50"
        name="producer"
        placeholder="Productor"
        value="<?= e((string)($old['producer'] ?? '')) ?>"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 placeholder-zinc-500 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500" /></div>

      <?php
      $currentYear = (int)date('Y');
      $selectedYear = (string)($old['release_date'] ?? $currentYear);
      ?>

      <div class="space-y-2"><label for="release_date" class="block text-sm">Año</label>
      <select
        id="release_date" required
        name="release_date"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <?php for ($y = min(2155, $currentYear + 1); $y >= 1901; $y--): ?>
          <option value="<?= $y ?>" <?= ($selectedYear === (string)$y) ? 'selected' : '' ?>>
            <?= $y ?>
          </option>
        <?php endfor; ?>
      </select></div>

      <div class="space-y-2"><label for="edition_id" class="block text-sm">Edición</label>
      <select
        id="edition_id" required
        name="edition_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
        <option value="">Edición</option>
        <?php foreach (($editions ?? []) as $ed): ?>
          <option value="<?= (int)$ed['id'] ?>" <?= ((string)($old['edition_id'] ?? '') === (string)$ed['id']) ? 'selected' : '' ?>>
            <?= e($ed['name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>

      </div>
      <section aria-labelledby="cover-label" class="mt-8 grid md:grid-cols-[220px_1fr] gap-6 rounded-xl border border-zinc-800 bg-zinc-900/70 p-5">
      <img id="current-cover" src="<?= e(\App\Core\CoverStore::url($currentCover ?? null)) ?>" data-fallback="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>" alt="Carátula actual" class="w-full max-w-[220px] aspect-square rounded-xl object-cover border border-zinc-700">
      <div>
        <label id="cover-label" for="cover" class="mb-2 block text-sm font-medium text-zinc-200">
          Carátula
        </label>

        <input
          id="cover"
          type="file"
          name="cover"
          accept="image/jpeg,image/png,image/webp"
          class="block w-full cursor-pointer rounded-lg border border-zinc-800 bg-zinc-950 text-sm text-zinc-300 file:mr-4 file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-emerald-500" />

        <p class="mt-2 text-xs text-zinc-500">
          JPG, PNG o WEBP, hasta 5 MB. Si no seleccionas una imagen, se conserva la carátula actual.
        </p>

        <img
          data-fallback="<?= e(\App\Core\CoverStore::fallbackUrl()) ?>"
          id="coverPreview"
          src=""
          alt="Vista previa de la carátula"
          class="mt-3 hidden h-32 w-32 rounded-xl border border-zinc-700 object-cover shadow-lg" />
      </div></section>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const input = document.getElementById('cover');
          const preview = document.getElementById('coverPreview');

          if (!input || !preview) {
            return;
          }

          let previewUrl = null;
          const release = () => { if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; } };
          window.addEventListener('pagehide', release);
          input.addEventListener('change', function () {
            release();
            const file = input.files && input.files[0];

            if (!file) {
              preview.src = '';
              preview.classList.add('hidden');
              return;
            }

            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.classList.remove('hidden');
          });
        });
      </script>

      <div class="mt-8 flex flex-wrap gap-5 rounded-xl border border-zinc-800 bg-zinc-900 p-5 text-zinc-200">
        <label class="inline-flex items-center gap-3 text-sm font-medium">
          <input type="checkbox" name="is_favorite" class="h-4 w-4 accent-emerald-500"
            <?= !empty($old['is_favorite']) ? 'checked' : '' ?> />
          Favorito
        </label>

        <label class="inline-flex items-center gap-3 text-sm font-medium">
          <input type="checkbox" name="is_desired" class="h-4 w-4 accent-emerald-500"
            <?= !empty($old['is_desired']) ? 'checked' : '' ?> />
          Deseado
        </label>
      </div>

      <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-zinc-800 pt-6"><button
        type="submit"
        class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-3 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-400">
        <?= !empty($editing) ? 'Guardar cambios' : 'Añadir vinilo' ?>
      </button>

      <a href="<?= $returnUrl ?>" class="rounded-xl border border-zinc-700 px-6 py-3 text-sm text-zinc-200 hover:bg-zinc-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-400">
        Cancelar
      </a></div>
    </form>
</article>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('vinyl-form'), button = form.querySelector('button[type="submit"]');
  form.addEventListener('submit', event => {
    if (form.dataset.submitted === 'true') { event.preventDefault(); return; }
    form.dataset.submitted = 'true'; button.disabled = true;
  });
  window.addEventListener('pageshow', () => { delete form.dataset.submitted; button.disabled = false; });
});
</script>
