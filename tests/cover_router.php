<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING',true);
if(!hash_equals((string)getenv('VLEX_COVER_KEY'),$_SERVER['HTTP_X_TEST_KEY']??'')){http_response_code(403);exit;}
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($path,'/assets/')){return false;}
if($path==='/__fixture/existing.png'){
 $directory=realpath((string)getenv('VLEX_COVER_RUNTIME'));
 if(!$directory || !str_starts_with($directory,realpath(__DIR__.'/runtime').'/covers-')){http_response_code(500);exit;}
 header('Content-Type: image/png');readfile($directory.'/fiction.png');exit;
}
if($path==='/broken-cover.webp'){http_response_code(404);exit;}
$_SERVER['SCRIPT_NAME']='/index.php';
require __DIR__.'/cover_fixtures.php';
require dirname(__DIR__).'/app/Core/bootstrap.php';
if(!\App\Core\Auth::check())\App\Core\Auth::login(['id'=>7,'username'=>'fiction']);
require dirname(__DIR__).'/public/index.php';
