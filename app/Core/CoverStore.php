<?php
declare(strict_types=1);
namespace App\Core;
final class CoverStore
{
    public static function directory(): string
    {
        return defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true ? app_storage_path('covers') : dirname(__DIR__, 2) . '/public/uploads/covers';
    }
    public static function withLock(callable $operation): mixed
    {
        $dir = self::directory();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) { throw new \RuntimeException('Cover storage unavailable'); }
        $lock = fopen($dir . '/.lock', 'c');
        if (!$lock) { throw new \RuntimeException('Cover lock unavailable'); }
        try {
            if (!flock($lock, LOCK_EX)) { throw new \RuntimeException('Cover lock failed'); }
            return $operation();
        } finally { fclose($lock); }
    }
    public static function inspect(string $path): string
    {
        $size = filesize($path);
        if (!$size || $size > 5 * 1024 * 1024) { throw new HttpException(422, 'La carátula debe pesar como máximo 5 MB.'); }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $info = @getimagesize($path);
        if (!isset($types[$mime]) || !$info || ($info['mime'] ?? '') !== $mime) { throw new HttpException(422, 'La carátula no es una imagen JPG, PNG o WEBP válida.'); }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8192 || $info[1] > 8192 || $info[0] * $info[1] > 24000000) {
            throw new HttpException(422, 'La carátula supera las dimensiones permitidas (8192 por lado y 24 millones de píxeles).');
        }
        return $types[$mime];
    }
    public static function upload(mixed $file): ?string
    {
        if ($file === null) { return null; }
        if (!is_array($file) || !is_int($file['error'] ?? null)) { throw new HttpException(422, 'Subida de carátula no válida.'); }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) { return null; }
        if ($file['error'] !== UPLOAD_ERR_OK) { throw new HttpException(422, 'No se pudo recibir la carátula. Comprueba el límite de subida del servidor.'); }
        $temp = $file['tmp_name'] ?? null;
        if (!is_string($temp) || !is_uploaded_file($temp)) { throw new HttpException(422, 'Subida de carátula no válida.'); }
        $extension = self::inspect($temp);
        $name = 'cover_' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($temp, self::directory() . '/' . $name)) { throw new \RuntimeException('Cover write failed'); }
        return 'uploads/covers/' . $name;
    }
    public static function cleanup(?string $path, callable $references): bool
    {
        // This is called only under withLock(), after commit or rollback.
        // Never delete arbitrary legacy, external, shared, or symlinked files.
        if (!$path || !preg_match('#^uploads/covers/(cover_[a-f0-9]{32}\.(jpg|png|webp))$#D', $path, $match)) { return false; }
        try { if ($references($path) !== 0) { return false; } } catch (\Throwable) { return false; }
        $file = self::directory() . '/' . $match[1];
        if (is_link($file) || !is_file($file) || realpath(dirname($file)) !== realpath(self::directory())) { return false; }
        return @unlink($file);
    }
    public static function fallbackUrl(): string
    {
        return '/assets/images/default-cover.webp';
    }

    public static function url(?string $path): string
    {
        $fallback = self::fallbackUrl();
        $path = trim((string)$path);
        if ($path === '') { return $fallback; }
        // External URLs are resolved by the browser; never fetch them on the server.
        if (preg_match('#^https?://#i', $path)) { return $path; }
        $public = dirname(__DIR__, 2) . '/public/';
        if (str_starts_with($path, $public)) { $path = substr($path, strlen($public)); }
        $parts = parse_url($path);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) { return $fallback; }
        $local = rawurldecode($parts['path'] ?? '');
        if (str_starts_with($local, 'public/')) { $local = substr($local, 7); }
        // Historical web paths may include the application's public directory prefix.
        if (preg_match('#/public/((?:uploads|assets)/.+)$#', $local, $match)) { $local = $match[1]; }
        $base = app_base_path();
        if ($base !== '' && str_starts_with($local, $base . '/')) { $local = substr($local, strlen($base) + 1); }
        $local = preg_replace('#^(?:\./)+#', '', ltrim($local, '/'));
        if (!preg_match('#^(uploads|assets)/.+\.(jpe?g|png|webp|gif|avif|svg)$#iD', $local) || preg_match('/[\x00-\x1f\x7f\\\\]/', $local)) { return $fallback; }
        $segments = explode('/', $local);
        if (in_array('..', $segments, true) || in_array('.', $segments, true) || in_array('', $segments, true)) { return $fallback; }
        // Do not follow any directory or file symlink into another checkout.
        $candidate = rtrim($public, '/');
        foreach ($segments as $segment) {
            $candidate .= '/' . $segment;
            if (is_link($candidate)) { return $fallback; }
        }
        if (!is_file($candidate)) { return $fallback; }
        $url = base_url(implode('/', array_map('rawurlencode', $segments)));
        if (isset($parts['query'])) { $url .= '?' . $parts['query']; }
        if (isset($parts['fragment'])) { $url .= '#' . $parts['fragment']; }
        return $url;
    }
}
