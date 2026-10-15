<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,HttpException};
use App\Models\EncyclopediaRecord;
use App\Services\Metadata\{MusicBrainzClient,MusicBrainzCreditGraph,MetadataException};
use PDO;

/** Explicit, bounded, durable edition refresh. GET requests never contact MusicBrainz. */
final class EncyclopediaCreditSync
{
    public const MAX_ENTITIES=256, MAX_CALLS=400, MAX_BYTES=12582912, STEP_CALLS=3;
    public static function available(?PDO $pdo=null): bool
    { return (bool)($pdo??Database::pdo())->query("SHOW COLUMNS FROM ENC_CREDITS LIKE 'relation_json'")->fetch(); }
    public static function requireSchema(): void
    { if(!self::available())throw new HttpException(503,'La actualización de créditos necesita la migración 006; los créditos actuales se conservan.'); }
    private static function sql(string $sql,array $args=[]): \PDOStatement
    { $s=Database::pdo()->prepare($sql);$s->execute($args);return $s; }
    private static function copy(int $vinyl,int $user): array
    {
        $r=EncyclopediaRecord::forCopy($vinyl,$user);
        if(!$r)throw new HttpException(404);
        if(empty($r['edition']['musicbrainz_release_id'])||empty($r['album']['musicbrainz_group_id'])||$r['album']['draft_owner_id']!==null)throw new HttpException(422,'Selecciona una edición MusicBrainz confirmada; no se actualizan borradores privados.');
        return $r;
    }
    private static function lockCopy(int $vinyl,int $user,callable $operation): mixed
    {
        $pdo=Database::pdo();EncyclopediaCollection::isolation($pdo);$pdo->beginTransaction();
        try{
            if(!self::sql('SELECT Id FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$vinyl,$user])->fetch())throw new HttpException(404);
            $result=$operation();$pdo->commit();return $result;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public static function start(int $vinyl,int $user): string
    {
        self::requireSchema();return self::lockCopy($vinyl,$user,static function()use($vinyl,$user){$r=self::copy($vinyl,$user);$pending=self::pending($vinyl,$user);if($pending)return $pending['id'];
        if((int)self::sql("SELECT COUNT(*) FROM ENC_CREDIT_JOBS WHERE owner_id=? AND status IN ('collecting','ready')",[$user])->fetchColumn()>=10)throw new HttpException(409,'Termina las actualizaciones pendientes antes de iniciar más consultas.');
        $key=bin2hex(random_bytes(32));
        $state=['release'=>$r['edition']['musicbrainz_release_id'],'group'=>$r['album']['musicbrainz_group_id'],'album'=>(int)$r['album']['id'],
          'started_at'=>microtime(true),'revision'=>EncyclopediaManual::revision((int)$r['album']['id']),'queue'=>[], 'responses'=>[], 'attempts'=>[], 'calls'=>0,'error'=>null,'retry_at'=>0];
        self::enqueue($state,'release',$state['release']);self::enqueue($state,'release-group',$state['group']);
        self::sql("INSERT INTO ENC_CREDIT_JOBS(id,owner_id,vinyl_id,edition_id,status,state_json) VALUES(?,?,?,?,'collecting',?)",[$key,$user,$vinyl,$r['edition']['id'],json_encode($state,JSON_THROW_ON_ERROR)]);
        return $key;});
    }
    public static function pending(int $vinyl,int $user): ?array
    {
        $r=self::copy($vinyl,$user);
        $j=self::sql("SELECT id FROM ENC_CREDIT_JOBS WHERE owner_id=? AND vinyl_id=? AND edition_id=? AND status IN ('collecting','ready') ORDER BY created_at DESC,id DESC LIMIT 1",[$user,$vinyl,$r['edition']['id']])->fetch();
        return $j?self::job($j['id'],$user):null;
    }
    public static function lastSaved(int $vinyl,int $user): ?array
    {
        $r=self::copy($vinyl,$user);
        $id=self::sql("SELECT j.id FROM ENC_CREDIT_JOBS j WHERE j.vinyl_id=? AND j.owner_id=? AND j.edition_id=? AND j.status='complete' ORDER BY (SELECT MAX(a.id) FROM ENC_ASSERTIONS a WHERE a.edition_id=j.edition_id AND a.field_name='credits.sync' AND JSON_UNQUOTE(JSON_EXTRACT(a.value_json,'$.job'))=j.id) DESC,j.updated_at DESC,j.id DESC LIMIT 1",[$vinyl,$user,$r['edition']['id']])->fetchColumn();
        return $id?self::job($id,$user):null;
    }
    public static function current(int $vinyl,int $user): ?array
    {
        $pending=self::pending($vinyl,$user);if($pending)return $pending;
        $r=self::copy($vinyl,$user);
        $id=self::sql('SELECT id FROM ENC_CREDIT_JOBS WHERE vinyl_id=? AND owner_id=? AND edition_id=? ORDER BY created_at DESC,COALESCE(JSON_EXTRACT(state_json,"$.started_at"),0) DESC,id DESC LIMIT 1',[$vinyl,$user,$r['edition']['id']])->fetchColumn();
        return $id?self::job($id,$user):null;
    }
    public static function cancel(string $key,int $user): void
    {
        $j=self::job($key,$user);
        if(!in_array($j['status'],['collecting','ready'],true))throw new HttpException(409,'Esta consulta ya terminó.');
        if(self::sql("UPDATE ENC_CREDIT_JOBS SET status='cancelled' WHERE id=? AND owner_id=? AND status IN ('collecting','ready') AND (lease_until IS NULL OR lease_until<NOW())",[$key,$user])->rowCount()!==1)throw new HttpException(409,'Espera a que termine el paso en curso antes de detener la consulta.');
    }
    public static function resume(string $key,int $user): void
    {
        $j=self::job($key,$user);self::lockCopy((int)$j['vinyl_id'],$user,static function()use($key,$user){$j=self::job($key,$user);
        if($j['status']!=='cancelled')throw new HttpException(409,'Solo se puede reanudar una consulta cancelada.');
        if((int)self::sql("SELECT COUNT(*) FROM ENC_CREDIT_JOBS WHERE owner_id=? AND status IN ('collecting','ready')",[$user])->fetchColumn()>=10)throw new HttpException(409,'Termina las actualizaciones pendientes antes de reanudar más consultas.');
        if(self::pending((int)$j['vinyl_id'],$user))throw new HttpException(409,'Ya hay otra consulta pendiente para esta edición. Termínala o cancélala antes de reanudar esta.');
        // Restore the saved checkpoint; no queue, attempt budget or credit is reset.
        $status=$j['state']['queue']?'collecting':'ready';
        if(self::sql("UPDATE ENC_CREDIT_JOBS SET status=? WHERE id=? AND owner_id=? AND status='cancelled' AND (lease_until IS NULL OR lease_until<NOW())",[$status,$key,$user])->rowCount()!==1)throw new HttpException(409,'La consulta cambió; recarga su estado.');});
    }
    public static function presentation(?array $j): array
    {
        if(!$j)return ['phase'=>'idle','title'=>'Preparar consulta','message'=>'Consulta los créditos de esta edición. Tus datos personales y créditos manuales se conservan.','disabled'=>false];
        $s=$j['state'];$busy=!empty($j['lease_active']);
        $phase=$j['status'];
        if($phase==='collecting'&&(!empty($s['error'])||($s['retry_at']??0)>time()))$phase='paused';
        [$title,$message]=match($phase){
            'collecting'=>['Recopilando información','Estamos consultando MusicBrainz para obtener los créditos de esta edición. Continúa para procesar el siguiente bloque; todavía no se han guardado los nuevos créditos.'],
            'paused'=>['Consulta temporalmente pausada','MusicBrainz no está disponible temporalmente. Tu progreso se ha conservado y podrás continuar después.'],
            'ready'=>['Recopilación completada','Hemos terminado de recopilar los créditos disponibles. Falta guardarlos: revisa el resultado y pulsa Guardar créditos.'],
            'complete'=>['Créditos guardados correctamente','Los créditos musicales se han actualizado correctamente.'],
            'cancelled'=>['Consulta cancelada','Los créditos guardados no han cambiado. El progreso se conserva y puedes reanudar esta consulta.'],
            'reverted'=>['Actualización revertida','Se ha recuperado el estado anterior de los créditos.'],
            default=>['Consulta detenida sin guardar','No se han aplicado créditos parciales. Conservamos el progreso para su diagnóstico; revisa el motivo antes de iniciar otra consulta.']};
        return ['phase'=>$phase,'title'=>$title,'message'=>$message,'disabled'=>$busy||($s['retry_at']??0)>time(),'busy'=>$busy];
    }
    public static function job(string $key,int $user): array
    {
        self::requireSchema();if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new HttpException(404);
        $j=self::sql('SELECT j.*,(j.lease_until>NOW()) AS lease_active FROM ENC_CREDIT_JOBS j WHERE j.id=? AND j.owner_id=?',[$key,$user])->fetch();if(!$j)throw new HttpException(404);
        $r=self::copy((int)$j['vinyl_id'],$user);$state=json_decode($j['state_json'],true,128,JSON_THROW_ON_ERROR);
        if((int)$r['edition']['id']!==(int)$j['edition_id']||$r['edition']['musicbrainz_release_id']!==$state['release']||$r['album']['musicbrainz_group_id']!==$state['group'])throw new HttpException(409,'La vinculación cambió; inicia una actualización para la edición actual.');
        return $j+['state'=>$state,'record'=>$r];
    }
    private static function enqueue(array &$state,string $kind,string $id): void
    {
        \App\Services\Metadata\MusicBrainzNormalizer::id($id);$k=$kind.'/'.$id;
        if(isset($state['responses'][$k])||in_array($k,$state['queue'],true))return;
        if(count($state['responses'])+count($state['queue'])>=self::MAX_ENTITIES)throw new HttpException(422,'Esta edición excede el presupuesto de 256 entidades. No se aplicó ningún cambio.');
        $state['queue'][]=$k;
    }
    private static function discover(array &$state,string $kind,array $d): void
    {
        if($kind==='release') {
            if(($d['release-group']['id']??null)!==$state['group'])throw new MetadataException('invalid_response');
            foreach($d['media']??[] as $m){$tracks=$m['tracks']??[];if(isset($m['track-count'])&&(int)$m['track-count']!==count($tracks))throw new MetadataException('invalid_response');if(isset($m['pregap']))$tracks[]=$m['pregap'];foreach($m['data-tracks']??[] as $dt)$tracks[]=$dt;foreach($tracks as $t)if(!empty($t['recording']['id']))self::enqueue($state,'recording',$t['recording']['id']);}
        }
        foreach($d['relations']??[] as $rel) {
            if($kind==='recording'&&($rel['type']??null)==='performance'&&isset($rel['work']['id']))self::enqueue($state,'work',$rel['work']['id']);
            foreach(['place','label'] as $leaf)if(isset($rel[$leaf]['id']))self::enqueue($state,$leaf,$rel[$leaf]['id']);
        }
    }
    private static function persist(string $key,string $lease,array $s,string $status): void
    {
        $json=json_encode($s,JSON_THROW_ON_ERROR);
        if(strlen($json)>self::MAX_BYTES)throw new HttpException(422,'Los metadatos exceden 12 MiB; no se aplicaron créditos parciales.');
        self::sql('UPDATE ENC_CREDIT_JOBS SET state_json=?,status=? WHERE id=? AND lease_token=?',[$json,$status,$key,$lease]);
        // MySQL reports zero changed rows for an identical checkpoint, too.
        if(!self::sql('SELECT id FROM ENC_CREDIT_JOBS WHERE id=? AND lease_token=?',[$key,$lease])->fetchColumn())throw new HttpException(409,'La reserva de esta consulta cambió; recarga su estado.');
    }
    public static function step(string $key,int $user,MusicBrainzClient $client): array
    {
        $j=self::job($key,$user);if($j['status']!=='collecting')return $j;
        $s=$j['state'];if($s['retry_at']>time())return $j;
        $lease=bin2hex(random_bytes(16));
        if(self::sql("UPDATE ENC_CREDIT_JOBS SET lease_token=?,lease_until=DATE_ADD(NOW(),INTERVAL 90 SECOND) WHERE id=? AND owner_id=? AND status='collecting' AND (lease_until IS NULL OR lease_until<NOW())",[$lease,$key,$user])->rowCount()!==1)throw new HttpException(409,'La consulta cambió o hay otro paso en curso. Recarga el estado; una reserva caduca en 90 segundos.');
        try {
            for($n=0;$n<self::STEP_CALLS && $s['queue'];$n++) {
                $k=$s['queue'][0];$attempt=($s['attempts'][$k]??0)+1;
                if($attempt>3||$s['calls']>=self::MAX_CALLS){$s['error']='Presupuesto de reintentos agotado. Inicia otra actualización; no se aplicaron cambios.';self::persist($key,$lease,$s,'failed');break;}
                $s['attempts'][$k]=$attempt;$s['calls']++;$s['error']=null;
                self::persist($key,$lease,$s,'collecting'); // Count attempts even after process interruption.
                [$kind,$id]=explode('/',$k,2);
                try {
                    $response=$client->creditLookup($kind,$id);
                    if(in_array($kind,['release','release-group','recording','work'],true))MusicBrainzCreditGraph::relations($kind,$response);
                    $s['responses'][$k]=$response;
                    self::discover($s,$kind,$response['data']);array_shift($s['queue']);$s['retry_at']=0;$s['last_http_status']=null;
                    self::persist($key,$lease,$s,$s['queue']?'collecting':'ready');
                }catch(MetadataException $e){
                    if(!$e->requestSent){$s['calls']--;$s['attempts'][$k]--;}
                    $s['last_http_status']=$e->httpStatus;$s['error']=$e->reason;$s['retry_at']=time()+max($e->retryAfter,min(60,2**$attempt));
                    self::persist($key,$lease,$s,($s['attempts'][$k]??0)>=3||in_array($e->reason,['not_found','invalid_response','configuration'],true)?'failed':'collecting');break;
                }
            }
        }catch(\Throwable $e){
            // Never log raw HTTP bodies, account information or exception messages.
            $s['error']='No se pudo completar la recopilación; los créditos guardados no cambiaron.';
            unset($s['responses'][$s['queue'][0]??'']);
            try {self::persist($key,$lease,$s,'failed');}catch(\Throwable){}
            throw $e;
        }finally{self::sql('UPDATE ENC_CREDIT_JOBS SET lease_token=NULL,lease_until=NULL WHERE id=? AND lease_token=?',[$key,$lease]);}
        return self::job($key,$user);
    }
    public static function graph(array $j): array
    {
        $rows=[];$s=$j['state'];
        foreach($s['responses'] as $k=>$response){[$kind]=explode('/',$k,2);if(!in_array($kind,['release','release-group','recording','work'],true))continue;
            foreach(MusicBrainzCreditGraph::relations($kind,$response,$s['responses']) as $r){$r['context_edition_id']=$kind==='release-group'?null:(int)$j['edition_id'];$r['relation_key']=hash('sha256',$r['key'].':'.($r['context_edition_id']??0));$rows[$r['relation_key']]=$r;}}
        return array_values($rows);
    }
    private static function short(?string $text,int $max=255): ?string
    { if($text===null||$text==='')return null;preg_match_all('/./us',$text,$m);return implode('',array_slice($m[0],0,$max)); }
    private static function identity(string $table,string $idColumn,string $mbid,string $name,?string $kind=null): int
    {
        $cols=$kind===null?'title,'.$idColumn:'name,'.$idColumn.',kind';$args=$kind===null?[self::short($name),$mbid]:[self::short($name),$mbid,$kind];
        self::sql('INSERT INTO '.$table.'('.$cols.') VALUES('.implode(',',array_fill(0,count($args),'?')).') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',$args);
        return (int)Database::pdo()->lastInsertId();
    }
    private static function source(array $r): int
    {
        self::sql("INSERT INTO ENC_SOURCES(provider,entity_type,external_id,source_url,fetched_at,license_note) VALUES('musicbrainz',?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),fetched_at=GREATEST(fetched_at,VALUES(fetched_at))",[$r['source_kind'],$r['source_mbid'],$r['source_url'],gmdate('Y-m-d H:i:s',$r['fetched_at']),'MusicBrainz: core CC0; supplementary CC BY-NC-SA 3.0']);
        return (int)Database::pdo()->lastInsertId();
    }
    private static function assertion(int $album,int $edition,int $user,string $field,array $value,?int $source=null): void
    {self::sql("INSERT INTO ENC_ASSERTIONS(album_id,edition_id,field_name,value_json,author_user_id,status,source_id) VALUES(NULL,?,?,?,?,'accepted',?)",[$edition,$field,json_encode($value,JSON_THROW_ON_ERROR),$user,$source]);}
    public static function apply(string $key,int $user): array
    {
        $j=self::job($key,$user);if($j['status']==='complete')return $j;
        if($j['status']!=='ready')throw new HttpException(409,'Finaliza todas las consultas antes de aplicar.');
        $pdo=Database::pdo();EncyclopediaCollection::isolation($pdo);$pdo->beginTransaction();
        try {
            self::sql('SELECT id FROM ENC_CREDIT_JOBS WHERE id=? FOR UPDATE',[$key]);
            $j=self::job($key,$user);if($j['status']==='complete'){$pdo->commit();return $j;}
            if($j['status']!=='ready')throw new HttpException(409,'La consulta ya no está lista para aplicar. Recarga su estado.');
            self::sql('SELECT Id FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$j['vinyl_id'],$user]);
            $copy=self::sql('SELECT edition_id FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=? FOR UPDATE',[$j['vinyl_id'],$user])->fetch();
            if(!$copy||(int)$copy['edition_id']!==(int)$j['edition_id'])throw new HttpException(409,'La edición cambió.');
            self::sql('SELECT id FROM ENC_ALBUMS WHERE id=? FOR UPDATE',[$j['state']['album']]);
            if(EncyclopediaManual::revision($j['state']['album'])!==$j['state']['revision'])throw new HttpException(409,'Los metadatos cambiaron durante la consulta. Inicia otra actualización.');
            $changes=[];$seen=[];$protected=0;$ids=[];$manualKeys=[];$newCount=0;$updatedCount=0;$unchangedCount=0;$manualCount=0;$retiredCount=0;$contextCounts=['new'=>0,'updated'=>0,'existing'=>0,'retained'=>0];
            foreach(EncyclopediaRecord::creditsFor($j['state']['album'],(int)$j['edition_id']) as $manual){
                if(($manual['credit_provider']??'manual')==='musicbrainz')continue;
                $manualCount++;
                if(!empty($manual['relation_key'])){$manualKeys[$manual['relation_key']]=true;continue;}
                $mr=$manual['relation']??null;
                if(!empty($mr['raw'])&&!empty($mr['source_kind'])&&!empty($mr['source_mbid'])){
                    foreach(MusicBrainzCreditGraph::relations($mr['source_kind'],['data'=>['id'=>$mr['source_mbid'],'relations'=>[$mr['raw']]],'fetched_at'=>0]) as $edge)$manualKeys[hash('sha256',$edge['key'].':'.($mr['source_kind']==='release-group'?0:$j['edition_id']))]=true;
                }
            }
            foreach(self::graph($j) as $r){
                $seen[]=$r['relation_key'];if(isset($manualKeys[$r['relation_key']])){$protected++;continue;}
                $kind=$r['source_kind'];$anchor=match($kind){'release-group'=>'album_id','release'=>'edition_id','recording'=>'recording_id','work'=>'work_id'};
                $target=match($kind){'release-group'=>$j['state']['album'],'release'=>(int)$j['edition_id'],default=>self::identity($kind==='work'?'ENC_WORKS':'ENC_RECORDINGS','musicbrainz_id',$r['source_mbid'],$j['state']['responses'][$kind.'/'.$r['source_mbid']]['data']['title'])};
                $artist=$r['target_kind']==='artist'?self::identity('ENC_ARTISTS','musicbrainz_id',$r['target_mbid'],$r['name'],match($r['raw']['artist']['type']??''){'Person'=>'person','Group'=>'group',default=>'unknown'}):null;
                $source=self::source($r);
                $old=self::sql('SELECT c.*,s.provider FROM ENC_CREDITS c LEFT JOIN ENC_SOURCES s ON s.id=c.source_id WHERE c.relation_key=? FOR UPDATE',[$r['relation_key']])->fetch();
                if($old && $old['provider']!=='musicbrainz'){$protected++;continue;}
                $instrument=self::short(implode(', ',MusicBrainzCreditGraph::instrumentAttributes($r['role'],$r['attributes'])),120);
                $json=json_encode($r,JSON_THROW_ON_ERROR);$cols=[$target,$artist,$artist?null:self::short($r['name']),self::short($r['role'],120),self::short($r['credited_as']),$source,$instrument,$r['relation_key'],$json,$r['context_edition_id'],gmdate('Y-m-d H:i:s',$r['fetched_at'])];
                if($old){
                    unset($old['provider']);
                    // Same fetched source and payload: true no-op, including history.
                    $oldPayload=json_decode($old['relation_json']??'null',true);$newPayload=$r;
                    if(is_array($oldPayload))unset($oldPayload['fetched_at']);unset($newPayload['fetched_at']);
                    if($oldPayload==$newPayload&&(int)$old['sync_active']===1){if(MusicBrainzCreditGraph::isContext($r))$contextCounts['existing']++;else $unchangedCount++;continue;}
                    if(MusicBrainzCreditGraph::isContext($r))$contextCounts['updated']++;else $updatedCount++;
                    self::sql("UPDATE ENC_CREDITS SET $anchor=?,artist_id=?,organisation=?,role=?,credited_as=?,source_id=?,instrument=?,relation_key=?,relation_json=?,context_edition_id=?,seen_at=?,sync_active=1 WHERE id=?",[...$cols,$old['id']]);$id=(int)$old['id'];
                }else{
                    if(MusicBrainzCreditGraph::isContext($r))$contextCounts['new']++;else $newCount++;
                    self::sql("INSERT INTO ENC_CREDITS($anchor,artist_id,organisation,role,credited_as,source_id,instrument,relation_key,relation_json,context_edition_id,seen_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)",$cols);$id=(int)$pdo->lastInsertId();
                }
                $after=self::sql('SELECT * FROM ENC_CREDITS WHERE id=?',[$id])->fetch();$changes[]=['before'=>$old?:null,'after'=>$after];$ids[]=$id;
            }
            // Scope recording/work edges to this exact edition; group edges are genuinely group-wide.
            $oldRows=self::sql('SELECT c.* FROM ENC_CREDITS c JOIN ENC_SOURCES s ON s.id=c.source_id WHERE c.relation_key IS NOT NULL AND c.sync_active=1 AND s.provider="musicbrainz" AND (c.context_edition_id=? OR (c.album_id=? AND c.context_edition_id IS NULL)) FOR UPDATE',[$j['edition_id'],$j['state']['album']])->fetchAll();
            foreach($oldRows as $old)if(!in_array($old['relation_key'],$seen,true)){
                // A narrower lookup intentionally omits some context. Absence is not deletion evidence.
                if(MusicBrainzCreditGraph::isContext(json_decode($old['relation_json']??'{}',true))){$contextCounts['retained']++;continue;}
                $retiredCount++;
                self::sql('UPDATE ENC_CREDITS SET sync_active=0 WHERE id=?',[$old['id']]);$after=$old;$after['sync_active']=0;$changes[]=['before'=>$old,'after'=>$after];
            }
            // Associate only works explicitly performed by recordings on the selected release.
            foreach($j['state']['responses'] as $k=>$response)if(str_starts_with($k,'recording/')){
                $rec=self::identity('ENC_RECORDINGS','musicbrainz_id',$response['data']['id'],$response['data']['title']);
                foreach($response['data']['relations']??[] as $rel)if(($rel['type']??'')==='performance'&&isset($rel['work']['id'])){
                    $w=$rel['work'];$wid=self::identity('ENC_WORKS','musicbrainz_id',$w['id'],$w['title']);self::sql('INSERT IGNORE INTO ENC_RECORDING_WORKS(recording_id,work_id) VALUES(?,?)',[$rec,$wid]);
                }
            }
            $s=$j['state'];$s['changes']=$changes;$s['protected']=$protected;$s['rows']=count($seen);$s['summary']=['new'=>$newCount,'updated'=>$updatedCount,'existing'=>$unchangedCount,'manual_preserved'=>$manualCount,'protected'=>$protected,'retired'=>$retiredCount];$s['context_summary']=$contextCounts;$s['credit_summary_version']=2;$s['applied_at']=gmdate('Y-m-d\TH:i:s\Z');
            self::assertion($s['album'],(int)$j['edition_id'],$user,'credits.sync',['job'=>$key,'changes'=>$changes,'protected'=>$protected]);
            $s['applied_revision']=EncyclopediaManual::revision($s['album']);
            $encoded=json_encode($s,JSON_THROW_ON_ERROR);if(strlen($encoded)>self::MAX_BYTES)throw new HttpException(422,'El historial recuperable excede el presupuesto; ningún crédito se modificó.');
            self::sql('UPDATE ENC_CREDIT_JOBS SET status="complete",state_json=? WHERE id=?',[$encoded,$key]);
            $pdo->commit();return self::job($key,$user);
        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            if($e instanceof HttpException && $e->status===409)self::sql('UPDATE ENC_CREDIT_JOBS SET status="failed" WHERE id=? AND owner_id=? AND status="ready"',[$key,$user]);
            throw $e;
        }
    }
    public static function revert(string $key,int $user): array
    {
        $j=self::job($key,$user);if($j['status']==='reverted')return $j;if($j['status']!=='complete')throw new HttpException(409);
        $pdo=Database::pdo();$pdo->beginTransaction();try{
            self::sql('SELECT id FROM ENC_CREDIT_JOBS WHERE id=? FOR UPDATE',[$key]);
            self::sql('SELECT Id FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$j['vinyl_id'],$user]);
            self::sql('SELECT vinyl_id FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=? FOR UPDATE',[$j['vinyl_id'],$user]);
            self::sql('SELECT id FROM ENC_ALBUMS WHERE id=? FOR UPDATE',[$j['state']['album']]);
            $j=self::job($key,$user);
            if(EncyclopediaManual::revision($j['state']['album'])!==$j['state']['applied_revision'])throw new HttpException(409,'Hay cambios posteriores; no se sobrescribirán.');
            foreach(array_reverse($j['state']['changes']) as $change){
                $id=$change['after']['id'];$current=self::sql('SELECT * FROM ENC_CREDITS WHERE id=? FOR UPDATE',[$id])->fetch();
                if($current!=$change['after'])throw new HttpException(409,'Un crédito cambió después; no se sobrescribirá.');
                $before=$change['before'];if(!$before){self::sql('UPDATE ENC_CREDITS SET sync_active=0 WHERE id=?',[$id]);continue;}
                $columns=array_diff(array_keys($before),['id']);self::sql('UPDATE ENC_CREDITS SET '.implode(',',array_map(fn($c)=>$c.'=?',$columns)).' WHERE id=?',[...array_map(fn($c)=>$before[$c],$columns),$id]);
            }
            self::assertion($j['state']['album'],(int)$j['edition_id'],$user,'credits.revert',['job'=>$key]);self::sql('UPDATE ENC_CREDIT_JOBS SET status="reverted" WHERE id=?',[$key]);$pdo->commit();return self::job($key,$user);
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
