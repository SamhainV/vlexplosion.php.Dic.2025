<?php
declare(strict_types=1);
namespace App\Core;
use App\Services\Recognition\{Provider,ChatGPTLocal,FixtureProvider};
final class AiFeature {
 public static function enabled():bool{return defined('VLEXPLOSION_TESTING')&&VLEXPLOSION_TESTING===true&&getenv('VLEX_AI_ENABLED')==='1';}
 public static function requireEnabled():void{Auth::requireLogin();if(!self::enabled())throw new HttpException(503,'La función IA está desactivada hasta revisar la migración y configurar un entorno autorizado.');}
 public static function provider():Provider{return getenv('VLEX_AI_PROVIDER')==='fixture'&&defined('VLEXPLOSION_TESTING')&&VLEXPLOSION_TESTING===true?new FixtureProvider():new ChatGPTLocal();}
 public static function limit():void{
  $root=app_storage_path('ai-usage');if(!is_dir($root))mkdir($root,0700,true);$f=fopen($root.'/'.Auth::id().'.json','c+');if(!$f||!flock($f,LOCK_EX))throw new HttpException(503);try{$s=json_decode(stream_get_contents($f),true)?:[];$now=time();$s=array_values(array_filter($s,fn($v)=>$v>$now-3600));$max=defined('VLEXPLOSION_TESTING')&&getenv('VLEX_AI_PROVIDER')==='fixture'?100:10;if(count($s)>=$max||($max===10&&$s&&$now-end($s)<15))throw new HttpException(429,'Límite de reconocimiento alcanzado. Conserva la foto y reintenta más tarde.');$s[]=$now;rewind($f);ftruncate($f,0);fwrite($f,json_encode($s));}finally{fclose($f);}
 }
}
