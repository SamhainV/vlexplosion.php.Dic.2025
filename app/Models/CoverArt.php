<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
use App\Core\CoverStore;
use App\Services\CoverArtClient;

final class CoverArt
{
    private static array $display=[];
    public static function available(): bool
    {
        static $yes; if($yes!==null)return $yes;
        try{return $yes=Database::pdo()->query("SHOW TABLES LIKE 'CAA_IMAGES'")->fetchColumn()!==false;}catch(\Throwable){return $yes=false;}
    }
    public static function forget(int $id): void {unset(self::$display[$id]);}
    public static function copy(int $id,int $owner): array
    {
        $q=Database::pdo()->prepare('SELECT v.Id,v.Image_Path,v.Title,c.edition_id,c.album_id,e.musicbrainz_release_id release_mbid,a.musicbrainz_group_id group_mbid FROM VINYLS_TBL v LEFT JOIN ENC_COPIES c ON c.vinyl_id=v.Id AND c.owner_id=v.User_Id LEFT JOIN ENC_EDITIONS e ON e.id=c.edition_id AND e.album_id=c.album_id LEFT JOIN ENC_ALBUMS a ON a.id=c.album_id AND a.draft_owner_id IS NULL WHERE v.Id=? AND v.User_Id=?');$q->execute([$id,$owner]);$v=$q->fetch();if(!$v)throw new \App\Core\HttpException(404,'Disco no encontrado.');
        $v['musical_release_mbid']=$v['release_mbid'];$v['visual_source']=null;
        if(empty($v['release_mbid'])&&CoverArtSource::available()){
            $review=CoverArtSource::review($id,$owner);
            if($review['active']){$v['release_mbid']=$review['active']['release_mbid'];$v['visual_source']=$review['active'];}
        }
        return $v;
    }
    public static function info(array $v): array
    {
        $p=Database::pdo()->prepare('SELECT * FROM CAA_PREFERENCES WHERE vinyl_id=?');$p->execute([$v['Id']]);$pref=$p->fetch()?:[];
        $scope= !empty($pref['group_mbid']) && $pref['group_mbid']===($v['group_mbid']??null)?'release-group':'release';
        $mbid=$scope==='release'?$v['release_mbid']:$v['group_mbid'];
        $images=[];
        foreach(['release'=>$v['release_mbid'],'release-group'=>$v['group_mbid']] as $kind=>$identifier){
            $q=Database::pdo()->prepare('SELECT * FROM CAA_IMAGES WHERE scope=? AND mbid=?');$q->execute([$kind,$identifier]);$row=$q->fetch()?:[];
            if(!empty($row['storage_key'])&&!is_file(CoverArtClient::path($row['storage_key'])))unset($row['storage_key']);
            $images[$kind]=$row;
        }
        return ['preference'=>$pref,'image'=>$images[$scope],'scope'=>$scope,'exact'=>$images['release'],'alternative'=>$images['release-group']];
    }

    public static function preload(array $items,int $owner): void
    {
        // One joined query for the current page; no provider calls on GET.
        if(!$items||!self::available())return;
        $ids=array_map(fn($v)=>(int)$v['Id'],$items);
        $visualColumn=CoverArtSource::available()?'p.visual_source_json':'NULL';
        $sql="SELECT $visualColumn visual_binding,v.Id,v.Image_Path,e.musicbrainz_release_id release_mbid,a.musicbrainz_group_id group_mbid,p.prefer_auto,p.group_mbid approved_group,ri.storage_key exact_key,gi.storage_key group_key FROM VINYLS_TBL v LEFT JOIN ENC_COPIES c ON c.vinyl_id=v.Id AND c.owner_id=v.User_Id LEFT JOIN ENC_EDITIONS e ON e.id=c.edition_id AND e.album_id=c.album_id LEFT JOIN ENC_ALBUMS a ON a.id=c.album_id LEFT JOIN CAA_PREFERENCES p ON p.vinyl_id=v.Id LEFT JOIN CAA_IMAGES ri ON ri.scope='release' AND ri.mbid=e.musicbrainz_release_id LEFT JOIN CAA_IMAGES gi ON gi.scope='release-group' AND gi.mbid=a.musicbrainz_group_id WHERE v.User_Id=? AND v.Id IN (".implode(',',array_fill(0,count($ids),'?')).')';
        $q=Database::pdo()->prepare($sql);$q->execute([$owner,...$ids]);
        foreach($q as $r){
            $visual=false;
            if(empty($r['release_mbid'])&&!empty($r['visual_binding'])){
                $resolved=self::copy((int)$r['Id'],$owner);
                $r['release_mbid']=$resolved['release_mbid'];$visual=$resolved['visual_source']!==null;
                $image=Database::pdo()->prepare("SELECT storage_key FROM CAA_IMAGES WHERE scope='release' AND mbid=?");$image->execute([$r['release_mbid']]);$r['exact_key']=$image->fetchColumn();
                // Private visual consent never authorizes a release-group alternative.
                $r['group_mbid']=null;
            }
            $group=!empty($r['approved_group'])&&$r['approved_group']===$r['group_mbid'];$key=$group?$r['group_key']:$r['exact_key'];$auto=$key&&is_file(CoverArtClient::path($key))&&(!$visual||!empty($r['prefer_auto']));$manual=!empty($r['Image_Path']);
            self::$display[(int)$r['Id']]=['url'=>$manual&&(!$r['prefer_auto']||!$auto)?CoverStore::url($r['Image_Path']):($auto?\base_url('covers/image?id='.$r['Id']):CoverStore::fallbackUrl()),'origin'=>$manual&&(!$r['prefer_auto']||!$auto)?'Carátula aportada por ti':($auto?($group?'CAA · grupo de ediciones (aproximada)':($visual?'CAA · edición confirmada para la carátula':'CAA · edición exacta')):'Sin carátula')];}
    }
    public static function display(array $v): array
    {
        $id=(int)$v['Id'];if(!isset(self::$display[$id]))self::preload([$v],\App\Core\Auth::id()??0);
        return self::$display[$id]??['url'=>CoverStore::url($v['Image_Path']??null),'origin'=>empty($v['Image_Path'])?'Sin carátula':'Carátula aportada por ti'];
    }
    public static function url(array $v): string {return self::display($v)['url'];}
    public static function select(int $id,int $owner,string $action): void
    {
        $v=self::copy($id,$owner);$group=null;$auto=0;
        if($action!=='manual'){
            $scope=$action==='group'?'release-group':'release';$mbid=$scope==='release'?$v['release_mbid']:$v['group_mbid'];
            if(!CoverArtClient::mbid($mbid))throw new \RuntimeException('Primero confirma una edición MusicBrainz.');
            $q=Database::pdo()->prepare('SELECT storage_key FROM CAA_IMAGES WHERE scope=? AND mbid=?');$q->execute([$scope,$mbid]);$key=$q->fetchColumn();
            if(!$key||!is_file(CoverArtClient::path($key)))throw new \RuntimeException('Primero descarga y comprueba esta carátula.');
            $auto=1;if($scope==='release-group')$group=$mbid;
        }
        Database::pdo()->prepare('INSERT INTO CAA_PREFERENCES(vinyl_id,prefer_auto,group_mbid) VALUES(?,?,?) ON DUPLICATE KEY UPDATE prefer_auto=VALUES(prefer_auto),group_mbid=VALUES(group_mbid)')->execute([$id,$auto,$group]);unset(self::$display[$id]);
    }
    public static function eligible(int $owner): array
    {
        $supports=CoverArtSource::available();$visual=$supports?' OR (a.draft_owner_id=v.User_Id AND p.visual_source_json IS NOT NULL)':'';
        $q=Database::pdo()->prepare("SELECT v.Id,e.musicbrainz_release_id mbid,ri.storage_key exact_key,gi.storage_key group_key FROM VINYLS_TBL v JOIN ENC_COPIES c ON c.vinyl_id=v.Id AND c.owner_id=v.User_Id JOIN ENC_EDITIONS e ON e.id=c.edition_id AND e.album_id=c.album_id JOIN ENC_ALBUMS a ON a.id=c.album_id LEFT JOIN CAA_PREFERENCES p ON p.vinyl_id=v.Id LEFT JOIN CAA_IMAGES ri ON ri.scope='release' AND ri.mbid=e.musicbrainz_release_id LEFT JOIN CAA_IMAGES gi ON gi.scope='release-group' AND gi.mbid=p.group_mbid AND p.group_mbid=a.musicbrainz_group_id WHERE v.User_Id=? AND coalesce(v.Image_Path,'')='' AND ((a.draft_owner_id IS NULL AND e.musicbrainz_release_id IS NOT NULL)".$visual.') ORDER BY v.Id LIMIT 1000');$q->execute([$owner]);
        $eligible=[];
        foreach($q->fetchAll() as $row){
            if(CoverArtClient::mbid($row['mbid'])){
                if((!empty($row['exact_key'])&&is_file(CoverArtClient::path($row['exact_key'])))||(!empty($row['group_key'])&&is_file(CoverArtClient::path($row['group_key']))))continue;
                $eligible[]=['Id'=>$row['Id'],'mbid'=>$row['mbid'],'visual_context'=>null];continue;
            }
            // Resolve consent only for private revisions; normal editions keep the joined fast path.
            $v=self::copy((int)$row['Id'],$owner);if(!CoverArtClient::mbid($v['release_mbid']))continue;$info=self::info($v);if(!empty($info['exact']['storage_key']))continue;
            $eligible[]=['Id'=>$v['Id'],'mbid'=>$v['release_mbid'],'visual_context'=>$v['visual_source']['context']??null];
        }
        return $eligible;
    }
    public static function batch(int $owner): ?array
    {
        $q=Database::pdo()->prepare('SELECT state_json FROM CAA_BATCHES WHERE owner_id=?');$q->execute([$owner]);$json=$q->fetchColumn();return $json?json_decode($json,true,32,JSON_THROW_ON_ERROR):null;
    }
    public static function batchAction(int $owner,string $action): array
    {
        $dir=\app_storage_path('cover-art');\App\Services\CoverArtHttp::directory($dir);$lock=fopen($dir.'/.owner-'.$owner,'c');
        if(!$lock)throw new \RuntimeException('Almacenamiento no disponible.');
        try{
            if(!flock($lock,LOCK_EX|LOCK_NB))throw new \RuntimeException('El lote está ocupado.');
            $s=self::batch($owner);
            if($action==='start'){
                if($s&&$s['state']!=='complete')throw new \RuntimeException('Reanuda el lote existente antes de iniciar otro.');
                $s=['state'=>'paused','queue'=>self::eligible($owner),'cursor'=>0,'reviewed'=>0,'downloaded'=>0,'missing'=>0,'skipped'=>0,'errors'=>0,'attempts'=>0,'retry_at'=>0];
            }elseif(!$s)throw new \RuntimeException('No hay lote preparado.');
            elseif($action==='pause')$s['state']='paused';
            elseif($action==='step'&&$s['state']!=='complete'){
                if(($s['retry_at']??0)>time())return $s;
                $s['state']='running';$item=$s['queue'][$s['cursor']]??null;$result='skipped';
                if($item){
                    try{$v=self::copy((int)$item['Id'],$owner);
                        if(empty($v['Image_Path'])&&$v['release_mbid']===$item['mbid']&&($v['visual_source']['context']??null)===($item['visual_context']??null)&&CoverArtClient::mbid($item['mbid'])){
                            $r=CoverArtClient::fetch('release',$item['mbid']);$result=match($r['result']){'ready'=>'downloaded','missing'=>'missing','waiting'=>'waiting',default=>'errors'};
                            $s['retry_at']=(int)($r['retry_at']??0);
                        }
                    }catch(\Throwable){$result='errors';$s['retry_at']=time()+60;}
                    if(in_array($result,['waiting','errors'],true)&&++$s['attempts']<3){$s['state']='paused';}
                    else{if($result==='waiting')$result='errors';$s[$result]++;$s['reviewed']++;$s['cursor']++;$s['attempts']=0;$s['retry_at']=0;}
                }
                $s['state']=$s['cursor']>=count($s['queue'])?'complete':'paused';
            }else throw new \RuntimeException('Acción de lote no válida.');
            if($s['cursor']>=count($s['queue']))$s['state']='complete';
            Database::pdo()->prepare('INSERT INTO CAA_BATCHES(owner_id,state_json) VALUES(?,?) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),updated_at=CURRENT_TIMESTAMP')->execute([$owner,json_encode($s,JSON_THROW_ON_ERROR)]);return $s;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
}
