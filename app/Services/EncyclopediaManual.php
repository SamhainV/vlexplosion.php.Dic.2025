<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,HttpException,Input};
use App\Models\EncyclopediaRecord;
use App\Services\Metadata\MusicBrainzNormalizer;
use PDO;

/** Structured editing. Default scope is a private musical draft, never a shared import. */
final class EncyclopediaManual
{
    public static function identityFingerprint(array $entity): string
    { return hash('sha256',json_encode(['id'=>(int)$entity['id'],'name'=>$entity['name'],'kind'=>$entity['kind']??null],JSON_THROW_ON_ERROR)); }
    public static function requireSchema(): void
    {
        if (!Database::pdo()->query("SHOW COLUMNS FROM ENC_CREDITS LIKE 'instrument'")->fetch()) throw new HttpException(503,'La edición musical requiere la migración incremental 005, pendiente de autorización en producción.');
    }
    public static function sharedEditor(int $user): bool
    {
        $configured=$_ENV['VLEX_ENC_SHARED_EDITORS']??getenv('VLEX_ENC_SHARED_EDITORS')?:'';
        return in_array((string)$user,array_filter(array_map('trim',explode(',',(string)$configured))),true);
    }
    public static function revision(int $album): int
    { return (int)self::row(Database::pdo(),'SELECT COALESCE(MAX(id),0) AS revision FROM ENC_ASSERTIONS WHERE album_id=? OR edition_id IN (SELECT id FROM ENC_EDITIONS WHERE album_id=?)',[$album,$album])['revision']; }
    public static function save(int $vinyl,int $user,string $scope,string $operation,array $input,int $revision,string $key,int $attempt=0): int
    {
        $pdo=Database::pdo();EncyclopediaCollection::isolation($pdo);self::requireSchema();
        if (!in_array($scope,['private','shared'],true) || !preg_match('/^[a-f0-9]{64}$/D',$key)) throw new HttpException(422,'Solicitud inválida.');
        if (in_array($operation,['artist_identity','label_identity'],true) && $scope!=='shared') throw new HttpException(403,'Una identidad requiere alcance compartido explícito y permiso editorial.');
        if ($scope==='shared'&&!self::sharedEditor($user)) throw new HttpException(403,'No tienes permiso explícito para editar metadatos compartidos.');
        $hash=hash('sha256',json_encode(['manual',$vinyl,$scope,$operation,$input,$revision],JSON_THROW_ON_ERROR));
        $pdo->beginTransaction();
        try {
            self::exec($pdo,'INSERT INTO AI_SAVE_KEYS_TBL(User_Id,Request_Key,Payload_Hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE Request_Key=Request_Key',[$user,$key,$hash]);
            $request=self::row($pdo,'SELECT * FROM AI_SAVE_KEYS_TBL WHERE User_Id=? AND Request_Key=? FOR UPDATE',[$user,$key]);
            if (!hash_equals($request['Payload_Hash'],$hash)) throw new HttpException(409,'Confirmación utilizada con otros datos.');
            $v=self::row($pdo,'SELECT * FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$vinyl,$user]);if (!$v) throw new HttpException(404);
            if ($request['Completed']) {$pdo->commit();return $vinyl;}
            $copy=self::row($pdo,'SELECT * FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=? FOR UPDATE',[$vinyl,$user]);
            $album=$copy?self::row($pdo,'SELECT * FROM ENC_ALBUMS WHERE id=? FOR UPDATE',[$copy['album_id']]):null;
            if ($album && $album['draft_owner_id']!==null && (int)$album['draft_owner_id']!==$user) throw new HttpException(403);
            if (($album?self::revision((int)$album['id']):0)!==$revision) throw new HttpException(409,'La ficha musical cambió. Recarga y revisa tus cambios.');
            if ($operation==='prepare') {
                if ($scope!=='private') throw new HttpException(422,'La preparación solo crea un borrador privado.');
                if ($album && (int)$album['draft_owner_id']===$user && $copy['edition_id']) throw new HttpException(409,'Ya existe un borrador privado editable.');
                $thisRecord=EncyclopediaRecord::forCopy($vinyl,$user);
                [$aid,$eid]=self::fork($pdo,$thisRecord,$user);
                $personal=json_decode($copy['overrides_json']??'{}',true)?:[];
                $personal['link_history'][]=['operation'=>'manual_draft','before'=>EncyclopediaLink::state($copy?:null),'after'=>$aid.':'.$eid,'user'=>$user,'at'=>gmdate('c')];
                if ($copy) self::exec($pdo,'UPDATE ENC_COPIES SET album_id=?,edition_id=?,overrides_json=? WHERE vinyl_id=? AND owner_id=?',[$aid,$eid,json_encode($personal,JSON_THROW_ON_ERROR),$vinyl,$user]);
                else self::exec($pdo,'INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id,overrides_json,added_at) VALUES(?,?,?,?,?,NOW())',[$vinyl,$user,$aid,$eid,json_encode($personal,JSON_THROW_ON_ERROR)]);
                $before=null;$after=['draft_album'=>$aid,'draft_edition'=>$eid,'from_album'=>$album['id']??null];
            } else {
                if (!$album || !$copy['edition_id']) throw new HttpException(422,'Prepara primero una ficha musical manual.');
                $aid=(int)$album['id'];$eid=(int)$copy['edition_id'];
                if ($scope==='private' && ($album['draft_owner_id']===null || (int)$album['draft_owner_id']!==$user)) throw new HttpException(403,'Una edición compartida requiere permiso editorial o un borrador privado explícito.');
                if ($scope==='private' && (int)self::row($pdo,'SELECT COUNT(*) AS n FROM ENC_COPIES WHERE album_id=?',[$aid])['n']>1) throw new HttpException(403);
                [$before,$after]=self::change($pdo,$aid,$eid,$operation,$input,$user);
            }
            self::exec($pdo,"INSERT INTO ENC_SOURCES(provider,entity_type,external_id,fetched_at) VALUES('manual','revision',?,NOW())",[$key]);$source=(int)$pdo->lastInsertId();
            if ($operation==='credit') self::exec($pdo,'UPDATE ENC_CREDITS SET source_id=? WHERE id=?',[$source,$after['id']]);
            self::exec($pdo,"INSERT INTO ENC_ASSERTIONS(album_id,field_name,value_json,source_id,author_user_id,status,manually_locked) VALUES(?,?,?,?,?,'accepted',1)",[$aid,'manual.'.$operation,json_encode(['before'=>$before,'after'=>$after],JSON_THROW_ON_ERROR),$source,$user]);
            self::exec($pdo,'UPDATE AI_SAVE_KEYS_TBL SET Vinyl_Id=?,Completed=1 WHERE User_Id=? AND Request_Key=?',[$vinyl,$user,$key]);
            $pdo->commit();return $vinyl;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction())$pdo->rollBack();
            if ($error instanceof \PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true)&&$attempt<2) return self::save($vinyl,$user,$scope,$operation,$input,$revision,$key,$attempt+1);
            throw $error;
        }
    }
    private static function fork(PDO $pdo,array $r,int $user): array
    {
        $a=$r['album'];self::exec($pdo,'INSERT INTO ENC_ALBUMS(title,original_date,release_type,language,description,draft_owner_id) VALUES(?,?,?,?,?,?)',[$a['title'],$a['original_date']??null,$a['release_type']??null,$a['language']??null,$a['description']??null,$user]);$aid=(int)$pdo->lastInsertId();
        foreach($r['artists'] as $pos=>$artist)self::exec($pdo,'INSERT INTO ENC_ALBUM_ARTISTS(album_id,artist_id,position) VALUES(?,?,?)',[$aid,$artist['id'],$pos+1]);
        if (!empty($a['id'])) self::exec($pdo,'INSERT INTO ENC_ALBUM_TERMS SELECT ?,term_id FROM ENC_ALBUM_TERMS WHERE album_id=?',[$aid,$a['id']]);
        $e=$r['edition']??[];$fields=['country','manufacture_country','release_date','barcode','packaging','edition_type','remastered','pressing_plant'];
        self::exec($pdo,'INSERT INTO ENC_EDITIONS(album_id,'.implode(',',$fields).') VALUES('.implode(',',array_fill(0,9,'?')).')',[$aid,...array_map(fn($k)=>$e[$k]??null,$fields)]);$eid=(int)$pdo->lastInsertId();
        if (!empty($e['id'])) self::exec($pdo,'INSERT INTO ENC_EDITION_LABELS SELECT ?,label_id,catalogue_number FROM ENC_EDITION_LABELS WHERE edition_id=?',[$eid,$e['id']]);
        $maps=['album_id'=>[$a['id']??0=>$aid],'edition_id'=>[$e['id']??0=>$eid],'track_id'=>[],'recording_id'=>[],'work_id'=>[]];
        foreach($r['media'] as $m) {
            self::exec($pdo,'INSERT INTO ENC_MEDIA(edition_id,position,format,rpm,diameter_inches,color,weight_grams) VALUES(?,?,?,?,?,?,?)',[$eid,$m['position'],$m['format'],$m['rpm'],$m['diameter_inches'],$m['color'],$m['weight_grams']]);$mid=(int)$pdo->lastInsertId();
            foreach($r['tracks'] as $t)if ((int)$t['medium_id']===(int)$m['id']) {
                $rid=null;
                if ($t['recording_id']) {
                    $old=(int)$t['recording_id'];
                    if (!isset($maps['recording_id'][$old])) {
                        $rec=self::row($pdo,'SELECT * FROM ENC_RECORDINGS WHERE id=?',[$old]);self::exec($pdo,'INSERT INTO ENC_RECORDINGS(title,duration_ms) VALUES(?,?)',[$rec['title'],$rec['duration_ms']]);$maps['recording_id'][$old]=(int)$pdo->lastInsertId();
                        foreach(self::rows($pdo,'SELECT w.* FROM ENC_WORKS w JOIN ENC_RECORDING_WORKS rw ON rw.work_id=w.id WHERE rw.recording_id=?',[$old]) as $w) {
                            if (!isset($maps['work_id'][$w['id']])){self::exec($pdo,'INSERT INTO ENC_WORKS(title) VALUES(?)',[$w['title']]);$maps['work_id'][$w['id']]=(int)$pdo->lastInsertId();}
                            self::exec($pdo,'INSERT INTO ENC_RECORDING_WORKS VALUES(?,?)',[$maps['recording_id'][$old],$maps['work_id'][$w['id']]]);
                        }
                    }
                    $rid=$maps['recording_id'][$old];
                }
                self::exec($pdo,'INSERT INTO ENC_TRACKS(medium_id,position,printed_number,side,title,duration_ms,recording_id) VALUES(?,?,?,?,?,?,?)',[$mid,$t['position'],$t['printed_number'],$t['side'],$t['title'],$t['duration_ms'],$rid]);$maps['track_id'][$t['id']]=(int)$pdo->lastInsertId();
            }
        }
        foreach($r['credits'] as $c)foreach($maps as $column=>$map)if (!empty($c[$column])&&isset($map[$c[$column]]))self::exec($pdo,"INSERT INTO ENC_CREDITS($column,artist_id,organisation,role,credited_as,source_id,instrument) VALUES(?,?,?,?,?,?,?)",[$map[$c[$column]],$c['artist_id'],$c['organisation'],$c['role'],$c['credited_as'],$c['source_id'],$c['instrument']??null]);
        if (EncyclopediaCreditSync::available($pdo)) {
            foreach($r['credits'] as $c)foreach($maps as $column=>$map)if(!empty($c[$column])&&isset($map[$c[$column]])&&!empty($c['relation_json']))self::exec($pdo,'UPDATE ENC_CREDITS SET relation_json=? WHERE '.$column.'=? AND artist_id <=> ? AND organisation <=> ? AND role=? AND credited_as <=> ? AND instrument <=> ?',[$c['relation_json'],$map[$c[$column]],$c['artist_id'],$c['organisation'],$c['role'],$c['credited_as'],$c['instrument']??null]);
        }
        return [$aid,$eid];
    }
    private static function text(array $input,string $key,int $limit,bool $required=false): ?string
    { $v=Input::text($input[$key]??'',$limit);if($required&&$v==='')throw new HttpException(422,'Completa '.$key.'.');return $v===''?null:$v; }
    private static function number(array $input,string $key,int $max,bool $required=false): ?int
    { $v=$input[$key]??'';if ($v===''&&!$required)return null;$n=Input::integer($v,$required?1:0);if($n>$max)throw new HttpException(422,'Número fuera de rango.');return $n; }
    private static function scoped(PDO $pdo,string $kind,int $id,int $aid,int $eid): array
    {
        $queries=['album'=>'SELECT * FROM ENC_ALBUMS WHERE id=? AND id=?','edition'=>'SELECT * FROM ENC_EDITIONS WHERE id=? AND album_id=?','medium'=>'SELECT * FROM ENC_MEDIA WHERE id=? AND edition_id=?','track'=>'SELECT t.* FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE t.id=? AND m.edition_id=?','recording'=>'SELECT DISTINCT r.* FROM ENC_RECORDINGS r JOIN ENC_TRACKS t ON t.recording_id=r.id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE r.id=? AND m.edition_id=?','work'=>'SELECT DISTINCT w.* FROM ENC_WORKS w JOIN ENC_RECORDING_WORKS rw ON rw.work_id=w.id JOIN ENC_TRACKS t ON t.recording_id=rw.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE w.id=? AND m.edition_id=?'];
        if(!isset($queries[$kind]))throw new HttpException(422,'Entidad inválida.');
        $row=self::row($pdo,$queries[$kind],[$id,in_array($kind,['album','edition'],true)?$aid:$eid]);if(!$row)throw new HttpException(404);return $row;
    }
    private static function identity(PDO $pdo,string $table,array $in,string $prefix,int $user): int
    {
        $id=self::number($in,$prefix.'_id',2147483647);
        if($id) {$row=self::row($pdo,'SELECT id,musicbrainz_id FROM '.$table.' WHERE id=?',[$id]);if(!$row)throw new HttpException(422,'Identidad no disponible.');if( !$row['musicbrainz_id'] && !\App\Models\EncyclopediaEntity::find($table==='ENC_ARTISTS'?'artist':'label',$id,$user))throw new HttpException(403,'Identidad musical privada no disponible.');return $id;}
        $name=self::text($in,$prefix.'_name',255,true);
        if(self::row($pdo,'SELECT id FROM '.$table.' WHERE name=? LIMIT 1 FOR UPDATE',[$name]))throw new HttpException(422,'Hay una coincidencia de nombre. Selecciona su identidad verificada en vez de duplicarla.');
        if($table==='ENC_ARTISTS'){ $kind=self::text($in,'artist_kind',16)??'unknown';if(!in_array($kind,['unknown','person','group'],true))throw new HttpException(422,'Tipo de artista inválido.');self::exec($pdo,'INSERT INTO ENC_ARTISTS(name,kind) VALUES(?,?)',[$name,$kind]); }else self::exec($pdo,'INSERT INTO '.$table.'(name) VALUES(?)',[$name]);return (int)$pdo->lastInsertId();
    }
    private static function assertNoDuplicateCredit(PDO $pdo,int $aid,int $eid,string $column,int $target,?int $artist,?string $organisation,string $role,?string $instrument,?string $alias,int $id): void
    {
        $matches=self::rows($pdo,'SELECT c.*,s.provider AS credit_provider FROM ENC_CREDITS c LEFT JOIN ENC_SOURCES s ON s.id=c.source_id WHERE c.'.$column.'=? AND c.artist_id <=> ? AND c.organisation <=> ? AND c.role=? AND c.instrument <=> ? AND c.credited_as <=> ? AND c.id<>? FOR UPDATE',[$target,$artist,$organisation,$role,$instrument,$alias,$id]);
        $visible=null;
        foreach($matches as $credit){
            // Superseded legacy imports remain as provenance, but are not a second active credit.
            // Reuse the reader's visibility rule; never exempt manual or rich relationship rows.
            if(($credit['credit_provider']??null)==='musicbrainz' && empty($credit['relation_key'])){
                $visible??=array_fill_keys(array_column(EncyclopediaRecord::creditsFor($aid,$eid),'id'),true);
                if(!isset($visible[$credit['id']]))continue;
            }
            throw new HttpException(409,'Este crédito ya existe; corrige su registro actual.');
        }
    }
    private static function change(PDO $pdo,int $aid,int $eid,string $op,array $in,int $user): array
    {
        $id=self::number($in,'entity_id',2147483647)??0;$before=null;$fields=[];$table='';
        if(in_array($op,['album','edition','medium','track','recording','work'],true)) {
            if($op==='album')$id=$aid;if($op==='edition')$id=$eid;
            if($id)$before=self::scoped($pdo,$op,$id,$aid,$eid);
            $table=['album'=>'ENC_ALBUMS','edition'=>'ENC_EDITIONS','medium'=>'ENC_MEDIA','track'=>'ENC_TRACKS','recording'=>'ENC_RECORDINGS','work'=>'ENC_WORKS'][$op];
            if($op==='album'){$fields=['title'=>self::text($in,'title',255,true),'original_date'=>self::date($in,'original_date'),'release_type'=>self::text($in,'release_type',80),'language'=>self::text($in,'language',32),'description'=>self::text($in,'description',10000)];}
            if($op==='edition'){$fields=['country'=>self::text($in,'country',80),'manufacture_country'=>self::text($in,'manufacture_country',80),'release_date'=>self::date($in,'release_date'),'barcode'=>self::text($in,'barcode',64),'packaging'=>self::text($in,'packaging',255),'edition_type'=>self::text($in,'edition_type',120),'pressing_plant'=>self::text($in,'pressing_plant',255),'remastered'=>self::number($in,'remastered',1)];}
            if($op==='medium'){$fields=['position'=>self::number($in,'position',65535,true),'format'=>self::text($in,'format',80),'rpm'=>self::decimal($in,'rpm',999.999),'diameter_inches'=>self::decimal($in,'diameter_inches',99.99),'color'=>self::text($in,'color',120),'weight_grams'=>self::number($in,'weight_grams',65535)];if(!$id)$fields['edition_id']=$eid;}
            if($op==='track'){$mid=self::number($in,'medium_id',2147483647,true);self::scoped($pdo,'medium',$mid,$aid,$eid);$rid=self::number($in,'recording_id',2147483647);if($rid)self::scoped($pdo,'recording',$rid,$aid,$eid);$fields=['medium_id'=>$mid,'position'=>self::number($in,'position',65535,true),'printed_number'=>self::text($in,'printed_number',32),'side'=>self::text($in,'side',16),'title'=>self::text($in,'title',255,true),'duration_ms'=>self::duration($in),'recording_id'=>$rid];}
            if($op==='recording'){$track=self::number($in,'track_id',2147483647,true);self::scoped($pdo,'track',$track,$aid,$eid);$fields=['title'=>self::text($in,'title',255,true),'duration_ms'=>self::duration($in)];}
            if($op==='work'){$recording=self::number($in,'recording_id',2147483647,true);self::scoped($pdo,'recording',$recording,$aid,$eid);$fields=['title'=>self::text($in,'title',255,true)];}
            if($id)self::exec($pdo,'UPDATE '.$table.' SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($fields))).' WHERE id=?',[...array_values($fields),$id]);
            else {self::exec($pdo,'INSERT INTO '.$table.'('.implode(',',array_keys($fields)).') VALUES('.implode(',',array_fill(0,count($fields),'?')).')',array_values($fields));$id=(int)$pdo->lastInsertId();}
            if($op==='recording')self::exec($pdo,'UPDATE ENC_TRACKS SET recording_id=? WHERE id=?',[$id,$track]);
            if($op==='work')self::exec($pdo,'INSERT INTO ENC_RECORDING_WORKS VALUES(?,?) ON DUPLICATE KEY UPDATE work_id=VALUES(work_id)',[$recording,$id]);
        } elseif($op==='credit') {
            $kind=self::text($in,'target_kind',16,true);$target=self::number($in,'target_id',2147483647,true);self::scoped($pdo,$kind,$target,$aid,$eid);if(!in_array($kind,['album','edition','track','recording','work'],true))throw new HttpException(422,'Destino inválido.');$column=$kind.'_id';
            if($id){$before=self::row($pdo,'SELECT * FROM ENC_CREDITS WHERE id=?',[$id]);if(!$before)throw new HttpException(404);foreach(['album','edition','track','recording','work'] as $k)if($before[$k.'_id'])self::scoped($pdo,$k,(int)$before[$k.'_id'],$aid,$eid);}
            $organisation=self::text($in,'organisation',255);if($organisation && (!empty($in['artist_id'])||!empty($in['artist_name'])))throw new HttpException(422,'Elige una identidad artística o una organización, no ambas.');$artist=$organisation?null:self::identity($pdo,'ENC_ARTISTS',$in,'artist',$user);$role=self::text($in,'role',120,true);$instrument=self::text($in,'instrument',120);
            $alias=self::text($in,'credited_as',255);
            self::assertNoDuplicateCredit($pdo,$aid,$eid,$column,$target,$artist,$organisation,$role,$instrument,$alias,$id);
            if($id) self::exec($pdo,'UPDATE ENC_CREDITS SET album_id=NULL,edition_id=NULL,track_id=NULL,recording_id=NULL,work_id=NULL,'.$column.'=?,artist_id=?,organisation=?,role=?,instrument=?,credited_as=?,source_id=NULL,credit_key=NULL WHERE id=?',[$target,$artist,$organisation,$role,$instrument,self::text($in,'credited_as',255),$id]);
            else {
                $creditKey=hash('sha256',json_encode(['manual',$column,$target,$artist,$organisation,$role,$instrument,$alias],JSON_THROW_ON_ERROR));
                self::exec($pdo,'INSERT INTO ENC_CREDITS('.$column.',artist_id,organisation,role,instrument,credited_as,credit_key) VALUES(?,?,?,?,?,?,?)',[$target,$artist,$organisation,$role,$instrument,$alias,$creditKey]);$id=(int)$pdo->lastInsertId();
            }
            $fields=['id'=>$id,'target_kind'=>$kind,'target_id'=>$target,'artist_id'=>$artist,'organisation'=>$organisation,'role'=>$role,'instrument'=>$instrument];
        } elseif($op==='label') {
            $oldLabel=self::number($in,'old_label_id',2147483647);$oldCat=self::text($in,'old_catalogue_number',120)??'';
            if($oldLabel){$before=self::row($pdo,'SELECT * FROM ENC_EDITION_LABELS WHERE edition_id=? AND label_id=? AND catalogue_number=?',[$eid,$oldLabel,$oldCat]);if(!$before)throw new HttpException(409,'El sello o catálogo ha cambiado.');}
            $label=self::identity($pdo,'ENC_LABELS',$in,'label',$user);$cat=self::text($in,'catalogue_number',120)??'';
            if($oldLabel)self::exec($pdo,'DELETE FROM ENC_EDITION_LABELS WHERE edition_id=? AND label_id=? AND catalogue_number=?',[$eid,$oldLabel,$oldCat]);
            self::exec($pdo,'INSERT INTO ENC_EDITION_LABELS VALUES(?,?,?) ON DUPLICATE KEY UPDATE catalogue_number=VALUES(catalogue_number)',[$eid,$label,$cat]);$fields=['label_id'=>$label,'catalogue_number'=>$cat];
        } elseif($op==='genre') {
            $oldTerm=self::number($in,'term_id',2147483647);if($oldTerm){$before=self::row($pdo,'SELECT * FROM ENC_ALBUM_TERMS WHERE album_id=? AND term_id=?',[$aid,$oldTerm]);if(!$before)throw new HttpException(409);}
            $name=self::text($in,'name',120,true);$kind=self::text($in,'kind',10,true);if(!in_array($kind,['genre','style'],true))throw new HttpException(422);
            self::exec($pdo,'INSERT INTO ENC_TERMS(kind,name) VALUES(?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',[$kind,$name]);$term=(int)$pdo->lastInsertId();if($oldTerm)self::exec($pdo,'DELETE FROM ENC_ALBUM_TERMS WHERE album_id=? AND term_id=?',[$aid,$oldTerm]);self::exec($pdo,'INSERT INTO ENC_ALBUM_TERMS VALUES(?,?) ON DUPLICATE KEY UPDATE term_id=VALUES(term_id)',[$aid,$term]);$fields=['kind'=>$kind,'name'=>$name];
        } elseif(in_array($op,['artist_identity','label_identity'],true)) {
            if(!self::sharedEditor($user))throw new HttpException(403,'Corregir una identidad requiere permiso editorial explícito.');
            $isArtist=$op==='artist_identity';$table=$isArtist?'ENC_ARTISTS':'ENC_LABELS';
            $available=$isArtist?array_merge(array_column(self::rows($pdo,'SELECT artist_id FROM ENC_ALBUM_ARTISTS WHERE album_id=?',[$aid]),'artist_id'),array_column(\App\Models\EncyclopediaRecord::creditsFor($aid,$eid),'artist_id')):array_column(self::rows($pdo,'SELECT label_id FROM ENC_EDITION_LABELS WHERE edition_id=?',[$eid]),'label_id');
            if(!in_array($id,array_map('intval',$available),true))throw new HttpException(404);
            $before=self::row($pdo,'SELECT * FROM '.$table.' WHERE id=? FOR UPDATE',[$id]);
            if (!hash_equals(self::identityFingerprint($before),self::text($in,'identity_before_hash',64)??'')) throw new HttpException(409,'La identidad cambió en otro trabajo. Recarga y compara antes de corregirla.');
            $name=self::text($in,'name',255,true);
            if(self::row($pdo,'SELECT id FROM '.$table.' WHERE name=? AND id<>? LIMIT 1 FOR UPDATE',[$name,$id]))throw new HttpException(409,'Hay otra identidad con ese nombre. Verifica y reasigna la relación; no se fusionarán identidades automáticamente.');
            $fields=['name'=>$name];if($isArtist){$kind=self::text($in,'kind',16,true);if(!in_array($kind,['unknown','person','group'],true))throw new HttpException(422);$fields['kind']=$kind;}
            self::exec($pdo,'UPDATE '.$table.' SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($fields))).' WHERE id=?',[...array_values($fields),$id]);
        } elseif($op==='artist') {
            $artist=self::identity($pdo,'ENC_ARTISTS',$in,'artist',$user);$pos=self::number($in,'position',65535,true);self::exec($pdo,'INSERT INTO ENC_ALBUM_ARTISTS(album_id,artist_id,position,credited_as) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE artist_id=VALUES(artist_id),credited_as=VALUES(credited_as)',[$aid,$artist,$pos,self::text($in,'credited_as',255)]);$fields=['artist_id'=>$artist,'position'=>$pos];
        } else throw new HttpException(422,'Operación musical inválida.');
        return [$before,['id'=>$id,'values'=>$fields]];
    }
    private static function duration(array $in): ?int
    { $value=self::text($in,'duration',16);if($value===null)return null;if(!preg_match('/^([0-9]{1,4}):([0-5][0-9])$/D',$value,$m))throw new HttpException(422,'Duración: minutos:segundos, por ejemplo 4:19.');return ((int)$m[1]*60+(int)$m[2])*1000; }
    private static function decimal(array $in,string $key,float $max): ?string
    { $v=self::text($in,$key,16);if($v===null)return null;if(!preg_match($key==='diameter_inches'?'/^[0-9]+(?:\.[0-9]{1,2})?$/D':'/^[0-9]+(?:\.[0-9]{1,3})?$/D',$v)||(float)$v<=0||(float)$v>$max)throw new HttpException(422,'Medida inválida.');return $v; }
    private static function date(array $in,string $key): ?string
    { try{return MusicBrainzNormalizer::date(self::text($in,$key,10));}catch(\App\Services\Metadata\MetadataException){throw new HttpException(422,'Fecha musical inválida.');} }
    private static function exec(PDO $pdo,string $q,array $v): void {$pdo->prepare($q)->execute($v);}
    private static function row(PDO $pdo,string $q,array $v): array|false {$s=$pdo->prepare($q);$s->execute($v);return $s->fetch(PDO::FETCH_ASSOC);}
    private static function rows(PDO $pdo,string $q,array $v): array {$s=$pdo->prepare($q);$s->execute($v);return $s->fetchAll(PDO::FETCH_ASSOC);}
}
