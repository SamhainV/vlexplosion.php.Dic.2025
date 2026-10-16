<?php
declare(strict_types=1);
require dirname(__DIR__).'/encyclopedia/connect.php';
require __DIR__.'/fixtures.php';
use App\Services\CoverArtClient as Client;
use App\Services\CoverArtHttp as Http;
use App\Models\CoverArt as Art;
use App\Models\Vinyl;
use App\Core\CoverStore;
$checks=0;
function ok(bool $b,string $name):void{global $checks;if(!$b)throw new RuntimeException('FAIL '.$name);$checks++;echo 'PASS '.$name."\n";}
function fails(callable $fn,string $name):void{try{$fn();}catch(Throwable){ok(true,$name);return;}ok(false,$name);}
$id='11111111-1111-4111-8111-111111111111';$group='22222222-2222-4222-8222-222222222222';
Http::$transport='caa_fixture';
foreach(['','localhost','../foo','11111111-1111-1111-1111-11111111111z'] as $bad)ok(!Client::mbid($bad),'invalid MBID');
foreach(['127.0.0.1','10.1.1.1','169.254.169.254','100.64.0.1','198.18.0.1','224.0.0.1','::1','::ffff:8.8.8.8','fc00::1','fe80::1','ff02::1','2001:db8::1'] as $ip)ok(!Http::publicIp($ip),'internal/reserved IP rejected');
ok(Http::publicIp('8.8.8.8'),'public IPv4');ok(Http::publicIp('2606:4700:4700::1111'),'public IPv6');
foreach(['https://localhost/','https://archive.org.evil.test/download/x.jpg','https://user@archive.org/x.jpg','https://archive.org:443/x.jpg','https://archive.org/download/../x.jpg','https://archive.org/download/%2e%2e/x.jpg','file:///etc/passwd','https://127.0.0.1/x.jpg','https://archive.org/download/x.svg'] as $bad)fails(fn()=>Http::validateUrl($bad),'SSRF URL rejected');
Http::validateUrl('https://archive.org/download/mbid-'.$id.'/index.json');ok(true,'CAA JSON redirect accepted');
Http::$transport=fn()=>['status'=>302,'headers'=>['location'=>'https://127.0.0.1/secret'],'body'=>''];fails(fn()=>Http::get('https://coverartarchive.org/release/'.$id,100,time()+2),'redirect SSRF rejected');
Http::$transport=fn()=>['status'=>302,'headers'=>['location'=>'https://coverartarchive.org/release/'.$id],'body'=>''];fails(fn()=>Http::get('https://coverartarchive.org/release/'.$id,100,time()+2),'redirect loop bounded');
Http::$transport=fn()=>['status'=>200,'headers'=>[],'body'=>str_repeat('x',101)];fails(fn()=>Http::get('https://coverartarchive.org/release/'.$id,100,time()+2),'response size bounded');
Http::$transport='caa_fixture';
$pdo->exec("INSERT INTO ENC_ALBUMS(id,title,musicbrainz_group_id) VALUES(900,'Fictional cover album','$group'); INSERT INTO ENC_EDITIONS(id,album_id,musicbrainz_release_id) VALUES(900,900,'$id')");
$pdo->exec("INSERT INTO VINYLS_TBL(Id,User_Id,Title,Image_Path) VALUES(900,7,'Cover fixture',NULL),(901,7,'Manual fixture','assets/images/default-cover.webp'),(902,7,'Unlinked fixture',NULL),(903,8,'Other owner',NULL); INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id) VALUES(900,7,900,900),(901,7,900,900),(903,8,900,900)");
$_SESSION['user']=['id'=>7];
fails(fn()=>Art::copy(903,7),'ownership enforced');
ok(count(Art::eligible(7))===1,'preview excludes manual/unlinked/other owner');
$r=Client::fetch('release',$id);ok($r['result']==='ready','exact approved front downloaded');
$key=$r['storage_key'];ok(is_file(Client::path($key)),'local verified image published');ok($r['release_mbid']===$id,'release provenance');
$hash=hash_file('sha256',Client::path($key));ok($hash===$r['sha256'],'published hash');
$calls=0;Http::$transport=function()use(&$calls){$calls++;throw new RuntimeException('network should not run');};
ok(Client::fetch('release',$id)['result']==='ready'&&$calls===0,'positive cache avoids requests');
Art::select(901,7,'auto');ok($pdo->query('SELECT Image_Path FROM VINYLS_TBL WHERE Id=901')->fetchColumn()==='assets/images/default-cover.webp','manual path retained when automatic selected');Art::select(901,7,'manual');
Art::preload([['Id'=>901]],7);ok(Art::display(['Id'=>901])['origin']==='Carátula aportada por ti','manual precedence');
Art::preload([['Id'=>900]],7);ok(str_contains(Art::url(['Id'=>900]),'covers/image?id=900'),'automatic used without manual');
// Shared same content is not duplicated on disk, even across releases.
ok(Client::publish(caa_fixture('https://example.invalid/x.png',1)['body'])===$key,'content deduplication');
fails(fn()=>Client::publish('<?php echo 1; ?>'),'executable rejected');fails(fn()=>Client::publish('<svg></svg>'),'SVG rejected');fails(fn()=>Client::publish(str_repeat('x',5*1024*1024+1)),'oversized image rejected');
$raw=caa_fixture('https://example.invalid/x.png',1)['body'];fails(fn()=>Client::publish(substr($raw,0,35)),'truncated raster rejected');
function resetCache():void{global $pdo;$pdo->exec('DELETE FROM CAA_IMAGES');@unlink(app_storage_path('cover-art').'/.next');}
foreach([404,429,503,500] as $status){resetCache();Http::$transport=fn()=>['status'=>$status,'headers'=>['retry-after'=>'3'],'body'=>''];$r=Client::fetch('release',$id);ok($r['result']===match($status){404=>'missing',429,503=>'waiting',default=>'error'},'HTTP '.$status.' handled');if(in_array($status,[429,503]))ok($r['retry_at']>=time()+2,'Retry-After respected');}
resetCache();Http::$transport=fn()=>throw new RuntimeException('interrupted');ok(Client::fetch('release',$id)['result']==='error','interrupted network safe');
resetCache();Http::$transport=fn()=>['status'=>200,'headers'=>[],'body'=>'invalid'];ok(Client::fetch('release',$id)['result']==='error','malformed JSON safe');
resetCache();Http::$transport=fn()=>['status'=>200,'headers'=>[],'body'=>json_encode(['images'=>[['front'=>false,'approved'=>true]]])];ok(Client::fetch('release',$id)['result']==='missing','no front normal absence');
resetCache();Http::$transport='caa_fixture';$r=Client::fetch('release-group',$group);ok($r['result']==='ready','explicit approximate alternative downloaded');
Art::preload([['Id'=>900]],7);ok(Art::display(['Id'=>900])['origin']==='Sin carátula','group never silently assigned');Art::select(900,7,'group');Art::preload([['Id'=>900]],7);ok(str_contains(Art::display(['Id'=>900])['origin'],'aproximada'),'group explicitly selected and labelled');
resetCache();Http::$transport='caa_fixture';
$s=Art::batchAction(7,'start');ok(count($s['queue'])===1&&$s['reviewed']===0,'batch start snapshots without downloading');
$s=Art::batchAction(7,'pause');ok($s['state']==='paused','batch paused durably');
$s=Art::batchAction(7,'step');ok($s['state']==='complete'&&$s['downloaded']===1&&$s['reviewed']===1,'batch resume completes one record');
ok(Art::batch(7)===$s,'progress persisted');ok(count(Art::eligible(7))===0,'completed records not downloaded again');
$s=Art::batchAction(7,'start');ok(count($s['queue'])===0&&$s['state']==='complete','repeated batch idempotent');
// Resumed after an interruption between cache commit and cursor save.
$pdo->exec('DELETE FROM CAA_BATCHES; DELETE FROM CAA_IMAGES');@unlink(app_storage_path('cover-art').'/.next');
$s=Art::batchAction(7,'start');Client::fetch('release',$id);$calls=0;Http::$transport=function()use(&$calls){$calls++;throw new RuntimeException('no repeat');};$s=Art::batchAction(7,'step');ok($s['downloaded']===1&&$calls===0,'resume after cache commit avoids repeat request');
// Error budget; no unbounded retries, no changes to manual cover.
$pdo->exec('DELETE FROM CAA_BATCHES; DELETE FROM CAA_IMAGES');@unlink(app_storage_path('cover-art').'/.next');Art::batchAction(7,'start');Http::$transport=fn()=>throw new RuntimeException('network');
for($i=0;$i<3;$i++){ $pdo->exec('UPDATE CAA_IMAGES SET retry_at=0');$s=Art::batch(7);$s['retry_at']=0;$q=$pdo->prepare('UPDATE CAA_BATCHES SET state_json=? WHERE owner_id=7');$q->execute([json_encode($s)]);@unlink(app_storage_path('cover-art').'/.next');$s=Art::batchAction(7,'step');}
ok($s['state']==='complete'&&$s['errors']===1,'three failures terminate record');
ok($pdo->query('SELECT Image_Path FROM VINYLS_TBL WHERE Id=901')->fetchColumn()==='assets/images/default-cover.webp','manual untouched after failures');
// A manual upload while a batch is paused makes the queued record ineligible.
$pdo->exec('DELETE FROM CAA_BATCHES; DELETE FROM CAA_IMAGES');@unlink(app_storage_path('cover-art').'/.next');Art::batchAction(7,'start');
$pdo->exec("UPDATE VINYLS_TBL SET Image_Path='assets/images/default-cover.webp' WHERE Id=900");
$s=Art::batchAction(7,'step');ok($s['skipped']===1&&$s['downloaded']===0,'manual cover added during pause skipped');
$pdo->exec('UPDATE VINYLS_TBL SET Image_Path=NULL WHERE Id=900');
// Traditional CRUD, owner checks, filters and pagination with the new schema.
$data=['title'=>'Synthetic CRUD cover','genre_id'=>1,'format_id'=>1,'condition_id'=>1,'record_label_id'=>1,'producer'=>'Synthetic','release_date'=>2000,'edition_id'=>1,'is_favorite'=>1,'is_desired'=>0,'author'=>'Fiction','image_path'=>null];
$new=Vinyl::createForUser(7,$data);ok($new>0,'traditional create');$data['title']='Synthetic edit';$data['image_path']='assets/images/default-cover.webp';ok(Vinyl::updateForUser($new,7,$data),'traditional update with manual cover');ok(!Vinyl::updateForUser($new,8,$data),'CRUD owner isolation');
ok(Vinyl::findByIdForUser($new,7)['Image_Path']===$data['image_path'],'manual path persists');
ok(count(Vinyl::paginateByUser(7,1,0,'newest',[]))===1,'pagination limit');ok(Vinyl::countByUser(7,['q'=>'Synthetic edit','fav'=>1,'desired'=>0])===1,'search and favourite filter');ok(Vinyl::deleteForUser($new,7),'traditional delete');
// Refresh failures preserve a previously verified automatic image.
resetCache();Http::$transport='caa_fixture';Client::fetch('release',$id);@unlink(app_storage_path('cover-art').'/.next');Http::$transport=fn()=>throw new RuntimeException('interruption');$r=Client::fetch('release',$id,true);ok($r['result']==='error'&&is_file(Client::path($r['storage_key'])),'refresh failure keeps working automatic image');
Art::select(901,7,'auto');$data['title']='Manual fixture';$data['image_path']='assets/images/vintage-bg.png';Vinyl::updateForUser(901,7,$data);Art::preload([['Id'=>901]],7);ok(Art::display(['Id'=>901])['origin']==='Carátula aportada por ti','new manual upload restores priority');ok(is_file(Client::path($key)),'manual change retains automatic bytes');
// Shared lock rejects overlapping provider work before any new HTTP call.
$held=fopen(app_storage_path('cover-art').'/.lock','c');flock($held,LOCK_EX);fails(fn()=>Client::fetch('release',$id),'overlapping provider operation rejected');flock($held,LOCK_UN);fclose($held);
// Prepare the demonstration again; no provider traffic.
resetCache();Http::$transport='caa_fixture';Client::fetch('release',$id);Art::select(900,7,'auto');
$pdo->prepare('UPDATE Users_TBL SET password=? WHERE id=7')->execute([password_hash('fictional-password',PASSWORD_DEFAULT)]);
ok(count(Vinyl::allowedSorts())===12,'12 collection sorts unchanged');
foreach(array_keys(Vinyl::allowedSorts()) as $sort)ok(count(Vinyl::paginateByUser(7,12,0,$sort,[]))===3,'collection sort '.$sort);
echo "CHECKS $checks\n";

// Two additional synthetic editions for the browser's bounded batch flow.
$pdo->exec("DELETE FROM CAA_BATCHES; INSERT INTO ENC_EDITIONS(id,album_id,musicbrainz_release_id) VALUES(904,900,'44444444-4444-4444-8444-444444444444'),(905,900,'55555555-5555-4555-8555-555555555555'); INSERT INTO VINYLS_TBL(Id,User_Id,Title) VALUES(904,7,'Batch fiction A'),(905,7,'Batch fiction B'); INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id) VALUES(904,7,900,904),(905,7,900,905)");
