<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Load .env
|--------------------------------------------------------------------------
*/

$envFile = __DIR__ . '/../../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $line = trim($line);

        // Ignorar comentarios
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Ignorar líneas inválidas
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $_ENV[trim($key)] = trim($value);
    }
}

$_ENV['APP_BACKGROUND'] = $_ENV['APP_BACKGROUND'] ?? 'assets/images/vintage-bg.png';

/*
|--------------------------------------------------------------------------
| Sessions
|--------------------------------------------------------------------------
*/

$sessionPath = __DIR__ . '/../../storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0770, true);
}

session_save_path($sessionPath);

session_start();

/*
|--------------------------------------------------------------------------
| Simple PSR-4-ish autoloader
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/helpers.php';