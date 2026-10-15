<?php
require dirname(__DIR__).'/encyclopedia/connect.php';
$_ENV['VLEX_ENC_ENABLED']='1';
use App\Services\EncyclopediaCreditSync as Sync;
try{if($argv[1]==='start')echo Sync::start((int)$argv[2],7);elseif($argv[1]==='resume'){Sync::resume($argv[2],7);echo 'RESUMED';}else throw new RuntimeException('Unknown fixture worker');}
catch(App\Core\HttpException $e){if($e->status!==409)throw $e;echo 'CONFLICT';}
