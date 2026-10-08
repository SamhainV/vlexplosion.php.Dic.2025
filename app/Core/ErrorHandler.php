<?php
declare(strict_types=1);
namespace App\Core;
final class ErrorHandler
{
    private static int $bufferLevel = 0;
    public static function install(): void
    {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '0'); // All application logging stays in this project.
        error_reporting(E_ALL);
        self::$bufferLevel = ob_get_level();
        ob_start();
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) { return false; }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler([self::class, 'respond']);
        register_shutdown_function(static function (): void {
            $last = error_get_last();
            if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::respond(new \RuntimeException('Fatal application error'));
            }
        });
    }
    public static function respond(\Throwable $error): void
    {
        while (ob_get_level() > self::$bufferLevel) { ob_end_clean(); }
        $status = $error instanceof HttpException ? $error->status : 500;
        if (!in_array($status, [400, 403, 404, 405, 422, 429], true)) { $status = 500; }
        http_response_code($status);
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
        }
        if ($status === 500) { self::log($error); }
        echo match ($status) {
            400, 422 => 'Los datos enviados no son válidos.',
            403 => 'La solicitud ha caducado o no es válida. Recarga el formulario.',
            404 => 'Recurso no encontrado.',
            405 => 'Método no permitido.',
            429 => 'Demasiados intentos. Espera antes de volver a intentarlo.',
            default => 'No se pudo completar la solicitud. Inténtalo de nuevo más tarde.',
        };
    }
    private static function log(\Throwable $error): void
    {
        // Never persist exception messages, SQL, request bodies or credentials.
        $dir = function_exists('app_storage_path') ? app_storage_path('logs') : dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { return; }
        @file_put_contents($dir . '/errors.log', gmdate('c') . ' ' . get_class($error) . ' code=' . (int)$error->getCode() . "\n", FILE_APPEND | LOCK_EX);
    }
}
