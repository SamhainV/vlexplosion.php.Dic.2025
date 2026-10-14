<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__, 2) . '/app/Core/bootstrap.php';
$socket = getenv('VLEXPLOSION_TEST_SOCKET');
$directory = $socket ? realpath(dirname($socket)) : false;
if (!$directory || dirname($directory) !== realpath(dirname(__DIR__) . '/runtime') || !preg_match('/^db-enc-[a-f0-9]{8}$/D', basename($directory)) || basename($socket) !== 's.sock') { throw new RuntimeException('Isolated socket required'); }
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=vlexplosion_test;charset=utf8mb4', 'test_runner', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$r = $pdo->query('SELECT @@datadir AS dir,@@skip_networking AS isolated')->fetch();
if (realpath($r['dir']) !== $directory || (int)$r['isolated'] !== 1) { throw new RuntimeException('Isolation mismatch'); }
(new ReflectionProperty(\App\Core\Database::class, 'pdo'))->setValue(null, $pdo);
