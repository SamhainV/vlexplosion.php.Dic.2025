<?php

use App\Core\Auth;
$collectionPage = ($view ?? '') === 'vinyls/index';
$visualPage = $collectionPage ? 'collection' : (($view ?? '') === 'vinyls/show' ? 'detail' : (($view ?? '') === 'vinyls/create' ? 'editor' : (str_starts_with($view ?? '', 'encyclopedia/') ? 'encyclopedia' : 'standard')));
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VLexPlosion</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?= e(base_url('assets/js/covers.js')) ?>"></script>
<?php if ($collectionPage): ?><link rel="stylesheet" href="<?= e(base_url('assets/css/collection.css')) ?>"><?php endif; ?>
  <link rel="stylesheet" href="<?= e(base_url('assets/css/theme.css')) ?>">
</head>

<body
  class="min-h-screen text-zinc-100 bg-black bg-cover bg-fixed bg-center vl-app vl-<?= e($visualPage) ?>-page"
  style="
    background-image:
      linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.72)),
      
      url('<?= e(base_url(app_background())) ?>');
  ">

  <div class="min-h-screen bg-black/10">

    <header class="sticky top-0 z-50 border-b border-zinc-800/70 bg-black/45 backdrop-blur-xl">
      <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap gap-3 items-center justify-between">

        <a
          href="<?= base_url(Auth::check() ? 'vinyls' : 'login') ?>"
          class="font-bold tracking-wide text-zinc-100 hover:text-emerald-300 transition">
          <span class="collection-brand-mark" aria-hidden="true">◎</span> VLexPlosion
          <?php if (defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true): ?><span style="display:block;font-size:11px;color:#fcd34d;font-weight:normal">Desarrollo · colección ficticia</span><?php endif; ?>
        </a>

        <nav class="flex items-center gap-3 text-sm">
          <?php if (Auth::check()): ?>
            <?php if (\App\Core\Encyclopedia::enabled()): ?><a class="text-emerald-300" href="<?= e(base_url('encyclopedia/search')) ?>">Búsqueda musical</a><?php endif; ?>

            <a
              class="rounded-lg px-3 py-1.5 text-zinc-200 hover:bg-zinc-800/80 hover:text-white transition"
              href="<?= base_url('vinyls') ?>">
              Mis discos
            </a>

            <form method="POST" action="<?= base_url('logout') ?>">
      <?= csrf_field() ?>
              <button
                class="rounded-lg bg-zinc-800/90 px-3 py-1.5 text-zinc-100 hover:bg-red-600 transition"
                type="submit">
                Cerrar sesión
              </button>
            </form>

          <?php else: ?>

            <a
              class="rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-500 transition"
              href="<?= base_url('login') ?>">
              Login
            </a>

          <?php endif; ?>
        </nav>

      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
