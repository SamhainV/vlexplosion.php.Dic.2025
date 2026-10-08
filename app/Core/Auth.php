<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) > 0;
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
        if ((int)($user['id'] ?? 0) <= 0) { throw new \InvalidArgumentException('Invalid user'); }
        if (!session_regenerate_id(true)) { throw new \RuntimeException('Session renewal failed'); }
        $_SESSION = [];
        Csrf::rotate();
        $_SESSION['_last_activity'] = time();
        $_SESSION['_authenticated_at'] = time();
        // Store minimal user fields in session
        $_SESSION['user'] = [
            'id' => (int)($user['id'] ?? 0),
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (!session_regenerate_id(true)) { throw new \RuntimeException('Session renewal failed'); }
        Csrf::rotate();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }
}
