<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/app/Core/bootstrap.php';
use App\Core\{CollectionFilter, CoverStore, Database, HttpException, Paginator, VinylInput};
use App\Models\Vinyl;
$socket = getenv('VLEXPLOSION_TEST_SOCKET');
$runtime = realpath(__DIR__ . '/runtime');
if (!$socket || !$runtime || !str_starts_with((string)realpath(dirname($socket)), $runtime . '/db-') || basename($socket) !== 's.sock') { throw new RuntimeException('Only an isolated test socket is permitted'); }
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=vlexplosion_test;charset=utf8mb4', 'test_runner', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true]);
$pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
(new ReflectionProperty(Database::class, 'pdo'))->setValue(null, $pdo);
$count = 0;
function check(bool $ok, string $label): void { global $count; if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } $count++; }
function rejects(callable $operation, string $label): void { try { $operation(); } catch (HttpException $e) { check($e->status === 422, $label); return; } throw new RuntimeException('FAIL: ' . $label); }
$data = VinylInput::validate(['title' => 'Fiction', 'authors' => ['Alpha', 'Shared'], 'producer' => 'Producer', 'genre_id' => '1', 'format_id' => '1', 'condition_id' => '1', 'record_label_id' => '1', 'edition_id' => '1', 'release_date' => '2020']);
for ($i=0; $i<26; $i++) {
    $item = $data; $item['title'] = $i%3 === 0 ? 'Tie' : sprintf('Fiction %02d', $i); $item['producer'] = $i%2 ? 'Beta' : 'Alpha'; $item['release_date'] = 2000 + $i%5; $item['is_favorite'] = $i%2; $item['is_desired'] = (int)($i%3 === 0);
    Vinyl::createForUser(7, $item);
}
$privateId = Vinyl::createForUser(8, $data);
$needle = $data; $needle['title'] = 'Needle 100%_'; $needleId = Vinyl::createForUser(7, $needle);
check(Vinyl::countByUser(7) === 27, 'one count per vinyl despite two authors');
check(count(Vinyl::paginateByUser(7,12,0)) === 12, 'twelve different vinyls');
check(Vinyl::findByIdForUser($privateId,7) === null, 'read access between users');
check(!Vinyl::updateForUser($privateId,7,$data), 'update access between users');
check(!Vinyl::deleteForUser($privateId,7), 'delete access between users');
check(Vinyl::findByIdForUser($privateId,8) !== null, 'foreign record intact');
$sorts = [
 'newest'=>[['Id',-1]], 'oldest'=>[['Id',1]], 'title_asc'=>[['Title',1],['Id',-1]], 'title_desc'=>[['Title',-1],['Id',-1]],
 'year_asc'=>[['Release_date',1],['Title',1],['Id',-1]], 'year_desc'=>[['Release_date',-1],['Title',1],['Id',-1]],
 'producer_asc'=>[['Producer',1],['Title',1],['Id',-1]], 'producer_desc'=>[['Producer',-1],['Title',1],['Id',-1]],
 'fav_first'=>[['Is_Favorite',-1],['Id',-1]], 'desired_first'=>[['Is_Desired',-1],['Id',-1]],
 'fav_then_title'=>[['Is_Favorite',-1],['Title',1],['Id',-1]], 'desired_then_title'=>[['Is_Desired',-1],['Title',1],['Id',-1]],
];
$base = $pdo->query('SELECT * FROM VINYLS_TBL WHERE User_Id=7')->fetchAll();
foreach ($sorts as $sort=>$columns) {
 $expected=$base;
 usort($expected, static function(array $a,array $b) use($columns):int { foreach($columns as [$field,$direction]) { $cmp=in_array($field,['Title','Producer'],true) ? strcmp($a[$field],$b[$field]) : (int)$a[$field] <=> (int)$b[$field]; if($cmp) return $direction*$cmp; } return 0; });
 $actual=Vinyl::paginateByUser(7,100,0,$sort);
 check(array_column($actual,'Id')===array_column($expected,'Id'), 'actual SQL ordering '.$sort);
 check(count(array_unique(array_column($actual,'Id')))===27, 'no duplicate cards '.$sort);
 $paginated=[]; for($offset=0;$offset<27;$offset+=12) $paginated=array_merge($paginated,Vinyl::paginateByUser(7,12,$offset,$sort));
 check(array_column($paginated,'Id')===array_column($expected,'Id'), 'pagination '.$sort);
 foreach($actual as $position=>$row) check(Vinyl::pageForIdByUser(7,(int)$row['Id'],12,$sort)===(int)floor($position/12)+1, 'position calculation '.$sort);
}
check(Vinyl::countByUser(7,['q'=>'Shared'])===27, 'author search with multiple authors');
check(Vinyl::countByUser(7,['q'=>"' OR 1=1 --"])===0, 'SQL injection treated literally');
check(Vinyl::countByUser(7,['q'=>'%_'])===1, 'literal wildcard search');
check(Vinyl::countByUser(7,['q'=>'No match'])===0, 'empty search');
foreach([['fav'=>1],['desired'=>1],['fav'=>1,'desired'=>1],['q'=>'Fiction','fav'=>1,'desired'=>1]] as $filters) {
 $items=Vinyl::paginateByUser(7,100,0,'title_asc',$filters);
 check(count($items)===Vinyl::countByUser(7,$filters),'consistent filter count');
 foreach($items as $position=>$row) {
  check(empty($filters['fav']) || (int)$row['Is_Favorite']===1,'favorite filter');
  check(empty($filters['desired']) || (int)$row['Is_Desired']===1,'desired filter');
  check(Vinyl::pageForIdByUser(7,(int)$row['Id'],12,'title_asc',$filters)===(int)floor($position/12)+1,'filtered position');
 }
}
$p=new Paginator(999,12,27); check($p->page===3 && $p->offset()===24,'last page bound');
$before=(int)$pdo->query('SELECT COUNT(*) FROM AUTHORS_TBL')->fetchColumn();
$bad=$data;$bad['authors']=['Rollback Fiction'];$bad['record_label_id']=99;
try { Vinyl::createForUser(7,$bad); throw new LogicException('Invalid FK accepted'); } catch(PDOException) { check(Vinyl::countByUser(7)===27,'create rollback'); check((int)$pdo->query('SELECT COUNT(*) FROM AUTHORS_TBL')->fetchColumn()===$before,'author rollback'); }
$updated=$data;$updated['title']='Edited Fiction';$updated['authors']=['Alpha','Shared','Third'];
check(Vinyl::updateForUser($needleId,7,$updated),'edit exists');
$row=Vinyl::findByIdForUser($needleId,7); check($row['Title']==='Edited Fiction' && count($row['authors'])===3,'edit persisted with all authors');
check(Vinyl::updateForUser($needleId,7,$updated),'unchanged edit succeeds');
$bad=$updated;$bad['title']='Should roll back';$bad['format_id']=99;
try { Vinyl::updateForUser($needleId,7,$bad); throw new LogicException('Invalid FK accepted'); } catch(PDOException) { check(Vinyl::findByIdForUser($needleId,7)['Title']==='Edited Fiction','edit rollback'); }
$pdo->exec("INSERT INTO AUTHORS_TBL (Author_Name) VALUES ('Alpha')");$duplicateId=(int)$pdo->lastInsertId();
$pdo->prepare('UPDATE AUTOR_VINYLS_TBL SET Autor_Id=:new WHERE Vinilo_Id=:vid AND Autor_Id=(SELECT a.Id FROM AUTHORS_TBL a WHERE Author_Name=:name ORDER BY a.Id LIMIT 1)')->execute(['new'=>$duplicateId,'vid'=>$needleId,'name'=>'Alpha']);
Vinyl::updateForUser($needleId,7,$updated);
$authors=Vinyl::findByIdForUser($needleId,7)['authors'];
check(in_array($duplicateId,array_map('intval',array_column($authors,'Id')),true),'preserve existing duplicate author identity');
check((int)$pdo->query("SELECT COUNT(*) FROM AUTHORS_TBL WHERE Author_Name='Alpha'")->fetchColumn()===2,'no author merging');
check(Vinyl::deleteForUser($needleId,7),'delete owned record');
check(!Vinyl::deleteForUser($needleId,7),'repeat delete is false');
check(Vinyl::findByIdForUser($needleId,7)===null,'deleted record absent');
check((int)$pdo->query('SELECT COUNT(*) FROM AUTOR_VINYLS_TBL WHERE Vinilo_Id='.$needleId)->fetchColumn()===0,'no orphan bridge');
check(Vinyl::findByIdForUser($privateId,8)!==null,'other user intact after CRUD');
// All file fixtures are synthetic and inside the test-only CoverStore directory.
CoverStore::withLock(static function():void{});
$dir=CoverStore::directory();$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==');
$file=$dir.'/cover_'.bin2hex(random_bytes(16)).'.png';file_put_contents($file,$png);$path='uploads/covers/'.basename($file);
foreach (['jpg','png','webp'] as $format) { check(CoverStore::inspect(dirname($socket).'/fixture.'.$format)===$format,'real format fixture '.$format); }
check(CoverStore::inspect($file)==='png','real PNG MIME and dimensions');
check(!CoverStore::withLock(fn()=>CoverStore::cleanup($path,fn()=>1)) && is_file($file),'shared image preserved');
check(!CoverStore::withLock(fn()=>CoverStore::cleanup($path,fn()=>throw new RuntimeException('fiction'))) && is_file($file),'reference query failure preserves image');
check(!CoverStore::withLock(fn()=>CoverStore::cleanup('../outside',fn()=>0)),'reject path traversal');
check(CoverStore::withLock(fn()=>CoverStore::cleanup($path,fn()=>0)) && !is_file($file),'unreferenced generated image removed');
$file=$dir.'/invalid-'.bin2hex(random_bytes(8));file_put_contents($file,'<?php fiction ?>');rejects(fn()=>CoverStore::inspect($file),'reject fake image');
$file=$dir.'/huge-'.bin2hex(random_bytes(8)).'.png';$huge=$png;$huge=substr_replace($huge,pack('N',9000),16,4);file_put_contents($file,$huge);rejects(fn()=>CoverStore::inspect($file),'reject oversized dimensions');
$file=$dir.'/size-'.bin2hex(random_bytes(8));file_put_contents($file,str_repeat('x',5*1024*1024+1));rejects(fn()=>CoverStore::inspect($file),'reject oversized bytes');
rejects(fn()=>CoverStore::upload(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$file]),'reject non-HTTP upload');
check(CoverStore::upload(['error'=>UPLOAD_ERR_NO_FILE])===null,'optional cover');
check(CoverStore::url('uploads/covers/missing.png')===base_url('assets/images/default-cover.webp'),'missing cover fallback');
check(CoverStore::url('javascript:fiction')===base_url('assets/images/default-cover.webp'),'invalid cover URL fallback');
$file=$dir.'/target-'.bin2hex(random_bytes(8));file_put_contents($file,$png);$link=$dir.'/cover_'.bin2hex(random_bytes(16)).'.png';symlink($file,$link);
check(!CoverStore::withLock(fn()=>CoverStore::cleanup('uploads/covers/'.basename($link),fn()=>0)) && is_file($file),'symlink target preserved');
// File locking must enforce the same account allowance across independent processes.
$limiterFile = __DIR__ . '/runtime/concurrent-' . bin2hex(random_bytes(8)) . '.json';
$workers=[];
for($i=0;$i<10;$i++) { $proc=proc_open([PHP_BINARY,__DIR__.'/worker.php','limiter',$limiterFile],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__)); $workers[]=[$proc,$pipes]; }
$accepted=0;
foreach($workers as [$proc,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($proc);check($status===0 && $err==='', 'concurrent limiter worker');$accepted+=(int)$out; }
check($accepted===5,'exact concurrent account allowance');
$workers=[];
for($i=0;$i<2;$i++) { $proc=proc_open([PHP_BINARY,__DIR__.'/worker.php','author'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));$workers[]=[$proc,$pipes]; }
foreach($workers as [$proc,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($proc)===0 && $err==='' && (int)$out>0,'concurrent author create: '.$err); }
check((int)$pdo->query("SELECT COUNT(*) FROM AUTHORS_TBL WHERE Author_Name='Concurrent Unique'")->fetchColumn()===1,'reuse author under concurrent creation');
// A real SQL reference in another user must preserve a shared cover.
$sharedFile=$dir.'/cover_'.bin2hex(random_bytes(16)).'.png';file_put_contents($sharedFile,$png);$sharedPath='uploads/covers/'.basename($sharedFile);
$shared=$data;$shared['image_path']=$sharedPath;$one=Vinyl::createForUser(7,$shared);$two=Vinyl::createForUser(8,$shared);
Vinyl::deleteForUser($one,7);
check(!CoverStore::withLock(fn()=>CoverStore::cleanup($sharedPath,[Vinyl::class,'imageReferences'])) && is_file($sharedFile),'real cross-user shared cover preserved');
Vinyl::deleteForUser($two,8);
check(CoverStore::withLock(fn()=>CoverStore::cleanup($sharedPath,[Vinyl::class,'imageReferences'])) && !is_file($sharedFile),'last real reference removed safely');
foreach ([7,8] as $uid) { $pdo->prepare('UPDATE Users_TBL SET password=:p WHERE id=:id')->execute(['p'=>password_hash('fictional-password', PASSWORD_BCRYPT), 'id'=>$uid]); }
// Demonstrate catalog cascades only inside a rolled-back fictional transaction.
$beforeCascade=Vinyl::countByUser(7);$pdo->beginTransaction();$pdo->exec('DELETE FROM GENRES_TBL WHERE Id=1');check(Vinyl::countByUser(7)===0,'catalog cascade confirmed in fixture');$pdo->rollBack();check(Vinyl::countByUser(7)===$beforeCascade,'fictional cascade rollback');
check(count(Vinyl::allowedSorts())===12,'twelve sort options retained');
echo 'OK: '.$count.' database/image checks; isolated MariaDB '.$pdo->query('SELECT VERSION()')->fetchColumn()."; fictional data only.\n";
