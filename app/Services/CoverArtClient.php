<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Core\CoverStore;

final class CoverArtClient
{
    public static function mbid(?string $id): bool { return is_string($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',$id)===1; }
    public static function path(string $key): string
    {
        if(!preg_match('/^[0-9a-f]{64}\.png$/D',$key)) throw new \RuntimeException('Imagen no válida.');
        $p=\app_storage_path('cover-art').'/'.$key;
        if(is_link($p)) throw new \RuntimeException('Imagen no válida.');
        return $p;
    }
    public static function lock(callable $fn): mixed
    {
        $dir=\app_storage_path('cover-art'); CoverArtHttp::directory($dir);
        $f=fopen($dir.'/.lock','c'); if(!$f) throw new \RuntimeException('Almacenamiento no disponible.');
        try { if(!flock($f,LOCK_EX|LOCK_NB)) throw new \RuntimeException('Otra búsqueda está en curso. Reintenta después.'); return $fn(); }
        finally {flock($f,LOCK_UN);fclose($f);}
    }
    public static function retryAfter(?string $value): int
    {
        if($value===null) return time()+60;
        $when=ctype_digit($value)?time()+(int)$value:strtotime($value);
        return max(time()+1,$when?:time()+60);
    }
    private static function request(string $url,int $limit,float $deadline): array
    {
        // Shared across owners/processes; never sleeps inside a web request.
        $gate=\app_storage_path('cover-art').'/.next'; $next=(int)(is_file($gate)?file_get_contents($gate):0);
        if($next>time()) return ['status'=>429,'headers'=>['retry-after'=>(string)($next-time())],'body'=>''];
        file_put_contents($gate,(string)(time()+1),LOCK_EX);
        $r=CoverArtHttp::get($url,$limit,$deadline);
        if(in_array($r['status'],[429,503],true)) file_put_contents($gate,(string)self::retryAfter($r['headers']['retry-after']??null),LOCK_EX);
        return $r;
    }
    public static function fetch(string $scope,string $mbid,bool $refresh=false): array
    {
        if(!in_array($scope,['release','release-group'],true)||!self::mbid($mbid)) throw new \InvalidArgumentException('Identificador MusicBrainz no válido.');
        return self::lock(function()use($scope,$mbid,$refresh){
            $pdo=Database::pdo(); $q=$pdo->prepare('SELECT * FROM CAA_IMAGES WHERE scope=? AND mbid=?');$q->execute([$scope,$mbid]);$old=$q->fetch()?:[];
            if(($old['retry_at']??0)>time()) return ['result'=>'waiting','retry_at'=>(int)$old['retry_at']];
            if(!$refresh && $old && strtotime($old['checked_at'].' UTC')>time()-86400 && in_array($old['status'],['ready','missing'],true) && (empty($old['storage_key']) || is_file(self::path($old['storage_key'])))) return $old+['result'=>$old['status']];
            $row=['scope'=>$scope,'mbid'=>$mbid,'release_mbid'=>$old['release_mbid']??null,'original_url'=>$old['original_url']??null,'storage_key'=>$old['storage_key']??null,'sha256'=>$old['sha256']??null,'mime_type'=>$old['mime_type']??null,'retrieved_at'=>$old['retrieved_at']??null,'checked_at'=>gmdate('Y-m-d H:i:s'),'status'=>'error','retry_at'=>0];
            $result='error'; $deadline=microtime(true)+18;
            try {
                $r=self::request("https://coverartarchive.org/$scope/$mbid",524288,$deadline);
                if(in_array($r['status'],[429,503],true)) {$result='waiting';$row['retry_at']=self::retryAfter($r['headers']['retry-after']??null);}
                elseif($r['status']===404) $result='missing';
                elseif($r['status']===200) {
                    $json=json_decode($r['body'],true,32,JSON_THROW_ON_ERROR);$front=null;
                    foreach($json['images']??[] as $image) if(($image['front']??false)===true && ($image['approved']??false)===true) {$front=$image;break;}
                    if(!$front) $result='missing';
                    else {
                        $url=$front['thumbnails']['500']??$front['image']??'';
                        $source=$scope==='release'?$mbid:basename(rtrim((string)($front['release']??$json['release']??''),'/'));
                        if(!self::mbid($source)) throw new \RuntimeException('No se puede verificar la edición de origen.');
                        // The metadata request is paced separately from the binary download.
                        $img=CoverArtHttp::get($url,5*1024*1024,$deadline);
                        if(in_array($img['status'],[429,503],true)) {$result='waiting';$row['retry_at']=self::retryAfter($img['headers']['retry-after']??null); file_put_contents(\app_storage_path('cover-art').'/.next',(string)$row['retry_at'],LOCK_EX);}
                        elseif($img['status']!==200) throw new \RuntimeException('Imagen no disponible.');
                        else { $key=self::publish($img['body']); $row=array_replace($row,['release_mbid'=>$source,'original_url'=>$url,'storage_key'=>$key,'sha256'=>substr($key,0,64),'mime_type'=>'image/png','retrieved_at'=>gmdate('Y-m-d H:i:s')]);$result='ready'; }
                    }
                }
            } catch(\Throwable $e) { $result='error'; }
            $row['status']=$result;
            if($result==='error') $row['retry_at']=time()+60;
            $cols=array_keys($row);$updates=[];foreach($cols as $col)if(!in_array($col,['scope','mbid']))$updates[]="$col=VALUES($col)";
            $pdo->prepare('INSERT INTO CAA_IMAGES ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).') ON DUPLICATE KEY UPDATE '.implode(',',$updates))->execute(array_values($row));
            return $row+['result'=>$result];
        });
    }
    public static function publish(string $bytes): string
    {
        $dir=\app_storage_path('cover-art');CoverArtHttp::directory($dir);$in=tempnam($dir,'.in-');$out=tempnam($dir,'.png-');
        try {
            file_put_contents($in,$bytes);CoverStore::inspect($in);
            if(!is_executable('/usr/bin/convert')||!is_executable('/usr/bin/timeout')) throw new \RuntimeException('Verificador de imágenes no disponible.');
            $pipes=[];$proc=proc_open(['/usr/bin/timeout','6','/usr/bin/convert','-limit','memory','32MiB','-limit','map','32MiB','-limit','disk','64MiB','-limit','time','4',$in.'[0]','-strip','PNG:'.$out],[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);
            if(!is_resource($proc)||proc_close($proc)!==0) throw new \RuntimeException('No se pudo decodificar la imagen.');
            CoverStore::inspect($out);$key=hash_file('sha256',$out).'.png';$target=self::path($key);
            if(!is_file($target)) {chmod($out,0600);if(!rename($out,$target))throw new \RuntimeException('No se pudo almacenar la imagen.');}
            elseif(!hash_equals(substr($key,0,64),hash_file('sha256',$target)))throw new \RuntimeException('Imagen local dañada.');
            return $key;
        } finally {if(is_file($in))unlink($in);if(is_file($out))unlink($out);}
    }
}
