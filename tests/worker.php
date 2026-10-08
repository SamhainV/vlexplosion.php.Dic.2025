<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
$_SERVER['SCRIPT_NAME']='/index.php';
require dirname(__DIR__).'/app/Core/bootstrap.php';
if (($argv[1]??'')==='limiter') {
    $path=$argv[2];
    if (!str_starts_with($path, realpath(__DIR__.'/runtime').'/')) { throw new RuntimeException('Unsafe fixture path'); }
    echo (new \App\Core\LoginLimiter($path))->attempt('concurrent-fiction','192.0.2.44',1000) ? '1' : '0'; exit;
}
$socket=getenv('VLEXPLOSION_TEST_SOCKET');
if (!$socket || !str_starts_with((string)realpath(dirname($socket)),realpath(__DIR__.'/runtime').'/db-') || basename($socket)!=='s.sock') { throw new RuntimeException('Unsafe socket'); }
$pdo=new PDO('mysql:unix_socket='.$socket.';dbname=vlexplosion_test;charset=utf8mb4','test_runner','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true]);
(new ReflectionProperty(\App\Core\Database::class,'pdo'))->setValue(null,$pdo);
$data=\App\Core\VinylInput::validate(['title'=>'Concurrent fiction','author'=>'Concurrent Unique','producer'=>'Fiction','genre_id'=>'1','format_id'=>'1','condition_id'=>'1','record_label_id'=>'1','edition_id'=>'1','release_date'=>'2020']);
echo \App\Models\Vinyl::createForUser(7,$data);
