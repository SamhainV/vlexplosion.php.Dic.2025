<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,HttpException};
use PDO;

/** Association only: never INSERT or UPDATE VINYLS_TBL or its author relationships. */
final class EncyclopediaLink
{
    public static function state(?array $copy): string
    { return ($copy['album_id']??0).':'.($copy['edition_id']??0); }
    public static function apply(int $vinyl,int $user,?array $metadata,string $expected,string $key,int $attempt=0): int
    {
        $pdo=Database::pdo();EncyclopediaCollection::isolation($pdo);
        if (!preg_match('/^[a-f0-9]{64}$/D',$key)) throw new HttpException(422,'Confirmación inválida.');
        $hash=hash('sha256',json_encode(['association',$vinyl,$metadata,$expected],JSON_THROW_ON_ERROR));
        $pdo->beginTransaction();
        try {
            self::sql($pdo,'INSERT INTO AI_SAVE_KEYS_TBL(User_Id,Request_Key,Payload_Hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE Request_Key=Request_Key',[$user,$key,$hash]);
            $request=self::row($pdo,'SELECT * FROM AI_SAVE_KEYS_TBL WHERE User_Id=? AND Request_Key=? FOR UPDATE',[$user,$key]);
            if (!hash_equals($request['Payload_Hash'],$hash)) throw new HttpException(409,'Esta confirmación ya tiene otro contenido.');
            $original=self::row($pdo,'SELECT * FROM VINYLS_TBL WHERE Id=? AND User_Id=? FOR UPDATE',[$vinyl,$user]);
            if (!$original) throw new HttpException(404);
            if ((int)$request['Completed']===1) { $pdo->commit();return $vinyl; }
            $copy=self::row($pdo,'SELECT * FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=? FOR UPDATE',[$vinyl,$user]);
            if (self::state($copy?:null)!==$expected) throw new HttpException(409,'La vinculación ha cambiado. Revísala antes de confirmar otra operación.');
            if ($metadata) [$album,$edition]=EncyclopediaCollection::import($pdo,$metadata);
            else {
                if (!$copy || !$copy['edition_id']) throw new HttpException(409,'No hay una edición para desvincular.');
                self::sql($pdo,'INSERT INTO ENC_ALBUMS(title,draft_owner_id) VALUES(?,?)',[$original['Title'],$user]);
                $album=(int)$pdo->lastInsertId();$edition=null;
            }
            $personal=json_decode($copy['overrides_json']??'{}',true)?:[];
            $personal['link_history'][]=['before'=>self::state($copy?:null),'after'=>$album.':'.($edition??0),'user'=>$user,'at'=>gmdate('c'),'operation'=>$metadata?'link':'detach'];
            if ($copy) self::sql($pdo,'UPDATE ENC_COPIES SET album_id=?,edition_id=?,overrides_json=? WHERE vinyl_id=? AND owner_id=?',[$album,$edition,json_encode($personal,JSON_THROW_ON_ERROR),$vinyl,$user]);
            else self::sql($pdo,'INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id,added_at,overrides_json) VALUES(?,?,?,?,NOW(),?)',[$vinyl,$user,$album,$edition,json_encode($personal,JSON_THROW_ON_ERROR)]);
            self::sql($pdo,'UPDATE AI_SAVE_KEYS_TBL SET Vinyl_Id=?,Completed=1 WHERE User_Id=? AND Request_Key=?',[$vinyl,$user,$key]);
            $pdo->commit();return $vinyl;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($error instanceof \PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true) && $attempt<2) return self::apply($vinyl,$user,$metadata,$expected,$key,$attempt+1);
            throw $error;
        }
    }
    private static function sql(PDO $pdo,string $sql,array $params): void {$pdo->prepare($sql)->execute($params);}
    private static function row(PDO $pdo,string $sql,array $params): array|false {$s=$pdo->prepare($sql);$s->execute($params);return $s->fetch(PDO::FETCH_ASSOC);}
}
