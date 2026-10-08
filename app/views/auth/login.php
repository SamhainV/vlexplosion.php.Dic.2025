<div class="max-w-md mx-auto bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
  <h1 class="text-xl font-semibold mb-4">Entrar</h1>

  <?php if (!empty($error)): ?>
    <div class="mb-4 rounded-lg bg-red-900/40 border border-red-800 px-3 py-2 text-sm">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= base_url('login') ?>" class="space-y-3">
      <?= csrf_field() ?>
    <div>
      <label for="login" class="block text-sm text-zinc-300 mb-1">Usuario o email</label>
      <input id="login" required maxlength="254" autocomplete="username" name="login" class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" placeholder="username o email" />
    </div>

    <div>
      <label for="password" class="block text-sm text-zinc-300 mb-1">Contraseña</label>
      <input id="password" required autocomplete="current-password" type="password" name="password" class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" placeholder="••••••••" />
    </div>

    <button type="submit" class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white py-2 font-medium">
      Entrar
    </button>
  </form>
</div>