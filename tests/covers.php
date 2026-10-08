<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Core/CoverStore.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
use App\Core\CoverStore;
$count=0;
function check(bool $ok,string $label):void {global $count;if(!$ok)throw new RuntimeException('FAIL: '.$label);$count++;}
foreach ([null,'','   ','uploads/covers/missing.jpg','/uploads/covers/missing.jpg','public/uploads/covers/missing.jpg','/legacy/public/uploads/covers/missing.jpg','../assets/images/default-cover.webp','/assets/../private.png','//unknown.example/cover.png','javascript:bad','/outside/private.jpg'] as $path) {check(CoverStore::url($path)==='/assets/images/default-cover.webp','fallback '.(string)$path);}
foreach (['assets/images/vintage-bg.png','/assets/images/vintage-bg.png','./assets/images/vintage-bg.png','public/assets/images/vintage-bg.png',dirname(__DIR__).'/public/assets/images/vintage-bg.png','/legacy/public/assets/images/vintage-bg.png'] as $path) {check(CoverStore::url($path)==='/assets/images/vintage-bg.png','existing local '.(string)$path);}
check(CoverStore::url('assets/images/vintage-bg.png?v=2')==='/assets/images/vintage-bg.png?v=2','query preserved');
check(CoverStore::url('https://example.invalid/cover.jpg')==='https://example.invalid/cover.jpg','external preserved without server fetch');
$_SERVER['SCRIPT_NAME']='/review/index.php';
check(CoverStore::url('/review/assets/images/vintage-bg.png')==='/review/assets/images/vintage-bg.png','mounted absolute existing');
check(CoverStore::url(null)==='/assets/images/default-cover.webp','default URL always root relative');
echo "OK: $count cover path checks; no database or image writes.\n";
