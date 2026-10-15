<?php
declare(strict_types=1);
require __DIR__.'/connect.php';require __DIR__.'/fixtures.php';
$_ENV['VLEX_ENC_ENABLED']='1';
use App\Services\{EncyclopediaCollection,EncyclopediaLink,EncyclopediaManual};
use App\Models\{EncyclopediaRecord,EncyclopediaEntity};
use App\Core\HttpException;
$n=0;
function ok(bool $v,string $label):void {global $n;if(!$v)throw new RuntimeException('FAIL: '.$label);$n++;}
function reject(callable $f,int $code,string $label):void{try{$f();}catch(HttpException $e){ok($e->status===$code,$label);return;}throw new RuntimeException('Accepted: '.$label);}
function legacy(PDO $p):array{return array_map(fn($t)=>$p->query('SELECT * FROM '.$t.' ORDER BY 1,2')->fetchAll(),['VINYLS_TBL','AUTHORS_TBL','AUTOR_VINYLS_TBL','Users_TBL']);}
$key=fn()=>bin2hex(random_bytes(32));
$m=App\Services\Metadata\MusicBrainzNormalizer::release(json_decode(file_get_contents(__DIR__.'/reference/enjoy-release.json'),true),json_decode(file_get_contents(__DIR__.'/reference/enjoy-group.json'),true));
$pdo->exec("INSERT INTO VINYLS_TBL(Id,Title,Genres_Id,Format_Id,Condition_Id,Record_Label_Id,Producer,Release_date,Edition_Id,User_Id,Is_Favorite,Is_Desired,Image_Path) VALUES(320,'Personal preserved title',1,1,1,1,'Personal producer',1993,1,7,1,1,'uploads/covers/private-fiction.png')");
$pdo->exec('INSERT INTO AUTOR_VINYLS_TBL SELECT Id,320 FROM AUTHORS_TBL LIMIT 1');
$before=legacy($pdo);$k=$key();
ok(EncyclopediaLink::apply(320,7,$m,'0:0',$k)===320,'legacy association returns same ID');
$r=EncyclopediaRecord::forCopy(320,7);$state=EncyclopediaLink::state($r['copy']);
ok(legacy($pdo)===$before,'all traditional records users authors covers and flags exactly unchanged');
ok(count($r['tracks'])===2&&count(array_filter($r['credits'],fn($c)=>!empty($c['artist_id'])))===20&&count($r['credits'])>=20&&count($r['labels'])===3&&$r['media'][0]['format']==='CD','reference import keeps full CD graph');
ok(EncyclopediaLink::apply(320,7,$m,'0:0',$k)===320&&legacy($pdo)===$before,'double click retry idempotent no extra VINYLS');
reject(fn()=>EncyclopediaLink::apply(320,8,$m,'0:0',$key()),404,'foreign owner rejected');
reject(fn()=>EncyclopediaLink::apply(320,7,$m,'0:0',$key()),409,'stale association rejected');
reject(fn()=>EncyclopediaLink::apply(320,7,null,$state,$k),409,'same key changed operation rejected');
$pdo->exec("UPDATE ENC_COPIES SET notes='PRIVATE_PRESERVED',overrides_json=JSON_SET(overrides_json,'$.country','PERSONAL COUNTRY') WHERE vinyl_id=320");
$ed=$r['edition']['id'];$album=$r['album']['id'];$editionCount=(int)$pdo->query('SELECT COUNT(*) FROM ENC_EDITIONS')->fetchColumn();
$k2=$key();EncyclopediaLink::apply(320,7,null,$state,$k2);$detached=EncyclopediaRecord::forCopy(320,7);
ok($detached['edition']===null&&legacy($pdo)===$before,'detach keeps original and all legacy fields');
ok((int)$pdo->query('SELECT COUNT(*) FROM ENC_EDITIONS')->fetchColumn()===$editionCount,'detach never deletes shared music');
ok($detached['copy']['notes']==='PRIVATE_PRESERVED'&&json_decode($detached['copy']['overrides_json'],true)['country']==='PERSONAL COUNTRY','detach retains private corrections');
$expected=EncyclopediaLink::state($detached['copy']);$parallel=$key();$workers=[];
for($i=0;$i<2;$i++){$proc=proc_open([PHP_BINARY,__DIR__.'/link-concurrent.php','320',$expected,$parallel],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$workers[]=[$proc,$pipes];}
foreach($workers as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);ok(proc_close($proc)===0&&$err===''&&$out==='320','concurrent association same key succeeds once');}
$r=EncyclopediaRecord::forCopy(320,7);
ok((int)$r['edition']['id']===(int)$ed&&legacy($pdo)===$before,'relink reuses identity without duplication');
$rev=EncyclopediaManual::revision((int)$r['album']['id']);
reject(fn()=>EncyclopediaManual::save(320,7,'shared','album',['title'=>'Bad'],$rev,$key()),403,'shared editing denied by default');
reject(fn()=>EncyclopediaManual::save(320,7,'private','album',['title'=>'Bad'],$rev,$key()),403,'private edit cannot modify shared import');
reject(fn()=>EncyclopediaManual::save(320,7,'private','artist_identity',['entity_id'=>'1','name'=>'Forbidden','kind'=>'person'],$rev,$key()),403,'canonical identity edit requires explicit editorial permission');
$forkKey=$key();EncyclopediaManual::save(320,7,'private','prepare',[],$rev,$forkKey);$draft=EncyclopediaRecord::forCopy(320,7);
ok($draft['album']['draft_owner_id']===7&&$draft['edition']['musicbrainz_release_id']===null,'explicit fork creates private musical identity');
ok(count($draft['tracks'])===2&&count(array_filter($draft['credits'],fn($c)=>!empty($c['artist_id'])))===20&&count($draft['credits'])>=20&&count($draft['works'])===2,'private fork retains songs works and all credits');
ok(in_array('musicbrainz',array_column($draft['sources'],'provider'),true)&&in_array('manual',array_column($draft['sources'],'provider'),true),'private fork preserves original source alongside manual provenance');
ok($draft['media'][0]['format']==='CD','manual preparation never converts CD to vinyl');
ok(legacy($pdo)===$before&&$draft['copy']['notes']==='PRIVATE_PRESERVED','private preparation preserves collection and notes');
EncyclopediaManual::save(320,7,'private','prepare',[],$rev,$forkKey);
ok((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE vinyl_id=320')->fetchColumn()===1,'prepare retry remains single copy');
$revision=EncyclopediaManual::revision((int)$draft['album']['id']);$parallel=$key();$workers=[];
$historyBefore=(int)$pdo->query('SELECT COUNT(*) FROM ENC_ASSERTIONS')->fetchColumn();
for($i=0;$i<2;$i++){$proc=proc_open([PHP_BINARY,__DIR__.'/manual-concurrent.php','320',(string)$revision,$parallel],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$workers[]=[$proc,$pipes];}
foreach($workers as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);ok(proc_close($proc)===0&&$err===''&&$out==='320','concurrent manual confirmation completes safely');}
ok((int)$pdo->query('SELECT COUNT(*) FROM ENC_ASSERTIONS')->fetchColumn()===$historyBefore+1,'concurrent manual edit creates exactly one revision');
$save=function(string $op,array $data)use($key):void{$r=EncyclopediaRecord::forCopy(320,7);EncyclopediaManual::save(320,7,'private',$op,$data,EncyclopediaManual::revision((int)$r['album']['id']),$key());};
$t=$draft['tracks'][0];$mid=$draft['media'][0]['id'];
$save('track',['entity_id'=>(string)$t['id'],'medium_id'=>(string)$mid,'position'=>'1','printed_number'=>'A1','side'=>'A','title'=>'Manual song <script>bad()</script>','duration'=>'5:07','recording_id'=>(string)$t['recording_id']]);
$r=EncyclopediaRecord::forCopy(320,7);ok($r['tracks'][0]['duration_ms']===307000&&$r['tracks'][0]['side']==='A','structured song correction duration order side');
$save('track',['medium_id'=>(string)$mid,'position'=>'3','printed_number'=>'B1','side'=>'B','title'=>'New manual song','duration'=>'3:10']);
$r=EncyclopediaRecord::forCopy(320,7);$newTrack=$r['tracks'][2]['id'];$save('recording',['track_id'=>(string)$newTrack,'title'=>'Manual studio recording','duration'=>'3:10']);
$r=EncyclopediaRecord::forCopy(320,7);$rid=$r['tracks'][2]['recording_id'];$save('work',['recording_id'=>(string)$rid,'title'=>'Manual composition']);
$r=EncyclopediaRecord::forCopy(320,7);$wid=$pdo->query('SELECT work_id FROM ENC_RECORDING_WORKS WHERE recording_id='.(int)$rid)->fetchColumn();
ok((int)$wid>0&&count($r['tracks'])===3,'new track recording and composition related structurally');
$save('credit',['target_kind'=>'track','target_id'=>(string)$newTrack,'artist_name'=>'Manual fictional musician','artist_kind'=>'person','role'=>'performer','instrument'=>'synthesizer']);
$artist=(int)$pdo->query("SELECT id FROM ENC_ARTISTS WHERE name='Manual fictional musician'")->fetchColumn();
$save('credit',['target_kind'=>'work','target_id'=>(string)$wid,'artist_id'=>(string)$artist,'role'=>'composer']);
$r=EncyclopediaRecord::forCopy(320,7);ok(count(array_filter($r['credits'],fn($c)=>!empty($c['artist_id'])))===22&&count($r['credits'])>=22&&in_array('synthesizer',array_column($r['credits'],'instrument'),true),'structured instrument and composer credits');
$save('artist',['artist_id'=>(string)$artist,'position'=>'2','credited_as'=>'Manual group member']);
$save('label',['label_name'=>'Manual fictional label','catalogue_number'=>'PRIVATE 001']);
$save('genre',['kind'=>'genre','name'=>'Manual electronic']);
$r=EncyclopediaRecord::forCopy(320,7);ok(count($r['labels'])===4&&count($r['genres'])===1,'structured label catalogue and genre');
$label=$r['labels'][0];$save('label',['label_id'=>(string)$label['id'],'catalogue_number'=>'CORRECTED CAT','old_label_id'=>(string)$label['id'],'old_catalogue_number'=>$label['catalogue_number']]);
ok((int)$pdo->query("SELECT COUNT(*) FROM ENC_EDITION_LABELS WHERE edition_id=".$r['edition']['id']." AND catalogue_number='CORRECTED CAT'")->fetchColumn()===1,'catalogue correction updates association');
$rev=EncyclopediaManual::revision((int)$r['album']['id']);
reject(fn()=>EncyclopediaManual::save(320,7,'private','credit',['target_kind'=>'work','target_id'=>(string)$wid,'artist_id'=>(string)$artist,'role'=>'composer'],$rev,$key()),409,'duplicate structured credit rejected');
reject(fn()=>EncyclopediaManual::save(320,7,'private','medium',['position'=>'2','format'=>'Vinyl','diameter_inches'=>'12.345'],$rev,$key()),422,'diameter precision cannot silently round');
reject(fn()=>EncyclopediaManual::save(320,7,'private','track',['medium_id'=>(string)$mid,'position'=>'4','title'=>'Invalid','duration'=>'3:99'],$rev,$key()),422,'invalid duration rolls back');
reject(fn()=>EncyclopediaManual::save(320,7,'private','credit',['target_kind'=>'edition;DROP TABLE','target_id'=>'1','artist_id'=>(string)$artist,'role'=>'producer'],$rev,$key()),422,'SQL injection in target blocked');
reject(fn()=>EncyclopediaManual::save(320,7,'private','credit',['target_kind'=>'track','target_id'=>(string)$newTrack,'artist_name'=>'Manual fictional musician','role'=>'producer'],$rev,$key()),422,'matching identity requires verified ID no duplicate');
reject(fn()=>EncyclopediaManual::save(320,8,'private','album',['title'=>'Forbidden'],$rev,$key()),404,'foreign editor rejected');
reject(fn()=>EncyclopediaManual::save(320,7,'private','album',['title'=>'Stale'],0,$key()),409,'concurrent stale revision blocked');
ok(EncyclopediaEntity::find('work',(int)$wid,7)!==null&&EncyclopediaEntity::find('work',(int)$wid,8)===null,'work navigation respects draft privacy');
ok(EncyclopediaEntity::find('recording',(int)$rid,7)!==null&&EncyclopediaEntity::find('recording',(int)$rid,8)===null,'recording privacy');
ok(EncyclopediaEntity::find('artist',$artist,7)!==null&&EncyclopediaEntity::find('artist',$artist,8)===null,'manual artist private relationships not exposed');
ok($pdo->query('SELECT COUNT(*) FROM ENC_ASSERTIONS WHERE author_user_id=7 AND manually_locked=1')->fetchColumn()>10,'manual author date source and before/after history');
// Shared permission is opt-in, and source refresh must not silently overwrite a manual correction.
EncyclopediaLink::apply(320,7,$m,EncyclopediaLink::state($r['copy']),$key());$r=EncyclopediaRecord::forCopy(320,7);$_ENV['VLEX_ENC_SHARED_EDITORS']='7';
$flood=(int)$pdo->query("SELECT id FROM ENC_ARTISTS WHERE name='Flood'")->fetchColumn();
EncyclopediaManual::save(320,7,'shared','artist_identity',['entity_id'=>(string)$flood,'name'=>'Flood test editorial correction','kind'=>'person','identity_before_hash'=>EncyclopediaManual::identityFingerprint($pdo->query('SELECT * FROM ENC_ARTISTS WHERE id='.$flood)->fetch())],EncyclopediaManual::revision((int)$r['album']['id']),$key());
reject(fn()=>EncyclopediaManual::save(320,7,'shared','artist_identity',['entity_id'=>(string)$flood,'name'=>'Unexpected overwrite','kind'=>'person','identity_before_hash'=>str_repeat('0',64)],EncyclopediaManual::revision((int)$r['album']['id']),$key()),409,'global identity stale fingerprint rejected across album contexts');
ok($pdo->query('SELECT name FROM ENC_ARTISTS WHERE id='.$flood)->fetchColumn()==='Flood test editorial correction','authorized canonical identity correction preserves identity ID');
EncyclopediaManual::save(320,7,'shared','artist_identity',['entity_id'=>(string)$flood,'name'=>'Flood','kind'=>'person','identity_before_hash'=>EncyclopediaManual::identityFingerprint($pdo->query('SELECT * FROM ENC_ARTISTS WHERE id='.$flood)->fetch())],EncyclopediaManual::revision((int)$r['album']['id']),$key());
EncyclopediaManual::save(320,7,'shared','album',['title'=>'Editorial manually corrected','original_date'=>'1992','release_type'=>'Single'],EncyclopediaManual::revision((int)$r['album']['id']),$key());
EncyclopediaLink::apply(320,7,$m,EncyclopediaLink::state($r['copy']),$key());$r=EncyclopediaRecord::forCopy(320,7);
ok($r['album']['title']==='Editorial manually corrected','source refresh cannot overwrite approved shared correction');
ok(json_decode($r['copy']['overrides_json'],true)['country']==='PERSONAL COUNTRY'&&$r['vinyl']['Title']==='Personal preserved title','personal correction prevails after shared update and relink');
ok(legacy($pdo)===$before,'no traditional rows modified through full workflow');
// Reset only this synthetic shared title for the independent browser fixture expectations.
EncyclopediaManual::save(320,7,'shared','album',['title'=>$m['album']['title'],'original_date'=>$m['album']['original_date'],'release_type'=>$m['album']['type']],EncyclopediaManual::revision((int)$r['album']['id']),$key());
$personalDraft=EncyclopediaCollection::create(7,['title'=>'Original personal draft','authors'=>['Fictional author'],'release_date'=>'1984']);
$draftRecord=EncyclopediaRecord::forCopy($personalDraft,7);
EncyclopediaManual::save($personalDraft,7,'private','prepare',[],EncyclopediaManual::revision((int)$draftRecord['album']['id']),$key());
$draftRecord=EncyclopediaRecord::forCopy($personalDraft,7);
EncyclopediaManual::save($personalDraft,7,'private','album',['title'=>'Distinct manual musical title'],EncyclopediaManual::revision((int)$draftRecord['album']['id']),$key());
$values=App\Core\VinylInput::validate(['title'=>'Changed personal title','authors'=>['Fictional author'],'release_date'=>'1984'],null,true);
ok(App\Models\Vinyl::updateForUser($personalDraft,7,$values),'personal editor saves a prepared musical draft copy');
$draftRecord=EncyclopediaRecord::forCopy($personalDraft,7);
ok($draftRecord['vinyl']['Title']==='Changed personal title'&&$draftRecord['album']['title']==='Distinct manual musical title','personal title edit never overwrites manually corrected musical title');
echo 'OK: '.$n." association/manual/navigation checks; isolated fictional collection and public reference only.\n";
