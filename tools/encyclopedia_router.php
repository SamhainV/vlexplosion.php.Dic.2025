<?php
declare(strict_types=1);
// Development entry point only: never read .env or use the normal DB configuration.
define('VLEXPLOSION_TESTING', true);
$port = (int)getenv('VLEX_ENC_PORT');
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' || ($_SERVER['HTTP_HOST'] ?? '') !== '127.0.0.1:' . $port) { http_response_code(403); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    $file = realpath(dirname(__DIR__) . '/public' . rawurldecode($path));
    $assets = realpath(dirname(__DIR__) . '/public/assets');
    if ($file && str_starts_with($file, $assets . '/') && !str_ends_with($file, '.php')) { return false; }
    http_response_code(404); exit;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/app/Core/bootstrap.php';
$socket = getenv('VLEXPLOSION_TEST_SOCKET');
$parent = $socket ? realpath(dirname($socket)) : false;
$runtime = realpath(dirname(__DIR__) . '/tests/runtime');
if (!$parent || dirname($parent) !== $runtime || !preg_match('/^db-enc-[a-f0-9]{8}$/D', basename($parent)) || basename($socket) !== 's.sock') { throw new RuntimeException('Isolated socket required'); }
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=vlexplosion_test;charset=utf8mb4', 'test_runner', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$isolation = $pdo->query('SELECT @@datadir AS dir,@@socket AS socket,@@skip_networking AS isolated')->fetch();
if (realpath($isolation['dir']) !== $parent || realpath($isolation['socket']) !== realpath($socket) || (int)$isolation['isolated'] !== 1) { throw new RuntimeException('Isolation mismatch'); }
(new ReflectionProperty(\App\Core\Database::class, 'pdo'))->setValue(null, $pdo);
if (preg_match('#^/uploads/covers/(cover_[a-f0-9]{32}\.(jpg|png|webp))$#D', $path, $m)) {
    \App\Core\Auth::requireLogin();
    $s = $pdo->prepare('SELECT Id FROM VINYLS_TBL WHERE User_Id=? AND Image_Path=?');
    $s->execute([\App\Core\Auth::id(), ltrim($path, '/')]);
    $file = \App\Core\CoverStore::directory() . '/' . $m[1];
    if (!$s->fetchColumn() || !is_file($file) || is_link($file)) { http_response_code(404); exit; }
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($file));
    header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store'); readfile($file); exit;
}
require dirname(__DIR__) . '/public/index.php';
