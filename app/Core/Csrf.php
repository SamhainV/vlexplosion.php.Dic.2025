<?php
declare(strict_types=1);
namespace App\Core;
final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
    public static function verifyRequest(): void
    {
        $submitted = $_POST['_csrf'] ?? null;
        $stored = $_SESSION['_csrf'] ?? null;
        if (!is_string($submitted) || !is_string($stored) || strlen($submitted) !== 64 || !hash_equals($stored, $submitted)) {
            throw new HttpException(403);
        }
    }
    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}
