<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
require __DIR__ . '/fixtures.php';
$_ENV['DB_ENABLED'] = '0';
$case = $argv[1] ?? '';
$_SERVER = ['SCRIPT_NAME' => '/index.php', 'REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login', 'REMOTE_ADDR' => '192.0.2.77'];
$_POST = [];
http_response_code(200);
// Set up a real local session, using only fictional contents.
require dirname(__DIR__) . '/app/Core/bootstrap.php';
$token = \App\Core\Csrf::token();
if (str_starts_with($case, 'get-')) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = match ($case) { 'get-create' => '/vinyls/create', 'get-list' => '/vinyls', default => '/login' };
    if ($case !== 'get-login') { \App\Core\Auth::login(['id' => 7]); }
}
if ($case === 'good-login' || $case === 'wrong-login' || $case === 'internal-error') {
    \App\Core\LoginLimiter::application()->success('fictional-user');
    \App\Core\LoginLimiter::application()->success('database-error');
    // Use a fresh documentation-only source address per run; the limiter is tested separately.
    $_SERVER['REMOTE_ADDR'] = '2001:db8::' . bin2hex(random_bytes(4));
}
if ($case === 'good-login' || $case === 'wrong-login') {
    $_POST = ['_csrf' => $token, 'login' => 'fictional-user', 'password' => $case === 'good-login' ? 'fictional-password' : 'wrong-fictional-password'];
}
if ($case === 'valid-logout') {
    $_SERVER['REQUEST_URI'] = '/logout';
    \App\Core\Auth::login(['id' => 7]);
    $_POST = ['_csrf' => \App\Core\Csrf::token()];
}
if ($case === 'csrf-store' || $case === 'csrf-delete' || $case === 'csrf-logout') {
    $_SERVER['REQUEST_URI'] = match ($case) { 'csrf-store' => '/vinyls/store', 'csrf-delete' => '/vinyls/delete', default => '/logout' };
    \App\Core\Auth::login(['id' => 7, 'username' => 'fictional-user', 'email' => 'fiction@example.invalid']);
} elseif ($case === 'array-login') {
    $_POST = ['_csrf' => $token, 'login' => ['bad'], 'password' => 'fictional-password'];
} elseif ($case === 'array-password') {
    $_POST = ['_csrf' => $token, 'login' => 'fictional-user', 'password' => ['bad']];
} elseif ($case === 'bad-catalogue' || $case === 'array-title') {
    $_SERVER['REQUEST_URI'] = '/vinyls/store';
    \App\Core\Auth::login(['id' => 7]);
    $_POST = ['_csrf' => \App\Core\Csrf::token(), 'title' => 'Fiction', 'author' => 'Fiction', 'producer' => 'Fiction', 'genre_id' => '1', 'format_id' => '1', 'condition_id' => '1', 'record_label_id' => '1', 'edition_id' => '1', 'release_date' => '2020'];
    if ($case === 'bad-catalogue') { $_POST['genre_id'] = '99'; }
    else { $_POST['title'] = ['bad']; }
} elseif ($case === 'internal-error') {
    $_POST = ['_csrf' => $token, 'login' => 'database-error', 'password' => 'fictional-password'];
}
register_shutdown_function(static function (): void { echo "\nTEST_STATUS=" . http_response_code() . "\nTEST_AUTH=" . (\App\Core\Auth::id() ?? 0); });
require dirname(__DIR__) . '/public/index.php';
