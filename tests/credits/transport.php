<?php
declare(strict_types=1);
require_once __DIR__.'/fixtures.php';
/** Only enabled by the socket-isolated test bootstrap. No network transport. */
function credit_fixture_response(string $url,string $ua): array
{
    $path=substr(parse_url($url,PHP_URL_PATH),6);
    parse_str(parse_url($url,PHP_URL_QUERY)??'',$query);
    if(str_contains($query['inc']??'','area-rels')||preg_match('#^(place|label)/#',$path)) {
        file_put_contents(getenv('VLEX_ENC_RUNTIME').'/credit-transport-calls',"GET fixture\n",FILE_APPEND|LOCK_EX);
        $data=credit_fixtures();
        foreach(glob(__DIR__.'/reference/*.json') as $file){
            $r=json_decode(file_get_contents($file),true,128,JSON_THROW_ON_ERROR);
            if(isset($r['data']))$data[$r['entity_type'].'/'.$r['data']['id']]=$r['data'];
        }
        return ['status'=>isset($data[$path])?200:404,'headers'=>[],'body'=>json_encode($data[$path]??[],JSON_THROW_ON_ERROR)];
    }
    return encyclopedia_fixture_response($url,$ua);
}
