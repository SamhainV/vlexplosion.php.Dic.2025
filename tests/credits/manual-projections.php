<?php
declare(strict_types=1);
require dirname(__DIR__).'/encyclopedia/connect.php';require __DIR__.'/fixtures.php';
use App\Services\{EncyclopediaCollection as Collection,EncyclopediaCreditSync as Sync,EncyclopediaManual as Manual};
use App\Services\Metadata\{MusicBrainzClient,MusicBrainzNormalizer};
use App\Models\EncyclopediaRecord as Record;
use App\Core\HttpException;
$_ENV['VLEX_ENC_ENABLED']='1';$checks=0;
function checkProjection(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException('FAIL projection: '.$label);$checks++;}
function queryProjection(string $sql,array $args=[]):array{global $pdo;$s=$pdo->prepare($sql);$s->execute($args);return $s->fetchAll();}
function rejectProjection(callable $f):void{try{$f();}catch(HttpException $e){checkProjection($e->status===409,'real duplicate rejected');return;}throw new RuntimeException('Duplicate accepted');}
$data=credit_fixtures();$r=$data['release/'.encyclopedia_fixture_id(2)];$g=$data['release-group/'.encyclopedia_fixture_id(1)];
$id=Collection::create(7,['title'=>'Projection regression','authors'=>['Personal artist'],'producer'=>null,'release_date'=>1984],MusicBrainzNormalizer::release(['data'=>$r,'fetched_at'=>1],['data'=>$g,'fetched_at'=>1]),null,['notes'=>'FICTIONAL PRIVATE NOTE']);
$personal=queryProjection('SELECT * FROM VINYLS_TBL WHERE Id=?',[$id]);$copy=queryProjection('SELECT * FROM ENC_COPIES WHERE vinyl_id=?',[$id]);
$record=Record::forCopy($id,7);$legacy=array_values(array_filter($record['credits'],fn($c)=>$c['role']==='engineer'))[0];$legacyRaw=queryProjection('SELECT * FROM ENC_CREDITS WHERE id=?',[$legacy['id']])[0];
$clock=(float)time();$client=new MusicBrainzClient(getenv('VLEX_ENC_RUNTIME').'/projection-cache','VLexPlosion/regression (https://example.invalid)',function($url)use($data){$key=substr(parse_url($url,PHP_URL_PATH),6);return ['status'=>isset($data[$key])?200:404,'headers'=>[],'body'=>json_encode($data[$key]??[],JSON_THROW_ON_ERROR)];},function()use(&$clock){return $clock;},function($s)use(&$clock){$clock+=$s;});
$collect=function($key)use($client){for($i=0;$i<12;$i++){$j=Sync::job($key,7);if($j['status']!=='collecting')return $j;Sync::step($key,7,$client);}throw new RuntimeException('Unbounded projection fixture');};
$key=Sync::start($id,7);$collect($key);Sync::apply($key,7);$record=Record::forCopy($id,7);
$rich=array_values(array_filter($record['credits'],fn($c)=>$c['role']==='engineer'&&!empty($c['relation_key'])))[0];
checkProjection(!in_array($legacy['id'],array_column($record['credits'],'id'),true),'legacy projection hidden');
checkProjection($rich['artist_id']===$legacy['artist_id']&&$rich['role']===$legacy['role']&&$rich['instrument']===$legacy['instrument']&&$rich['credited_as']===$legacy['credited_as'],'exact old false-duplicate tuple reproduced');
$total=(int)$pdo->query('SELECT COUNT(*) FROM ENC_CREDITS')->fetchColumn();
$input=['entity_id'=>(string)$rich['id'],'target_kind'=>'edition','target_id'=>(string)$rich['edition_id'],'artist_id'=>(string)$rich['artist_id'],'role'=>$rich['role'],'instrument'=>$rich['instrument']??'','credited_as'=>$rich['credited_as']??''];
$_ENV['VLEX_ENC_SHARED_EDITORS']='7';$duplicateImported=$input;unset($duplicateImported['entity_id']);rejectProjection(fn()=>Manual::save($id,7,'shared','credit',$duplicateImported,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32))));$revision=Manual::revision((int)$record['album']['id']);$saveKey=bin2hex(random_bytes(32));
Manual::save($id,7,'shared','credit',$input,$revision,$saveKey);
checkProjection((int)$pdo->query('SELECT COUNT(*) FROM ENC_CREDITS')->fetchColumn()===$total,'same-value confirmation creates no credit');
checkProjection(queryProjection('SELECT * FROM ENC_CREDITS WHERE id=?',[$legacy['id']])[0]===$legacyRaw,'hidden projection byte-identical');
$manual=queryProjection('SELECT * FROM ENC_CREDITS WHERE id=?',[$rich['id']])[0];
checkProjection($manual['relation_key']===$rich['relation_key']&&$manual['relation_json']===$rich['relation_json'],'original relationship provenance retained');
checkProjection(queryProjection('SELECT provider FROM ENC_SOURCES WHERE id=?',[$manual['source_id']])[0]['provider']==='manual','explicit confirmation becomes manual');
$history=(int)$pdo->query('SELECT COUNT(*) FROM ENC_ASSERTIONS')->fetchColumn();Manual::save($id,7,'shared','credit',$input,$revision,$saveKey);
checkProjection((int)$pdo->query('SELECT COUNT(*) FROM ENC_ASSERTIONS')->fetchColumn()===$history,'same confirmation key idempotent');
$create=$input;unset($create['entity_id']);rejectProjection(fn()=>Manual::save($id,7,'shared','credit',$create,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32))));
$distinct=$create;$distinct['role']='Different genuine credit';Manual::save($id,7,'shared','credit',$distinct,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32)));
$other=queryProjection("SELECT id FROM ENC_CREDITS WHERE role='Different genuine credit'")[0]['id'];$edit=$input;$edit['entity_id']=(string)$other;
rejectProjection(fn()=>Manual::save($id,7,'shared','credit',$edit,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32))));
$body=$data['release/'.encyclopedia_fixture_id(2)];$org=array_values(array_filter($record['credits'],fn($c)=>$c['role']==='manufactured'&&!empty($c['relation_key'])))[0];
$orgInput=['entity_id'=>(string)$org['id'],'target_kind'=>'edition','target_id'=>(string)$org['edition_id'],'organisation'=>$org['organisation'],'role'=>$org['role'],'credited_as'=>$org['credited_as']??''];
Manual::save($id,7,'shared','credit',$orgInput,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32)));checkProjection(true,'organisation same-value confirmation');unset($orgInput['entity_id']);rejectProjection(fn()=>Manual::save($id,7,'shared','credit',$orgInput,Manual::revision((int)$record['album']['id']),bin2hex(random_bytes(32))));
unset($_ENV['VLEX_ENC_SHARED_EDITORS']);$key=Sync::start($id,7);$collect($key);$updated=Sync::apply($key,7);
checkProjection($updated['state']['protected']===2,'manual person and company protected');checkProjection(queryProjection('SELECT * FROM ENC_CREDITS WHERE id=?',[$rich['id']])[0]===$manual,'refresh does not overwrite manual confirmation');
checkProjection(queryProjection('SELECT * FROM VINYLS_TBL WHERE Id=?',[$id])===$personal&&queryProjection('SELECT * FROM ENC_COPIES WHERE vinyl_id=?',[$id])===$copy,'all personal fields preserved');
checkProjection(!Manual::sharedEditor(7),'no shared editing permission remains');
// An edition which has not been refreshed still exposes its legacy credits: they MUST block duplicates.
$otherData=credit_fixtures(true);$otherRelease=$otherData['release/'.encyclopedia_fixture_id(22)];$otherId=Collection::create(7,['title'=>'Unsynchronized edition regression','authors'=>['Personal artist'],'producer'=>null,'release_date'=>1984],MusicBrainzNormalizer::release(['data'=>$otherRelease,'fetched_at'=>2],['data'=>$g,'fetched_at'=>2]));$otherRecord=Record::forCopy($otherId,7);$visibleLegacy=array_values(array_filter($otherRecord['credits'],fn($c)=>$c['role']==='engineer'))[0];
$_ENV['VLEX_ENC_SHARED_EDITORS']='7';$vinput=['target_kind'=>'edition','target_id'=>(string)$visibleLegacy['edition_id'],'artist_id'=>(string)$visibleLegacy['artist_id'],'role'=>$visibleLegacy['role'],'credited_as'=>$visibleLegacy['credited_as']??''];
rejectProjection(fn()=>Manual::save($otherId,7,'shared','credit',$vinput,Manual::revision((int)$otherRecord['album']['id']),bin2hex(random_bytes(32))));
$vinput['entity_id']=(string)$visibleLegacy['id'];$prior=(int)$pdo->query('SELECT COUNT(*) FROM ENC_CREDITS')->fetchColumn();Manual::save($otherId,7,'shared','credit',$vinput,Manual::revision((int)$otherRecord['album']['id']),bin2hex(random_bytes(32)));checkProjection((int)$pdo->query('SELECT COUNT(*) FROM ENC_CREDITS')->fetchColumn()===$prior,'existing unsynchronized editor confirmation remains compatible');unset($_ENV['VLEX_ENC_SHARED_EDITORS']);
echo 'PASS '.$checks." specific projection/manual regression checks; fixtures only, no network.\n";
