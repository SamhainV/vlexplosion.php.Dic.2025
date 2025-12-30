<div class="max-w-md mx-auto bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
  <h1 class="text-xl font-semibold mb-4">Entrar</h1>

  <?php if (!empty($error)): ?>
    <div class="mb-4 rounded-lg bg-red-900/40 border border-red-800 px-3 py-2 text-sm">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/login" class="space-y-3">
    <div>
      <label class="block text-sm text-zinc-300 mb-1">Usuario o email</label>
      <input name="login" class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" placeholder="username o email" />
    </div>

    <div>
      <label class="block text-sm text-zinc-300 mb-1">Contraseña</label>
      <input type="password" name="password" class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 outline-none focus:ring focus:ring-zinc-600" placeholder="••••••••" />
    </div>

    <button type="submit" class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white py-2 font-medium">
      Entrar
    </button>
  </form>

  <p class="text-xs text-zinc-400 mt-4">
    Nota: La tabla es <code class="bg-zinc-950 px-1 py-0.5 rounded">Users_TBL</code> con
    <code class="bg-zinc-950 px-1 py-0.5 rounded">username</code>,
    <code class="bg-zinc-950 px-1 py-0.5 rounded">email</code> y contraseña en bcrypt.
  </p>
</div>
