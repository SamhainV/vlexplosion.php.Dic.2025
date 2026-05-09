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

        // Quitar query string
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Base path del proyecto en localhost
        $basePath = '/vlexplosion.php.Dic.2025/public';

        // Si la URL empieza por la base, se la quitamos
        if (str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        // Normalizar ruta vacía
        if ($path === '' || $path === false) {
            $path = '/';
        }

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