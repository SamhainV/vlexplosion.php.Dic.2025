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
