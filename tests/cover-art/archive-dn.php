<?php
declare(strict_types=1);
// Regression for the canonical Internet Archive redirect seen in the real probe.
// No network, database, user images or credentials.
define('VLEXPLOSION_TESTING',true);
require dirname(__DIR__,2).'/app/Core/bootstrap.php';
use App\Services\CoverArtHttp as Http;
$n=0;
function check(bool $value,string $label):void{global $n;if(!$value)throw new RuntimeException('FAIL '.$label);$n++;echo "PASS $label\n";}
$id='76df3287-6cda-33eb-8e9a-044b5e15ffdd';
$caa='https://coverartarchive.org/release/'.$id;
$ia='https://archive.org/download/mbid-'.$id.'/index.json';
$dn='https://dn711103.ca.archive.org/0/items/mbid-'.$id.'/index.json';
foreach([$caa,$ia,$dn,str_replace('index.json','mbid-'.$id.'-829521842_thumb500.jpg',$dn),'https://ia801408.us.archive.org/0/items/mbid-'.$id.'/index.json','https://ia601408.eu.archive.org/0/items/mbid-'.$id.'/index.json'] as $url){Http::validateUrl($url);check(true,'allowed official endpoint');}
foreach(['https://dn711103.ca.archive.org.evil.test/0/items/mbid-'.$id.'/index.json','https://dnmalicious.ca.archive.org/0/items/mbid-'.$id.'/index.json','https://dn711103.ca.archive.org/etc/passwd','https://dn711103.ca.archive.org:443/0/items/mbid-'.$id.'/index.json','https://user@dn711103.ca.archive.org/0/items/mbid-'.$id.'/index.json','https://dn711103.ca.archive.org/0/items/mbid-'.$id.'/%2e%2e/index.json','https://dn711103.ca.archive.org/0/items/mbid-'.$id.'/index.json?url=http://127.0.0.1','https://127.0.0.1/0/items/mbid-'.$id.'/index.json'] as $url){$rejected=false;try{Http::validateUrl($url);}catch(RuntimeException){$rejected=true;}check($rejected,'hostile endpoint rejected');}
$calls=[];Http::$transport=function($url,$limit)use(&$calls,$caa,$ia,$dn){$calls[]=$url;return match($url){$caa=>['status'=>307,'headers'=>['location'=>$ia],'body'=>''],$ia=>['status'=>302,'headers'=>['location'=>$dn],'body'=>''],$dn=>['status'=>200,'headers'=>['content-type'=>'application/json'],'body'=>'{"images":[]}'],default=>throw new RuntimeException('Unexpected endpoint')};};
$r=Http::get($caa,524288,microtime(true)+2);check($r['status']===200&&$r['url']===$dn,'307/302/200 canonical chain');check($calls===[$caa,$ia,$dn],'each redirect requested once');
Http::$transport=fn()=>['status'=>302,'headers'=>['location'=>'https://localhost/secret'],'body'=>''];$rejected=false;try{Http::get($caa,524288,microtime(true)+2);}catch(RuntimeException){$rejected=true;}check($rejected,'redirect to internal host still rejected');
foreach(['127.0.0.1','::1','::ffff:127.0.0.1','10.1.1.1','169.254.169.254'] as $ip)check(!Http::publicIp($ip),'private IP still rejected');
echo "CHECKS $n; no real network or SQL\n";
