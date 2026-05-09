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
<body class="bg-zinc-950 text-zinc-100 min-h-screen">
  <header class="border-b border-zinc-800">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="font-semibold tracking-wide">VinylLibraryDB</div>

      <nav class="flex items-center gap-3 text-sm">
        <?php if (Auth::check()): ?>

          <a class="hover:underline"
             href="<?= base_url('vinyls') ?>">
             Mis vinilos
          </a>

          <form method="POST"
                action="<?= base_url('logout') ?>">

            <button class="px-3 py-1 rounded bg-zinc-800 hover:bg-zinc-700"
                    type="submit">
              Cerrar sesión
            </button>

          </form>

        <?php else: ?>

          <a class="hover:underline"
             href="<?= base_url('login') ?>">
             Login
          </a>

        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-8">