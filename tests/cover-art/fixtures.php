<?php
declare(strict_types=1);
// Synthetic raster and identifiers only. No external HTTP is performed.
function caa_fixture(string $url,int $limit): array
{
    $id='11111111-1111-4111-8111-111111111111';
    if(preg_match('#/release/([0-9a-f-]{36})$#D',$url,$m))$id=$m[1];
    $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAKklEQVR4nGMQaEigKWIYtWDUglELRi0YtWDUglELRi0YtWDUglELhooFACEkwC58N1x8AAAAAElFTkSuQmCC');
    if(str_ends_with($url,'.jpg')||str_ends_with($url,'.png'))return ['status'=>200,'headers'=>[],'body'=>$png];
    return ['status'=>200,'headers'=>[],'body'=>json_encode(['release'=>'https://musicbrainz.org/release/'.$id,'images'=>[['front'=>false,'approved'=>true,'image'=>'https://coverartarchive.org/release/'.$id.'/1.jpg'],['front'=>true,'approved'=>true,'thumbnails'=>['500'=>'http://coverartarchive.org/release/'.$id.'/2-500.jpg']]]])];
}
