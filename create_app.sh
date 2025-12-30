#!/usr/bin/env bash
set -euo pipefail

# VinylLibraryDB PHP MVC Scaffold Generator
# Usage:
#   bash create_app.sh /path/to/project
# Example:
#   bash create_app.sh ./vinylapp

TARGET_DIR="${1:-vinylapp}"

mkdir -p "$TARGET_DIR"

# Create folders
mkdir -p \
  "$TARGET_DIR/app/config" \
  "$TARGET_DIR/app/core" \
  "$TARGET_DIR/app/controllers" \
  "$TARGET_DIR/app/models" \
  "$TARGET_DIR/app/views/layouts" \
  "$TARGET_DIR/app/views/auth" \
  "$TARGET_DIR/app/views/vinyls" \
  "$TARGET_DIR/public/assets" \
  "$TARGET_DIR/storage" \
  "$TARGET_DIR/vendor"

# -------------------------
# Root files
# -------------------------
cat > "$TARGET_DIR/.gitignore" <<'EOF'
/vendor/
/storage/*.log
/public/assets/*.css
/public/assets/*.js
.env
EOF

cat > "$TARGET_DIR/README.md" <<'EOF'
# VinylLibraryDB - PHP MVC (PDO) Scaffold

## Requisitos
- PHP 8.1+ (recomendado 8.2/8.3)
- MySQL/MariaDB
- Apache/Nginx (o PHP built-in server)

## Estructura
- `public/` Front controller y assets
- `app/` Código MVC
  - `core/` Núcleo (Router, Controller base, DB, Auth, etc.)
  - `controllers/` Controladores
  - `models/` Modelos (PDO)
  - `views/` Vistas (Tailwind por CDN de momento)
  - `config/` Configuración (DB)
- `storage/` Logs / cache (si lo necesitas)

## Arranque rápido (dev)
Desde la raíz del proyecto:
```bash
php -S localhost:8000 -t public
```
Y abre: http://localhost:8000

## Tailwind
De momento se usa Tailwind por CDN (rápido y simple).
Más adelante, si quieres compilación real (npm + tailwind), lo integramos en `public/assets/`.
EOF

# -------------------------
# public/
# -------------------------
cat > "$TARGET_DIR/public/.htaccess" <<'EOF'
RewriteEngine On

# If your app is in a subfolder, you may need RewriteBase /subfolder/public/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
EOF

cat > "$TARGET_DIR/public/index.php" <<'EOF'
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\VinylController;

// Routes
$router = new Router();

// Auth
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Vinyls
$router->get('/vinyls', [VinylController::class, 'index']);

// Dispatch
$router->dispatch();
EOF

# -------------------------
# app/core/
# -------------------------
cat > "$TARGET_DIR/app/core/bootstrap.php" <<'EOF'
<?php
declare(strict_types=1);

session_start();

// Simple PSR-4-ish autoloader for App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Common helpers
require_once __DIR__ . '/helpers.php';
EOF

cat > "$TARGET_DIR/app/core/helpers.php" <<'EOF'
<?php
declare(strict_types=1);

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
EOF

cat > "$TARGET_DIR/app/core/Router.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, array{0: string, 1: string}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->$action();
    }
}
EOF

cat > "$TARGET_DIR/app/core/Controller.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: " . $viewFile);
        }

        require __DIR__ . '/../views/layouts/header.php';
        require $viewFile;
        require __DIR__ . '/../views/layouts/footer.php';
    }
}
EOF

cat > "$TARGET_DIR/app/core/Database.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['name']
        );

        try {
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo "DB connection error.";
            // You can log $e->getMessage() to storage if you want
            exit;
        }

        return self::$pdo;
    }
}
EOF

cat > "$TARGET_DIR/app/core/Auth.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }

    public static function login(array $user): void
    {
        // Store minimal user fields in session
        $_SESSION['user'] = [
            'id' => (int)($user['id'] ?? 0),
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }
}
EOF

cat > "$TARGET_DIR/app/core/Paginator.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public int $page;
    public int $perPage;
    public int $total;
    public int $pages;

    public function __construct(int $page, int $perPage, int $total)
    {
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->total = max(0, $total);
        $this->pages = (int)max(1, ceil($this->total / $this->perPage));
        if ($this->page > $this->pages) {
            $this->page = $this->pages;
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
EOF

# -------------------------
# app/config/
# -------------------------
cat > "$TARGET_DIR/app/config/database.php" <<'EOF'
<?php
declare(strict_types=1);

/**
 * DB config
 * Provided by you:
 * if ($_SERVER['HTTP_HOST'] === 'www.samhain.org') {
 *   host=localhost
 *   name=s01f7025_VinylLibraryDB
 *   user=s01f7025_vlexplosion
 *   pass=A29xxRamones
 * }
 *
 * Local dev: adjust to your local DB credentials and DB name (same as .sql file)
 */

if (($_SERVER['HTTP_HOST'] ?? '') === 'www.samhain.org') {
    return [
        'host' => 'localhost',
        'name' => 's01f7025_VinylLibraryDB',
        'user' => 's01f7025_vlexplosion',
        'pass' => 'A29xxRamones',
    ];
}

// Local / other hosts
return [
    'host' => 'localhost',
    'name' => 's01f7025_VinylLibraryDB',
    'user' => 'root',
    'pass' => '',
];
EOF

# -------------------------
# app/models/
# -------------------------
cat > "$TARGET_DIR/app/models/User.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public static function findByUsernameOrEmail(string $login): ?array
    {
        $pdo = Database::pdo();
        $sql = "SELECT id, username, password, email
                FROM Users_TBL
                WHERE username = :login OR email = :login
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}
EOF

cat > "$TARGET_DIR/app/models/Vinyl.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Vinyl
{
    public static function countByUser(int $userId): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM VINYLS_TBL WHERE User_Id = :uid");
        $stmt->execute(['uid' => $userId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    /**
     * Basic listing for the logged user.
     * Later we can join AUTHORS_TBL, GENRES_TBL, etc.
     */
    public static function paginateByUser(int $userId, int $limit, int $offset): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired, Image_Path
                FROM VINYLS_TBL
                WHERE User_Id = :uid
                ORDER BY Id DESC
                LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
EOF

# -------------------------
# app/controllers/
# -------------------------
cat > "$TARGET_DIR/app/controllers/AuthController.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/vinyls');
        }
        $this->view('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        $login = trim($_POST['login'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->view('auth/login', ['error' => 'Rellena usuario/email y contraseña.']);
            return;
        }

        $user = User::findByUsernameOrEmail($login);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->view('auth/login', ['error' => 'Credenciales incorrectas.']);
            return;
        }

        Auth::login($user);
        redirect('/vinyls');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
EOF

cat > "$TARGET_DIR/app/controllers/VinylController.php" <<'EOF'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Vinyl;

final class VinylController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12;

        $userId = Auth::id() ?? 0;
        $total = Vinyl::countByUser($userId);
        $p = new Paginator($page, $perPage, $total);

        $items = Vinyl::paginateByUser($userId, $p->perPage, $p->offset());

        $this->view('vinyls/index', [
            'items' => $items,
            'p' => $p,
        ]);
    }
}
EOF

# -------------------------
# app/views/layouts/
# -------------------------
cat > "$TARGET_DIR/app/views/layouts/header.php" <<'EOF'
<?php
use App\Core\Auth;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VinylLibraryDB</title>

  <!-- Tailwind CDN (rápido para empezar). Luego podemos compilar Tailwind "de verdad". -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">
  <header class="border-b border-zinc-800">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="font-semibold tracking-wide">VinylLibraryDB</div>
      <nav class="flex items-center gap-3 text-sm">
        <?php if (Auth::check()): ?>
          <a class="hover:underline" href="/vinyls">Mis vinilos</a>
          <form method="POST" action="/logout">
            <button class="px-3 py-1 rounded bg-zinc-800 hover:bg-zinc-700" type="submit">Cerrar sesión</button>
          </form>
        <?php else: ?>
          <a class="hover:underline" href="/login">Login</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-8">
EOF

cat > "$TARGET_DIR/app/views/layouts/footer.php" <<'EOF'
  </main>

  <footer class="border-t border-zinc-800">
    <div class="max-w-6xl mx-auto px-4 py-6 text-xs text-zinc-400">
      © <?= date('Y') ?> VinylLibraryDB
    </div>
  </footer>
</body>
</html>
EOF

# -------------------------
# app/views/auth/
# -------------------------
cat > "$TARGET_DIR/app/views/auth/login.php" <<'EOF'
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
EOF

# -------------------------
# app/views/vinyls/
# -------------------------
cat > "$TARGET_DIR/app/views/vinyls/index.php" <<'EOF'
<h1 class="text-2xl font-semibold mb-6">Mis vinilos</h1>

<div class="mb-4 text-sm text-zinc-400">
  Total: <span class="text-zinc-200 font-medium"><?= (int)$p->total ?></span>
</div>

<?php if (empty($items)): ?>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
    No tienes vinilos aún.
  </div>
<?php else: ?>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($items as $v): ?>
      <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="font-semibold"><?= e($v['Title'] ?? '') ?></div>
            <div class="text-xs text-zinc-400 mt-1">
              <?= e((string)($v['Producer'] ?? '')) ?> · <?= e((string)($v['Release_date'] ?? '')) ?>
            </div>
          </div>
          <div class="text-xs text-zinc-300 flex flex-col items-end gap-1">
            <?php if (!empty($v['Is_Favorite'])): ?>
              <span class="px-2 py-0.5 rounded bg-amber-900/40 border border-amber-700">Fav</span>
            <?php endif; ?>
            <?php if (!empty($v['Is_Desired'])): ?>
              <span class="px-2 py-0.5 rounded bg-sky-900/40 border border-sky-700">Wish</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Paginación -->
  <?php if ($p->pages > 1): ?>
    <div class="mt-8 flex items-center justify-center gap-2 text-sm">
      <?php
        $prev = max(1, $p->page - 1);
        $next = min($p->pages, $p->page + 1);
      ?>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === 1 ? 'opacity-40 pointer-events-none' : '' ?>"
         href="/vinyls?page=<?= $prev ?>">Anterior</a>

      <span class="px-3 py-1 text-zinc-300">
        Página <?= (int)$p->page ?> / <?= (int)$p->pages ?>
      </span>

      <a class="px-3 py-1 rounded border border-zinc-700 hover:bg-zinc-800 <?= $p->page === $p->pages ? 'opacity-40 pointer-events-none' : '' ?>"
         href="/vinyls?page=<?= $next ?>">Siguiente</a>
    </div>
  <?php endif; ?>

<?php endif; ?>
EOF

# Done
echo "✅ Scaffold creado en: $TARGET_DIR"
echo "➡️  Dev server: php -S localhost:8000 -t $TARGET_DIR/public"
