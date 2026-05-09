<?php

use App\Core\Auth;
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VinylLibraryDB</title>

  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body
  class="min-h-screen text-zinc-100 bg-black bg-cover bg-fixed bg-center"
  style="
    background-image:
      linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.72)),
      url('<?= base_url('assets/images/vintage-bg.png') ?>');
  ">

  <div class="min-h-screen bg-black/10">

    <header class="sticky top-0 z-50 border-b border-zinc-800/70 bg-black/45 backdrop-blur-xl">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">

        <a
          href="<?= base_url(Auth::check() ? 'vinyls' : 'login') ?>"
          class="font-bold tracking-wide text-zinc-100 hover:text-emerald-300 transition">
          VinylLibraryDB
        </a>

        <nav class="flex items-center gap-3 text-sm">
          <?php if (Auth::check()): ?>

            <a
              class="rounded-lg px-3 py-1.5 text-zinc-200 hover:bg-zinc-800/80 hover:text-white transition"
              href="<?= base_url('vinyls') ?>">
              Mis vinilos
            </a>

            <form method="POST" action="<?= base_url('logout') ?>">
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