<?php
declare(strict_types=1);
namespace App\Core;
final class SessionManager
{
    public static function start(): void
    {
        $path = app_storage_path('sessions');
        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new \RuntimeException('Session storage unavailable');
        }
        session_save_path($path);
        session_name('vlexplosion_session');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => app_base_path() . '/',
            'secure' => $https || ($_ENV['SESSION_COOKIE_SECURE'] ?? '0') === '1',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) { throw new \RuntimeException('Session start failed'); }
        self::expire();
    }
    public static function expire(?int $now = null): void
    {
        $now ??= time();
        if (isset($_SESSION['user'])) {
            $last = $_SESSION['_last_activity'] ?? 0;
            $started = $_SESSION['_authenticated_at'] ?? 0;
            if (!is_int($last) || !is_int($started) || $last > $now || $started > $now || $now - $last >= 1800 || $now - $started >= 43200) {
                Auth::logout();
                return;
            }
            $_SESSION['_last_activity'] = $now;
        }
    }
}
