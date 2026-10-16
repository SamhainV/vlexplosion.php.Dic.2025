<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\{Database,HttpException};
use App\Services\CoverArtClient;

/** Per-copy visual consent. Never changes musical identity or fetches a provider. */
final class CoverArtSource
{
    public static function available(): bool
    {
        static $available;
        if($available!==null)return $available;
        try{return $available=Database::pdo()->query("SHOW COLUMNS FROM CAA_PREFERENCES LIKE 'visual_source_json'")->fetch()!==false;}catch(\Throwable){return $available=false;}
    }
    private static function rows(string $query,array $args=[]): array
    {$q=Database::pdo()->prepare($query);$q->execute($args);return $q->fetchAll();}
    private static function description(array $album,array $edition): array
    {
        return ['album'=>$album,'edition'=>$edition,
            'labels'=>self::rows('SELECT l.id,l.name,el.catalogue_number FROM ENC_EDITION_LABELS el JOIN ENC_LABELS l ON l.id=el.label_id WHERE el.edition_id=? ORDER BY l.id,el.catalogue_number',[$edition['id']]),
            'media'=>self::rows('SELECT * FROM ENC_MEDIA WHERE edition_id=? ORDER BY position,id',[$edition['id']]),
            'tracks'=>self::rows('SELECT t.* FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=? ORDER BY m.position,t.position,t.id',[$edition['id']])];
    }
    public static function review(int $id,int $owner): array
    {
        $copy=self::rows('SELECT c.album_id,c.edition_id,c.owner_id,c.overrides_json FROM VINYLS_TBL v LEFT JOIN ENC_COPIES c ON c.vinyl_id=v.Id AND c.owner_id=v.User_Id WHERE v.Id=? AND v.User_Id=?',[$id,$owner])[0]??null;
        if(!$copy)throw new HttpException(404,'Disco no encontrado.');
        $pref=self::available()?(self::rows('SELECT visual_source_json FROM CAA_PREFERENCES WHERE vinyl_id=?',[$id])[0]??[]):[];
        $stored=json_decode($pref['visual_source_json']??'null',true);
        $result=['applicable'=>false,'current'=>null,'candidates'=>[],'active'=>null,'stored'=>$stored,'stale'=>$stored!==null];
        $a=self::rows('SELECT * FROM ENC_ALBUMS WHERE id=? AND draft_owner_id=?',[$copy['album_id'],$owner])[0]??null;
        $e=self::rows('SELECT * FROM ENC_EDITIONS WHERE id=? AND album_id=?',[$copy['edition_id'],$copy['album_id']])[0]??null;
        if(!$a||!$e||!empty($e['musicbrainz_release_id']))return $result;
        $result['applicable']=true;$current=self::description($a,$e);$result['current']=$current;
        $history=json_decode($copy['overrides_json']??'{}',true)['link_history']??[];
        // A contiguous, explicit draft ancestry is evidence of origin, never consent.
        $state=$copy['album_id'].':'.$copy['edition_id'];$origin=null;
        foreach(array_reverse(is_array($history)?$history:[]) as $event){
            if(!is_array($event)||($event['after']??null)!==$state||($event['operation']??null)!=='manual_draft'||!preg_match('/^([1-9][0-9]*):([1-9][0-9]*)$/D',(string)($event['before']??''),$m))break;
            $previousAlbum=self::rows('SELECT * FROM ENC_ALBUMS WHERE id=? AND (draft_owner_id IS NULL OR draft_owner_id=?)',[(int)$m[1],$owner])[0]??null;
            $previousEdition=self::rows('SELECT * FROM ENC_EDITIONS WHERE id=? AND album_id=?',[(int)$m[2],(int)$m[1]])[0]??null;
            if(!$previousAlbum||!$previousEdition)break;
            if($previousAlbum['draft_owner_id']===null&&CoverArtClient::mbid($previousEdition['musicbrainz_release_id'])){$origin=$previousAlbum;break;}
            $state=$event['before'];
        }
        if(!$origin)return $result;
        foreach(self::rows('SELECT * FROM ENC_EDITIONS WHERE album_id=? AND musicbrainz_release_id IS NOT NULL ORDER BY id',[$origin['id']]) as $edition){
            if(!CoverArtClient::mbid($edition['musicbrainz_release_id']))continue;
            $candidate=self::description($origin,$edition);
            $candidate['context']=hash('sha256',json_encode([$id,$owner,$copy['album_id'],$copy['edition_id'],$current,$candidate,$state],JSON_THROW_ON_ERROR));
            $result['candidates'][]=$candidate;
            if(is_array($stored)&&($stored['version']??null)===1&&(int)($stored['edition_id']??0)===(int)$edition['id']&&($stored['release_mbid']??null)===$edition['musicbrainz_release_id']&&is_string($stored['context']??null)&&hash_equals($candidate['context'],$stored['context'])){
                $result['active']=$stored;$result['stale']=false;
            }
        }
        return $result;
    }
    public static function confirm(int $id,int $owner,int $edition,string $context,bool $confirmed): void
    {
        if(!self::available())throw new HttpException(503,'La confirmación de fuente visual requiere la actualización 008.');
        if(!$confirmed||!preg_match('/^[a-f0-9]{64}$/D',$context))throw new HttpException(422,'Confirma expresamente la edición para la carátula.');
        $pdo=Database::pdo();$pdo->beginTransaction();
        try{
            self::rows('SELECT Id FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$id,$owner]);
            self::rows('SELECT vinyl_id FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=? FOR UPDATE',[$id,$owner]);
            self::rows('SELECT a.id,e.id FROM ENC_ALBUMS a JOIN ENC_EDITIONS e ON e.album_id=a.id WHERE e.id=? FOR UPDATE',[$edition]);
            $review=self::review($id,$owner);$choice=null;
            foreach($review['candidates'] as $c)if((int)$c['edition']['id']===$edition&&hash_equals($c['context'],$context))$choice=$c;
            if(!$choice)throw new HttpException(409,'La edición o la revisión cambió. Recarga y compara antes de confirmar.');
            if($review['active']&&(int)$review['active']['edition_id']===$edition){$pdo->commit();return;}
            $binding=['version'=>1,'edition_id'=>$edition,'release_mbid'=>$choice['edition']['musicbrainz_release_id'],'context'=>$context,'confirmed_at'=>gmdate('c')];
            $pdo->prepare('INSERT INTO CAA_PREFERENCES(vinyl_id,prefer_auto,visual_source_json) VALUES(?,0,?) ON DUPLICATE KEY UPDATE visual_source_json=VALUES(visual_source_json),prefer_auto=0,group_mbid=NULL')->execute([$id,json_encode($binding,JSON_THROW_ON_ERROR)]);
            $pdo->commit();CoverArt::forget($id);
        }catch(\Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
    }
    public static function remove(int $id,int $owner,bool $confirmed): void
    {
        if(!$confirmed)throw new HttpException(422,'Confirma que quieres retirar la fuente visual.');
        CoverArt::copy($id,$owner);
        if(self::available())Database::pdo()->prepare('UPDATE CAA_PREFERENCES p JOIN VINYLS_TBL v ON v.Id=p.vinyl_id SET p.visual_source_json=NULL,p.prefer_auto=0,p.group_mbid=NULL WHERE p.vinyl_id=? AND v.User_Id=?')->execute([$id,$owner]);
        CoverArt::forget($id);
    }
}
