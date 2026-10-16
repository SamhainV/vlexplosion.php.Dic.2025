<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

/** Only stored musical metadata; personal copies and unpublished drafts stay scoped. */
final class EncyclopediaEntity
{
    private static function rows(string $query, array $args): array
    {
        $s=Database::pdo()->prepare($query); $s->execute($args); return $s->fetchAll();
    }
    private static function visibleMusical(string $kind,int $id,int $user): bool
    {
        $q='SELECT e.id FROM ENC_EDITIONS e JOIN ENC_ALBUMS a ON a.id=e.album_id JOIN ENC_MEDIA m ON m.edition_id=e.id JOIN ENC_TRACKS t ON t.medium_id=m.id ';
        $q.=$kind==='work'?'JOIN ENC_RECORDING_WORKS rw ON rw.recording_id=t.recording_id WHERE rw.work_id=?':'WHERE t.recording_id=?';
        return (bool)self::rows($q.' AND (a.draft_owner_id IS NULL OR a.draft_owner_id=?) LIMIT 1',[$id,$user]);
    }
    public static function find(string $kind, int $id, int $user): ?array
    {
        if ($kind==='artist') {
            $entity=self::rows('SELECT * FROM ENC_ARTISTS WHERE id=?',[$id])[0]??null;
            if (!$entity) return null;
            $context=\App\Services\EncyclopediaCreditSync::available()? ' UNION SELECT e.album_id FROM ENC_CREDITS c JOIN ENC_EDITIONS e ON e.id=c.context_edition_id WHERE c.artist_id='.(int)$id:'';
            $albums=self::rows('SELECT DISTINCT a.id,a.title FROM ENC_ALBUMS a JOIN (
                SELECT album_id FROM ENC_ALBUM_ARTISTS WHERE artist_id=?
                UNION SELECT album_id FROM ENC_CREDITS WHERE artist_id=? AND album_id IS NOT NULL
                UNION SELECT e.album_id FROM ENC_CREDITS c JOIN ENC_EDITIONS e ON e.id=c.edition_id WHERE c.artist_id=?
                UNION SELECT e.album_id FROM ENC_CREDITS c JOIN ENC_TRACKS t ON t.id=c.track_id OR t.recording_id=c.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id JOIN ENC_EDITIONS e ON e.id=m.edition_id WHERE c.artist_id=?
                UNION SELECT e.album_id FROM ENC_CREDITS c JOIN ENC_RECORDING_WORKS rw ON rw.work_id=c.work_id JOIN ENC_TRACKS t ON t.recording_id=rw.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id JOIN ENC_EDITIONS e ON e.id=m.edition_id WHERE c.artist_id=?
                '.$context.') related ON related.album_id=a.id WHERE (a.draft_owner_id IS NULL OR a.draft_owner_id=?) ORDER BY a.title',[$id,$id,$id,$id,$id,$user]);
            if (!$albums && !$entity['musicbrainz_id']) return null;
            $credits=[];$visibleAlbums=[];
            foreach($albums as $a) {
                $hasCredit=false;
                $editions=self::rows('SELECT id FROM ENC_EDITIONS WHERE album_id=?',[$a['id']]);
                foreach($editions?:[['id'=>null]] as $ed)foreach(EncyclopediaRecord::creditsFor((int)$a['id'],$ed['id']?(int)$ed['id']:null) as $c)if((int)$c['artist_id']===$id){$hasCredit=true;$c['album_title']=$a['title'];$c['related_album_id']=$a['id'];$credits[$c['id'].'-'.$a['id']]=$c;}
                if($hasCredit||self::rows('SELECT album_id FROM ENC_ALBUM_ARTISTS WHERE album_id=? AND artist_id=?',[$a['id'],$id]))$visibleAlbums[]=$a;
            }
            $albums=$visibleAlbums;
            if(!$albums&&!$entity['musicbrainz_id'])return null;
            return ['entity'=>$entity,'albums'=>$albums,'participantCredits'=>array_values($credits)];
        }
        if (in_array($kind,['label','work','recording'],true)) {
            $table=['label'=>'ENC_LABELS','work'=>'ENC_WORKS','recording'=>'ENC_RECORDINGS'][$kind];
            $entity=self::rows('SELECT * FROM '.$table.' WHERE id=?',[$id])[0]??null;if(!$entity)return null;
            $join=$kind==='label'?'JOIN ENC_EDITION_LABELS el ON el.edition_id=e.id AND el.label_id=?':($kind==='recording'?'JOIN ENC_MEDIA m ON m.edition_id=e.id JOIN ENC_TRACKS t ON t.medium_id=m.id AND t.recording_id=?':'JOIN ENC_MEDIA m ON m.edition_id=e.id JOIN ENC_TRACKS t ON t.medium_id=m.id JOIN ENC_RECORDING_WORKS rw ON rw.recording_id=t.recording_id AND rw.work_id=?');
            $related=self::rows('SELECT DISTINCT a.id,a.title,e.id AS edition_id,e.country,e.release_date FROM ENC_ALBUMS a JOIN ENC_EDITIONS e ON e.album_id=a.id '.$join.' WHERE a.draft_owner_id IS NULL OR a.draft_owner_id=? ORDER BY a.title',[$id,$user]);
            if(!$related)return null;
            $credits=[];if($kind!=='label')foreach($related as $a)foreach(EncyclopediaRecord::creditsFor((int)$a['id'],(int)$a['edition_id']) as $c)if((int)$c[$kind.'_id']===$id)$credits[$c['id']]=$c;
            $relations=$kind==='work'?self::rows('SELECT r.id,r.title FROM ENC_RECORDINGS r JOIN ENC_RECORDING_WORKS rw ON rw.recording_id=r.id WHERE rw.work_id=?',[$id]):($kind==='recording'?self::rows('SELECT w.id,w.title FROM ENC_WORKS w JOIN ENC_RECORDING_WORKS rw ON rw.work_id=w.id WHERE rw.recording_id=?',[$id]):[]);
            $other=$kind==='work'?'recording':'work';
            $relations=array_values(array_filter($relations,fn($x)=>self::visibleMusical($other,(int)$x['id'],$user)));
            return ['entity'=>$entity,'related'=>$related,'participantCredits'=>array_values($credits),'relations'=>$relations];
        }
        if ($kind==='edition') {
            $entity=self::rows('SELECT e.* FROM ENC_EDITIONS e JOIN ENC_ALBUMS a ON a.id=e.album_id WHERE e.id=? AND (a.draft_owner_id IS NULL OR a.draft_owner_id=?)',[$id,$user])[0]??null;
            if (!$entity) return null;
            $albumId=(int)$entity['album_id'];
        } elseif ($kind==='album') { $albumId=$id; }
        else return null;
        $album=self::rows('SELECT * FROM ENC_ALBUMS WHERE id=? AND (draft_owner_id IS NULL OR draft_owner_id=?)',[$albumId,$user])[0]??null;
        if (!$album) return null;
        $data=['entity'=>$kind==='album'?$album:$entity,'album'=>$album,
            'artists'=>self::rows('SELECT a.id,a.name FROM ENC_ARTISTS a JOIN ENC_ALBUM_ARTISTS aa ON aa.artist_id=a.id WHERE aa.album_id=? ORDER BY aa.position',[$albumId]),
            'copies'=>self::rows('SELECT v.Id,v.Title FROM VINYLS_TBL v JOIN ENC_COPIES c ON c.vinyl_id=v.Id AND c.owner_id=v.User_Id WHERE c.album_id=? AND c.owner_id=?'.($kind==='edition'?' AND c.edition_id=?':'').' ORDER BY v.Id', $kind==='edition'?[$albumId,$user,$id]:[$albumId,$user]),
            'editions'=>self::rows('SELECT id,country,release_date FROM ENC_EDITIONS WHERE album_id=? ORDER BY release_date,id',[$albumId])];
        if ($kind==='edition') {
            $data['tracks']=self::rows('SELECT t.*,m.position AS medium_position,m.format FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=? ORDER BY m.position,t.position',[$id]);
            $data['media']=self::rows('SELECT * FROM ENC_MEDIA WHERE edition_id=? ORDER BY position',[$id]);
            $data['credits']=EncyclopediaRecord::creditsFor($albumId,$id);
            $data['genres']=self::rows('SELECT t.name FROM ENC_TERMS t JOIN ENC_ALBUM_TERMS at ON at.term_id=t.id WHERE at.album_id=? ORDER BY t.name',[$albumId]);
            $data['edition']=$entity;
            $data['labels']=self::rows('SELECT l.id,l.name,el.catalogue_number FROM ENC_EDITION_LABELS el JOIN ENC_LABELS l ON l.id=el.label_id WHERE el.edition_id=? ORDER BY l.name',[$id]);
        }
        $data['genres']=self::rows('SELECT t.id,t.kind,t.name FROM ENC_TERMS t JOIN ENC_ALBUM_TERMS at ON at.term_id=t.id WHERE at.album_id=? ORDER BY t.name',[$albumId]);
        if($kind==='album') $data['credits']=EncyclopediaRecord::creditsFor($albumId,null);
        else {
            $data['recordings']=self::rows('SELECT DISTINCT r.* FROM ENC_RECORDINGS r JOIN ENC_TRACKS t ON t.recording_id=r.id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?',[$id]);
            $data['works']=self::rows('SELECT DISTINCT w.*,rw.recording_id FROM ENC_WORKS w JOIN ENC_RECORDING_WORKS rw ON rw.work_id=w.id JOIN ENC_TRACKS t ON t.recording_id=rw.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?',[$id]);
            $data['sources']=EncyclopediaRecord::sourcesFor($albumId,$id);
        }
        return $data;
    }
}
