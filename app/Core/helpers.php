<?php
declare(strict_types=1);

function app_base_path(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    // Ejemplo:
    // /vlexplosion.php.Dic.2025/public/index.php
    // /misdiscos/index.php
    $base = str_replace('/index.php', '', $scriptName);

    return rtrim($base, '/');
}

function base_url(string $path = ''): string
{
    $base = app_base_path();

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




function app_background(): string
{
    return $_ENV['APP_BACKGROUND'] ?? 'assets/images/vintage-bg.png';
}

