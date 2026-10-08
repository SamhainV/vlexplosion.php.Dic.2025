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
    $path = $_ENV['APP_BACKGROUND'] ?? 'assets/images/vintage-bg.png';
    if (!is_string($path) || !preg_match('#^assets/images/[a-zA-Z0-9_-]+\.(png|jpe?g|webp)$#D', $path)) {
        return 'assets/images/vintage-bg.png';
    }
    return $path;
}


function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}

function app_storage_path(string $part): string
{
    $root = dirname(__DIR__, 2);
    $testing = defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true;
    $run = $testing ? (getenv('VLEXPLOSION_TEST_RUN') ?: 'default') : '';
    if ($testing && !preg_match('/^[a-z0-9-]{1,64}$/D', $run)) { throw new RuntimeException('Invalid test run identifier'); }
    return $root . ($testing ? '/tests/runtime/application/' . $run : '/storage') . '/' . $part;
}

function collection_query(int $page, string $sort): string
{
    return http_build_query(['page' => $page, 'sort' => $sort] + \App\Core\CollectionFilter::read());
}
function collection_hidden_fields(): string
{
    $html = '';
    foreach (\App\Core\CollectionFilter::read() as $key => $value) {
        $html .= '<input type="hidden" name="return_' . $key . '" value="' . e((string)$value) . '">';
    }
    return $html;
}
