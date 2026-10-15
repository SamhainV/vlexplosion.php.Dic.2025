<?php
declare(strict_types=1);
require dirname(__DIR__).'/encyclopedia/connect.php';require __DIR__.'/fixtures.php';
use App\Services\{EncyclopediaCollection as Collection,EncyclopediaCreditSync as Sync,EncyclopediaManual as Manual};
use App\Services\Metadata\{MusicBrainzClient,MusicBrainzNormalizer as Normalize};
use App\Models\EncyclopediaRecord as Record;use App\Core\HttpException;
$_ENV['VLEX_ENC_ENABLED']='1';$n=0;
function checkProgress(bool $v,string $label):void{global $n;if(!$v)throw new RuntimeException('FAIL: '.$label);$n++;}
function rowsProgress(string $sql,array $args=[]):array{global $pdo;$s=$pdo->prepare($sql);$s->execute($args);return $s->fetchAll();}
function rejectProgress(callable $f):void{try{$f();}catch(HttpException $e){checkProgress($e->status===409,'incompatible action refused');return;}throw new RuntimeException('Expected 409');}
function checkpointProgress(string $key,array $s):void{global $pdo;$pdo->prepare('UPDATE ENC_CREDIT_JOBS SET state_json=? WHERE id=?')->execute([json_encode($s),$key]);}
function clientProgress(array $data,?callable $override=null,?int &$network=null):MusicBrainzClient{
 $clock=(float)time();return new MusicBrainzClient(getenv('VLEX_ENC_RUNTIME').'/progress-'.bin2hex(random_bytes(4)),'VLexPlosion/test (https://example.invalid)',function($url)use($data,$override,&$network){$network++;$k=substr(parse_url($url,PHP_URL_PATH),6);$out=$override?$override($k):null;return $out??['status'=>isset($data[$k])?200:404,'headers'=>[],'body'=>json_encode($data[$k]??[],JSON_THROW_ON_ERROR)];},function()use(&$clock){return $clock;},function($s)use(&$clock){$clock+=$s;});
}
function finishProgress(string $key,MusicBrainzClient $client):array{for($i=0;$i<15;$i++){$j=Sync::job($key,7);if($j['status']!=='collecting')return $j;$j=Sync::step($key,7,$client);if($j['state']['retry_at']>time())return $j;}throw new RuntimeException('Unbounded test');}
$data=credit_fixtures();$release=$data['release/'.encyclopedia_fixture_id(2)];$group=$data['release-group/'.encyclopedia_fixture_id(1)];
$copy=Collection::create(7,['title'=>'Progress fixture edition','authors'=>['Fictional personal artist'],'release_date'=>1984],Normalize::release(['data'=>$release,'fetched_at'=>1],['data'=>$group,'fetched_at'=>1]),null,['notes'=>'SYNTHETIC PRIVATE NOTE']);
$personal=rowsProgress('SELECT * FROM VINYLS_TBL');$personalCopies=rowsProgress('SELECT * FROM ENC_COPIES');
$key=Sync::start($copy,7);checkProgress(Sync::start($copy,7)===$key,'start reuses pending checkpoint');
$before=rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id');$network=0;$client=clientProgress($data,null,$network);
$j=Sync::step($key,7,$client);checkProgress($j['state']['calls']<=3,'step bounded');checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id')===$before,'collecting never saves partial credits');
// Reproduce the observed durable 8 received / 7 pending / 9 calls independently of real data.
$observed=$j;$observed['state']['responses']=array_fill_keys(range(1,8),[]);$observed['state']['queue']=range(1,7);$observed['state']['calls']=9;$observed['state']['error']=null;
checkProgress(Sync::presentation($observed)['phase']==='collecting','8/7/9 is pending, not paused or saved');
$j=finishProgress($key,$client);checkProgress($j['status']==='ready'&&!$j['state']['queue'],'full collection ready');checkProgress(Sync::presentation($j)['phase']==='ready','collection and save are different states');
checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id')===$before,'ready preview still preserves credits');
$j=Sync::apply($key,7);checkProgress($j['status']==='complete'&&$j['state']['summary']['new']===10&&$j['state']['context_summary']['new']===2,'real committed new count');checkProgress(!empty($j['state']['applied_at']),'committed timestamp');
$credits=rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id');$assertions=rowsProgress('SELECT * FROM ENC_ASSERTIONS ORDER BY id');Sync::apply($key,7);
checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id')===$credits&&rowsProgress('SELECT * FROM ENC_ASSERTIONS ORDER BY id')===$assertions,'double apply no duplicate or audit');
$completed=$key;$key=Sync::start($copy,7);finishProgress($key,clientProgress($data));$j=Sync::apply($key,7);
checkProgress($j['state']['summary']['new']===0&&$j['state']['summary']['updated']===0&&$j['state']['summary']['existing']===10&&$j['state']['context_summary']['existing']===2,'same credits despite different fetched timestamps');
checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id')===$credits,'unchanged credit rows genuinely unchanged');
// Mark one real fixture relationship manual, then repeat with a new provider credit.
$r=Record::forCopy($copy,7);$c=array_values(array_filter($r['credits'],fn($c)=>!empty($c['relation_key'])&&$c['role']==='engineer'))[0];
$_ENV['VLEX_ENC_SHARED_EDITORS']='7';Manual::save($copy,7,'shared','credit',['entity_id'=>$c['id'],'target_kind'=>'edition','target_id'=>$c['edition_id'],'artist_id'=>$c['artist_id'],'role'=>$c['role'],'instrument'=>$c['instrument']??'','credited_as'=>$c['credited_as']??''],Manual::revision((int)$r['album']['id']),bin2hex(random_bytes(32)));unset($_ENV['VLEX_ENC_SHARED_EDITORS']);
$manual=rowsProgress('SELECT * FROM ENC_CREDITS WHERE id=?',[$c['id']]);$changed=$data;$extra=$release['relations'][0];$extra['type']='lighting';$changed['release/'.$release['id']]['relations'][]=$extra;
$key=Sync::start($copy,7);finishProgress($key,clientProgress($changed));$j=Sync::apply($key,7);
checkProgress($j['state']['summary']['new']===1&&$j['state']['summary']['manual_preserved']===1,'new and protected manual counts');checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS WHERE id=?',[$c['id']])===$manual,'manual byte-identical');
// A display-name correction changes an existing edge without changing its identity.
$updatedData=$changed;$updatedData['release/'.$release['id']]['relations'][0]['artist']['name']='Updated fictional display name';
$key=Sync::start($copy,7);finishProgress($key,clientProgress($updatedData));$j=Sync::apply($key,7);checkProgress($j['state']['summary']['updated']===1,'updated count excludes timestamp-only refreshes');
// Temporary errors retain queue and do not spend attempts while the local cooldown blocks network.
foreach([503,429] as $status){
 $key=Sync::start($copy,7);$calls=0;$flaky=clientProgress($data,fn()=>['status'=>$status,'headers'=>['retry-after'=>'30'],'body'=>''],$calls);
 $j=Sync::step($key,7,$flaky);checkProgress($j['status']==='collecting'&&$j['state']['last_http_status']===$status,'specific HTTP '.$status.' recorded');checkProgress(Sync::presentation($j)['phase']==='paused','clear paused state');
 checkProgress($j['state']['retry_at']>=time()+29,'Retry-After honored');$beforeCalls=$calls;$beforeState=$j['state'];Sync::step($key,7,$flaky);checkProgress($calls===$beforeCalls&&Sync::job($key,7)['state']===$beforeState,'no immediate retry');
 // A premature browser retry sees the client cache's local gate, not a second upstream failure.
 $s=$j['state'];$s['retry_at']=0;checkpointProgress($key,$s);$j=Sync::step($key,7,$flaky);
 checkProgress($calls===$beforeCalls&&$j['state']['calls']===$beforeState['calls'],'local cooldown does not consume calls');checkProgress(max($j['state']['attempts'])===1,'local cooldown does not exhaust entity attempts');
 $s=$j['state'];$s['retry_at']=0;checkpointProgress($key,$s);$j=finishProgress($key,clientProgress($changed));checkProgress($j['status']==='ready','resume same durable queue');Sync::apply($key,7);
}
$key=Sync::start($copy,7);Sync::step($key,7,clientProgress($data));$j=Sync::job($key,7);$saved=$j['state'];Sync::cancel($key,7);checkProgress(Sync::job($key,7)['state']===$saved,'cancel preserves all checkpoint data');rejectProgress(fn()=>Sync::apply($key,7));Sync::resume($key,7);checkProgress(Sync::job($key,7)['state']===$saved,'resume no budget reset or progress loss');$j=finishProgress($key,clientProgress($changed));Sync::cancel($key,7);Sync::resume($key,7);checkProgress(Sync::job($key,7)['status']==='ready','cancelled ready review resumes directly');Sync::apply($key,7);
$key=Sync::start($copy,7);$pdo->prepare('UPDATE ENC_CREDIT_JOBS SET created_at=DATE_SUB(NOW(),INTERVAL 2 DAY) WHERE id=?')->execute([$key]);checkProgress(Sync::pending($copy,7)['id']===$key,'old checkpoint remains discoverable');checkProgress(Sync::step($key,7,clientProgress($data))['state']['calls']>0,'old checkpoint explicitly resumes without resetting budget');Sync::cancel($key,7);
$key=Sync::start($copy,7);$pdo->prepare('UPDATE ENC_CREDIT_JOBS SET lease_token=?,lease_until=DATE_ADD(NOW(),INTERVAL 90 SECOND) WHERE id=?')->execute([str_repeat('a',32),$key]);checkProgress(Sync::presentation(Sync::job($key,7))['disabled'],'active lease blocks actions independently of PHP timezone');rejectProgress(fn()=>Sync::step($key,7,clientProgress($data)));rejectProgress(fn()=>Sync::cancel($key,7));$pdo->prepare('UPDATE ENC_CREDIT_JOBS SET lease_until=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE id=?')->execute([$key]);checkProgress(Sync::step($key,7,clientProgress($data))['state']['calls']>0,'stale lease recovers');Sync::cancel($key,7);
$key=Sync::start($copy,7);$always=clientProgress($data,fn()=>['status'=>503,'headers'=>['retry-after'=>'1'],'body'=>'']);
for($i=0;$i<3;$i++){$j=Sync::job($key,7);$s=$j['state'];$s['retry_at']=0;checkpointProgress($key,$s);Sync::step($key,7,clientProgress($data,fn()=>['status'=>503,'headers'=>['retry-after'=>'1'],'body'=>'' ]));}
checkProgress(Sync::job($key,7)['status']==='failed','three real upstream errors stop');
$key=Sync::start($copy,7);Sync::step($key,7,clientProgress($data,fn()=>['status'=>404,'headers'=>[],'body'=>'']));checkProgress(Sync::job($key,7)['status']==='failed','permanent error never offered as resumable pause');
$rollback=$changed;$bad=$extra;$bad['type']='FORCE_ROLLBACK';$rollback['release/'.$release['id']]['relations'][]=$bad;$key=Sync::start($copy,7);finishProgress($key,clientProgress($rollback));$before=rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id');try{Sync::apply($key,7);throw new RuntimeException('Expected trigger');}catch(PDOException){checkProgress(rowsProgress('SELECT * FROM ENC_CREDITS ORDER BY id')===$before,'failed transaction rolls all credits back');}checkProgress(Sync::job($key,7)['status']==='ready','failed save never marked completed');Sync::cancel($key,7);
// Two tiny fixture workers exercise the actual row lock, not a simulated duplicate click.
function workersProgress(array $commands):array{
 $workers=[];foreach($commands as $args){$pipes=[];$p=proc_open(['php',__DIR__.'/progress-worker.php',...$args],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$workers[]=[$p,$pipes];}
 $out=[];foreach($workers as [$p,$pipes]){$out[]=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException('Private concurrency worker failed: '.$err);}return $out;
}
$keys=workersProgress([['start',(string)$copy],['start',(string)$copy]]);checkProgress(count(array_unique($keys))===1,'simultaneous starts reuse one checkpoint');$key=$keys[0];Sync::cancel($key,7);
workersProgress([['start',(string)$copy],['resume',$key]]);$active=rowsProgress("SELECT id FROM ENC_CREDIT_JOBS WHERE vinyl_id=? AND status IN ('collecting','ready')",[$copy]);checkProgress(count($active)===1,'resume versus start cannot create incompatible active jobs');Sync::cancel($active[0]['id'],7);
checkProgress(rowsProgress('SELECT * FROM VINYLS_TBL')===$personal&&rowsProgress('SELECT * FROM ENC_COPIES')===$personalCopies,'personal fields preserved through all cases');
// Independent ready/paused/collecting jobs for browser states (all fictional).
$states=[];
foreach(['idle','collecting','paused','failed','no-new','ready'] as $mode){$id=Collection::create(7,['title'=>'Credit progress demonstration · '.$mode,'authors'=>['Fictional band']],Normalize::release(['data'=>$release,'fetched_at'=>1],['data'=>$group,'fetched_at'=>1]));$k=$mode==='idle'?null:Sync::start($id,7);
 if($mode==='ready'||$mode==='no-new')finishProgress($k,clientProgress($changed));
 if($mode==='no-new'){Sync::apply($k,7);$k=Sync::start($id,7);finishProgress($k,clientProgress($changed));$repeat=Sync::apply($k,7);checkProgress($repeat['state']['summary']['new']===0&&$repeat['state']['summary']['updated']===0,'browser no-change fixture verified from committed summary');}
 if($mode==='paused')Sync::step($k,7,clientProgress($data,fn()=>['status'=>503,'headers'=>['retry-after'=>'60'],'body'=>'']));
 if($mode==='failed')Sync::step($k,7,clientProgress($data,fn()=>['status'=>404,'headers'=>[],'body'=>'']));
 $states[$mode]=['copy'=>$id,'job'=>$k];}
file_put_contents(getenv('VLEX_ENC_RUNTIME').'/progress-states.json',json_encode($states));
echo 'PASS '.$n." credit progress checks; isolated fixtures only, all provider responses simulated.\n";
