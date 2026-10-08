<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
if (!hash_equals((string)getenv('VLEXPLOSION_TEST_KEY'), $_SERVER['HTTP_X_TEST_KEY'] ?? '')) { http_response_code(403); exit; }
if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/assets/')) { return false; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/app/Core/bootstrap.php';
$socket = getenv('VLEXPLOSION_TEST_SOCKET');
if (!$socket || !str_starts_with((string)realpath(dirname($socket)), realpath(__DIR__ . '/runtime') . '/db-') || basename($socket) !== 's.sock') { throw new RuntimeException('Only isolated socket allowed'); }
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=vlexplosion_test;charset=utf8mb4', 'test_runner', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>true]);
(new ReflectionProperty(\App\Core\Database::class, 'pdo'))->setValue(null,$pdo);
if (($_SERVER['REQUEST_URI'] ?? '') === '/__test/state') {
 header('Content-Type: application/json');
 echo json_encode(['user'=>\App\Core\Auth::id(), 'files'=>count(glob(\App\Core\CoverStore::directory().'/cover_*') ?: []), 'session'=>session_id()], JSON_THROW_ON_ERROR); exit;
}
if (($_SERVER['REQUEST_URI'] ?? '') === '/__test/expire') {
 \App\Core\Csrf::verifyRequest(); $_SESSION['_last_activity']=time()-1800; echo 'fixture expiry'; exit;
}
require dirname(__DIR__) . '/public/index.php';
