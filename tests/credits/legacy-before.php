<?php
declare(strict_types=1);
// Execute the actual approved stable writer/normalizer, loaded only from this test runtime.
$run=getenv('VLEX_ENC_RUNTIME');
eval(substr(file_get_contents($run.'/stable-MusicBrainzNormalizer.php'),5));
// This test file has the same directory depth as app/Services, preserving __DIR__ isolation checks.
eval(substr(file_get_contents($run.'/stable-EncyclopediaCollection.php'),5));
require dirname(__DIR__).'/encyclopedia/connect.php';
$_ENV['VLEX_ENC_ENABLED']='1';
use App\Services\{EncyclopediaCollection,EncyclopediaManual};
use App\Services\Metadata\MusicBrainzNormalizer;
use App\Models\EncyclopediaRecord;
$r=json_decode(file_get_contents(__DIR__.'/reference/release-e42cf164-56d6-470d-95f1-b7ca8e7d888d.json'),true,128,JSON_THROW_ON_ERROR);
$g=json_decode(file_get_contents(__DIR__.'/reference/release-group-71f1482e-e63f-3b2c-811b-939f62708f2a.json'),true,128,JSON_THROW_ON_ERROR);
$m=MusicBrainzNormalizer::release($r,$g);
$id=EncyclopediaCollection::create(7,['title'=>'Fictional owner copy of Violator','authors'=>['Personal artist'],'producer'=>'Personal producer','release_date'=>'1993','is_favorite'=>'1'], $m,null,['notes'=>'PRIVATE FICTIONAL LEGACY NOTE','country'=>'PERSONAL COUNTRY']);
$record=EncyclopediaRecord::forCopy($id,7);
$c=array_values(array_filter($record['credits'],fn($c)=>$c['role']==='producer'))[0];
$anchor=null;foreach(['album','edition','recording','work','track'] as $kind)if(!empty($c[$kind.'_id'])){$anchor=$kind;break;}
$_ENV['VLEX_ENC_SHARED_EDITORS']='7';
EncyclopediaManual::save($id,7,'shared','credit',['entity_id'=>(string)$c['id'],'target_kind'=>$anchor,'target_id'=>(string)$c[$anchor.'_id'],'artist_id'=>(string)$c['artist_id'],'role'=>'Legacy manual production correction','instrument'=>'Legacy protected instrument'],EncyclopediaManual::revision((int)$record['album']['id']),bin2hex(random_bytes(32)));
unset($_ENV['VLEX_ENC_SHARED_EDITORS']);
$snapshot=['copy'=>$id,'manual_id'=>$c['id'],'tables'=>[],'columns'=>$pdo->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetchAll()];
foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table){$rows=$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll();usort($rows,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));$snapshot['tables'][$table]=$rows;}
file_put_contents($run.'/legacy-before.json',json_encode($snapshot,JSON_THROW_ON_ERROR));
echo "PASS stable b0d935e actually imported the public release under schema 005; manual correction prepared.\n";
