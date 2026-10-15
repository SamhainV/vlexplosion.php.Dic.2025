<?php
// Optional public, GET-only demonstration capture. No application bootstrap, DB or sessions.
if(PHP_SAPI!=='cli')exit(1);
spl_autoload_register(static function($c){if(str_starts_with($c,'App\\'))require dirname(__DIR__).'/app/'.str_replace('\\','/',substr($c,4)).'.php';});
$root=dirname(__DIR__);$client=new App\Services\Metadata\MusicBrainzClient($root.'/tests/runtime/credit-reference-cache','VLexPlosion/credits-dev (https://github.com/SamhainV/vlexplosion.php.Dic.2025)');
$release=$argv[1]??'e42cf164-56d6-470d-95f1-b7ca8e7d888d';$queue=['release/'.$release];$responses=[];$calls=0;
while($queue){
 if(++$calls>80)throw new RuntimeException('Reference request budget reached');
 $k=array_shift($queue);[$kind,$id]=explode('/',$k,2);if(isset($responses[$k]))continue;
 $saved=$root.'/tests/credits/reference/'.str_replace('/','-',$k).'.json';
 if(is_file($saved))$r=json_decode(file_get_contents($saved),true,128,JSON_THROW_ON_ERROR);
 else {usleep(1300000);try{$r=$client->creditLookup($kind,$id);}catch(App\Services\Metadata\MetadataException $e){echo 'PAUSED: '.$e->reason.'; Retry-After '.$e->retryAfter.' seconds. Saved captures retained; rerun explicitly later.'.PHP_EOL;exit(2);}}
 $responses[$k]=$r;
 file_put_contents($root.'/tests/credits/reference/'.str_replace('/','-',$k).'.json',json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
 echo $kind.' '.$id.(is_file($saved)?' saved':' captured').PHP_EOL;
 $d=$r['data'];$add=[];
 if($kind==='release'){
  $add[]='release-group/'.$d['release-group']['id'];
  foreach($d['media']??[] as $m)foreach($m['tracks']??[] as $t)if(isset($t['recording']['id']))$add[]='recording/'.$t['recording']['id'];
 }
 if(in_array($kind,['release','release-group','recording','work'],true))foreach($d['relations']??[] as $rel){
  if($kind==='recording'&&($rel['type']??'')==='performance'&&isset($rel['work']['id']))$add[]='work/'.$rel['work']['id'];
  foreach(['place','label'] as $leaf)if(isset($rel[$leaf]['id']))$add[]=$leaf.'/'.$rel[$leaf]['id'];
 }
 foreach($add as $next)if(!isset($responses[$next])&&!in_array($next,$queue,true))$queue[]=$next;
}
$indexFile=$root.'/tests/credits/reference/index.json';$index=is_file($indexFile)?json_decode(file_get_contents($indexFile),true):[];$index['releases'][$release]=['entities'=>count($responses),'complete'=>true,'captured_at'=>gmdate('c'),'source'=>'https://musicbrainz.org/release/'.$release];file_put_contents($indexFile,json_encode($index,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo 'PUBLIC REFERENCE COMPLETE: '.count($responses).' entities, GET only.'.PHP_EOL;
