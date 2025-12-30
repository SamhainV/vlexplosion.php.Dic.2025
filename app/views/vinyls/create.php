<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-semibold">Añadir vinilo</h1>
  <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800" href="/vinyls">Volver</a>
</div>

<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-lg bg-red-900/40 border border-red-800 px-3 py-2 text-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<form method="POST" action="/vinyls/store" class="max-w-xl rounded-2xl border border-zinc-800 bg-zinc-900 p-5 space-y-4">
  <div>
    <label class="block text-sm text-zinc-300 mb-1">Título *</label>
    <input name="title" value="<?= e((string)($old['title'] ?? '')) ?>"
           class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" />
  </div>

  <div>
    <label class="block text-sm text-zinc-300 mb-1">Productor</label>
    <input name="producer" value="<?= e((string)($old['producer'] ?? '')) ?>"
           class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" />
  </div>

  <div>
    <label class="block text-sm text-zinc-300 mb-1">Año / Fecha (como lo tengas en DB)</label>
    <input name="release_date" value="<?= e((string)($old['release_date'] ?? '')) ?>"
           class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600"
           placeholder="1988 o 1988-01-01" />
  </div>

  <div class="flex items-center gap-6">
    <label class="inline-flex items-center gap-2 text-sm">
      <input type="checkbox" name="is_favorite" class="accent-emerald-500"
        <?= !empty($old['is_favorite']) ? 'checked' : '' ?> />
      Favorito
    </label>

    <label class="inline-flex items-center gap-2 text-sm">
      <input type="checkbox" name="is_desired" class="accent-sky-500"
        <?= !empty($old['is_desired']) ? 'checked' : '' ?> />
      Deseado
    </label>
  </div>

  <button class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white py-2 font-medium" type="submit">
    Guardar
  </button>
</form>
