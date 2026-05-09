<?php
declare(strict_types=1);

function base_url(string $path = ''): string
{
    $base = '/vlexplosion.php.Dic.2025/public';

    if ($path === '') {
        return $base . '/';
    }

    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
