<?php
// Variables esperadas desde el controller:
// $error, $old, $genres, $formats, $conditions, $labels, $editions
$old = $old ?? [];
?>

<?php if (!empty($error)): ?>
  <div class="fixed inset-x-0 top-4 z-50 flex justify-center px-4">
    <div class="w-full max-w-md rounded-xl bg-red-900/50 border border-red-800 px-4 py-3 text-sm shadow-lg">
      <?= e($error) ?>
    </div>
  </div>
<?php endif; ?>

<div class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 px-4 py-8">
  <div class="relative w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-950/95 shadow-2xl">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-800">
      <h2 class="text-lg font-semibold text-zinc-100">Añadir vinilo</h2>
      <a
        href="/vinyls"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900 hover:bg-zinc-800 text-zinc-200"
        aria-label="Cerrar">✕</a>
    </div>

    <form method="POST" action="/vinyls/store" class="p-4 space-y-3">
      <input type="hidden" name="return_sort" value="<?= e((string)($return_sort ?? 'newest')) ?>">

      <input
        name="title"
        placeholder="Título"
        value="<?= e((string)($old['title'] ?? '')) ?>"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 placeholder-zinc-500 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700" />

      <select
        name="genre_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <option value="">Género musical</option>
        <?php foreach (($genres ?? []) as $g): ?>
          <option value="<?= (int)$g['id'] ?>" <?= ((string)($old['genre_id'] ?? '') === (string)$g['id']) ? 'selected' : '' ?>>
            <?= e($g['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input
        name="author"
        placeholder="Autor"
        value="<?= e((string)($old['author'] ?? '')) ?>"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 placeholder-zinc-500 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700" />

      <select
        name="format_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <option value="">Formato</option>
        <?php foreach (($formats ?? []) as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ((string)($old['format_id'] ?? '') === (string)$f['id']) ? 'selected' : '' ?>>
            <?= e($f['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select
        name="condition_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <option value="">Estado de conservación</option>
        <?php foreach (($conditions ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((string)($old['condition_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select
        name="record_label_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <option value="">Discográfica</option>
        <?php foreach (($labels ?? []) as $l): ?>
          <option value="<?= (int)$l['id'] ?>" <?= ((string)($old['record_label_id'] ?? '') === (string)$l['id']) ? 'selected' : '' ?>>
            <?= e($l['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input
        name="producer"
        placeholder="Producer"
        value="<?= e((string)($old['producer'] ?? '')) ?>"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 placeholder-zinc-500 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700" />

      <?php
      $currentYear = (int)date('Y');
      $selectedYear = (string)($old['release_date'] ?? $currentYear);
      ?>
      <select
        name="release_date"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <?php for ($y = $currentYear; $y >= 1900; $y--): ?>
          <option value="<?= $y ?>" <?= ($selectedYear === (string)$y) ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>

      <select
        name="edition_id"
        class="w-full rounded-lg bg-zinc-900 text-zinc-100 border border-zinc-800 px-3 py-2 outline-none focus:ring-2 focus:ring-zinc-700">
        <option value="">Edición</option>
        <?php foreach (($editions ?? []) as $ed): ?>
          <option value="<?= (int)$ed['id'] ?>" <?= ((string)($old['edition_id'] ?? '') === (string)$ed['id']) ? 'selected' : '' ?>>
            <?= e($ed['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="pt-2 flex items-center justify-center gap-10 text-zinc-200">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="is_favorite" class="h-4 w-4 accent-emerald-500"
            <?= !empty($old['is_favorite']) ? 'checked' : '' ?> />
          Favorito
        </label>

        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="is_desired" class="h-4 w-4 accent-emerald-500"
            <?= !empty($old['is_desired']) ? 'checked' : '' ?> />
          Deseado
        </label>
      </div>

      <button
        type="submit"
        class="w-full mt-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-500/40 py-3 font-semibold">
        Añadir vinilo
      </button>

      <a href="/vinyls" class="block text-center text-sm text-zinc-400 hover:text-zinc-200 mt-2">
        Cancelar
      </a>
    </form>
  </div>
</div>