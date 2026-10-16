<?php
declare(strict_types=1);
namespace App\Services;

/** HTTPS transport: manual redirects, pinned public DNS, bounded subprocess/output. */
final class CoverArtHttp
{
    public static $transport = null; // Injected only by isolated fixture runners.

    public static function publicIp(string $ip): bool
    {
        $packed=@inet_pton($ip); if($packed===false)return false;
        if(strlen($packed)===16) {
            // Global unicast only; reject transition/documentation mechanisms.
            if((ord($packed[0])&0xe0)!==0x20 || str_starts_with(strtolower($ip),'2002:') || str_starts_with(strtolower($ip),'2001:0:') || substr($packed,0,4)===hex2bin('20010db8') || (ord($packed[0])===0x20 && ord($packed[1])===1 && ord($packed[2])<2) || substr($packed,0,2)===hex2bin('3fff'))return false;
        }
        if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return false;
        if(strlen($packed)===4){$n=unpack('N',$packed)[1];foreach([[0,8],[0x64400000,10],[0xc0000000,24],[0xc0000200,24],[0xc6336400,24],[0xcb007100,24],[0xc6120000,15],[0xe0000000,4],[0xf0000000,4]] as [$net,$bits])if(($n & (0xffffffff << (32-$bits)))===$net)return false;}
        return true;
    }

    public static function validateUrl(string $url): array
    {
        if (strlen($url)>1024 || preg_match('/[\x00-\x20\\\\%]/', $url)) throw new \RuntimeException('Destino de imagen no permitido.');
        $p=parse_url($url);
        if (!$p || ($p['scheme']??'')!=='https' || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['query']) || isset($p['fragment'])) throw new \RuntimeException('Destino no permitido.');
        $host=strtolower($p['host']??''); $path=$p['path']??'';
        if (str_contains($path,'..')) throw new \RuntimeException('Ruta no permitida.');
        $uuid='[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
        $allowed=($host==='coverartarchive.org' && preg_match('#^/(release|release-group)/'.$uuid.'(?:/[0-9]+(?:-(?:250|500|1200))?\.(?:jpg|png|webp))?/?$#D',$path))
          || (($host==='archive.org' || preg_match('/^(?:ia[0-9]+\.(?:us|eu)|dn[0-9]+\.ca)\.archive\.org$/D',$host)) && preg_match('#^/(?:download|[0-9]+/items)/mbid-'.$uuid.'/(?:[a-zA-Z0-9_.-]+\.(?:jpg|png|webp)|index\.json)$#D',$path));
        if (!$allowed) throw new \RuntimeException('Origen de carátula no permitido.');
        return [$host,$path];
    }

    public static function get(string $url, int $limit, float $deadline): array
    {
        for($hop=0;$hop<5;$hop++) {
            // Legacy CAA metadata can contain HTTP URLs. Upgrade; never use HTTP.
            if(str_starts_with($url,'http://'))$url='https://'.substr($url,7);
            [$host]=self::validateUrl($url);
            if (microtime(true)>$deadline) throw new \RuntimeException('Tiempo de descarga agotado.');
            if (self::$transport !== null && defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING) {
                $r=(self::$transport)($url,$limit);
            } else {
                $dnsPipes=[]; $dns=proc_open(['/usr/bin/timeout',(string)max(0.001,min(3,$deadline-microtime(true))),'/usr/bin/getent','ahosts',$host],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['file','/dev/null','w']],$dnsPipes);
                if(!is_resource($dns))throw new \RuntimeException('DNS no disponible.');
                $answers=stream_get_contents($dnsPipes[1],65536);fclose($dnsPipes[1]);$dnsExit=proc_close($dns);$ips=[];
                if($dnsExit!==0)throw new \RuntimeException('DNS no disponible.');
                foreach(explode("\n",trim($answers)) as $line){$ip=preg_split('/\s+/',trim($line))[0];if(!self::publicIp($ip))throw new \RuntimeException('DNS no público.');$ips[]=$ip;}
                if(microtime(true)>=$deadline)throw new \RuntimeException('Tiempo de descarga agotado.');
                if(!$ips) throw new \RuntimeException('No se pudo resolver el proveedor.');
                $dir=\app_storage_path('cover-art'); self::directory($dir);
                $headers=tempnam($dir,'.headers-'); $body=tempnam($dir,'.body-');
                try {
                    $ip=$ips[0]; if(str_contains($ip,':')) $ip='['.$ip.']';
                    $args=['/usr/bin/curl','--silent','--show-error','--proto','=https','--noproxy','*','--resolve',"$host:443:$ip",'--connect-timeout','3','--max-time',(string)max(0.001,min(10,$deadline-microtime(true))),'--max-filesize',(string)$limit,'--user-agent','VLexPlosion/0.4 (local personal collection; Cover Art Archive)','--header','Accept: application/json, image/*;q=0.9','--dump-header',$headers,'--output',$body,'--write-out','%{http_code}',$url];
                    $pipes=[]; $proc=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['file','/dev/null','a']],$pipes);
                    if(!is_resource($proc)) throw new \RuntimeException('Transporte HTTPS no disponible.');
                    fclose($pipes[0]); $code=stream_get_contents($pipes[1],16); fclose($pipes[1]); $exit=proc_close($proc);
                    if($exit!==0 || filesize($body)>$limit) throw new \RuntimeException('Descarga incompleta o demasiado grande.');
                    $h=[]; foreach(explode("\n",file_get_contents($headers)) as $line) if(str_contains($line,':')) {[$k,$v]=explode(':',$line,2);$h[strtolower(trim($k))]=trim($v);}
                    $r=['status'=>(int)$code,'headers'=>$h,'body'=>file_get_contents($body)];
                } finally {if(is_file($headers))unlink($headers);if(is_file($body))unlink($body);}
            }
            if(strlen($r['body']??'')>$limit) throw new \RuntimeException('Respuesta demasiado grande.');
            if(in_array($r['status'],[301,302,303,307,308],true)) { $url=$r['headers']['location']??''; continue; }
            $r['url']=$url; return $r;
        }
        throw new \RuntimeException('Demasiadas redirecciones.');
    }

    public static function directory(string $path): void
    {
        if(!is_dir($path) && !mkdir($path,0700,true) && !is_dir($path)) throw new \RuntimeException('Almacenamiento no disponible.');
        if(is_link($path)) throw new \RuntimeException('Almacenamiento no válido.');
    }
}
