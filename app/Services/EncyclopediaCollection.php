<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Database, HttpException, VinylInput};
use App\Models\Vinyl;
use PDO;

/** Manual and assisted creation use the same legacy writer and transaction. */
final class EncyclopediaCollection
{
    public static function create(int $user, array $data, ?array $metadata = null, ?string $key = null, array $personal = []): int
    {
        $pdo = Database::pdo();
        self::isolation($pdo);
        foreach (['is_favorite', 'is_desired'] as $flag) { if (isset($data[$flag]) && is_int($data[$flag])) { $data[$flag] = (string)$data[$flag]; } }
        $validated = VinylInput::validate($data, null, true);
        $validated['image_path'] = $data['image_path'] ?? null;
        $key ??= bin2hex(random_bytes(32));
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) { throw new HttpException(422, 'Confirmación inválida.'); }
        $hash = hash('sha256', json_encode([$validated, $metadata, $personal], JSON_THROW_ON_ERROR));
        if ($pdo->inTransaction()) { throw new \RuntimeException('Collection service owns its transaction'); }
        $pdo->beginTransaction();
        try {
            self::sql($pdo, 'INSERT INTO AI_SAVE_KEYS_TBL(User_Id,Request_Key,Payload_Hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE Request_Key=Request_Key', [$user, $key, $hash]);
            $request = self::row($pdo, 'SELECT * FROM AI_SAVE_KEYS_TBL WHERE User_Id=? AND Request_Key=? FOR UPDATE', [$user, $key]);
            if (!hash_equals($request['Payload_Hash'], $hash)) { throw new HttpException(409, 'La confirmación ya se utilizó con otros datos. Inicia otra alta.'); }
            if ($request['Vinyl_Id'] !== null) { $pdo->commit(); return (int)$request['Vinyl_Id']; }
            if ((int)$request['Completed'] === 1) { throw new HttpException(409, 'Este ejemplar ya fue eliminado. Inicia otra alta.'); }
            $editionId = null;
            if ($metadata !== null) {
                [$albumId, $editionId] = self::import($pdo, $metadata);
            } else {
                self::sql($pdo, 'INSERT INTO ENC_ALBUMS(title,draft_owner_id) VALUES(?,?)', [$validated['title'], $user]);
                $albumId = (int)$pdo->lastInsertId();
            }
            // Nested Vinyl writer honours the outer transaction and retains all author handling.
            $id = Vinyl::createForUser($user, $validated);
            self::sql($pdo, 'INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id,notes,added_at,overrides_json) VALUES(?,?,?,?,?,NOW(),?)',
                [$id, $user, $albumId, $editionId, $personal['notes'] ?? null, json_encode($personal, JSON_THROW_ON_ERROR)]);
            if ($metadata !== null && $validated['title'] !== $metadata['album']['title']) {
                self::sql($pdo, "INSERT INTO ENC_ASSERTIONS(album_id,field_name,value_json,author_user_id,status,manually_locked) VALUES(?,'title',?,?,'proposed',1)",
                    [$albumId, json_encode($validated['title'], JSON_THROW_ON_ERROR), $user]);
            }
            self::sql($pdo, 'UPDATE AI_SAVE_KEYS_TBL SET Vinyl_Id=?,Completed=1 WHERE User_Id=? AND Request_Key=?', [$id, $user, $key]);
            $pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $error;
        }
    }

    public static function isolation(PDO $pdo): void
    {
        $r = $pdo->query('SELECT DATABASE() AS db,@@datadir AS dir,@@skip_networking AS isolated')->fetch(PDO::FETCH_ASSOC);
        if (!(defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true)) {
            if (!\App\Core\Encyclopedia::enabled() || empty($_ENV['DB_NAME']) || $r['db'] !== $_ENV['DB_NAME']) {
                throw new \RuntimeException('Encyclopedia writes require explicit activation and the configured database');
            }
            return;
        }
        $dir = realpath($r['dir']); $root = realpath(dirname(__DIR__, 2) . '/tests/runtime');
        if ($r['db'] !== 'vlexplosion_test' || !$dir || dirname($dir) !== $root || !preg_match('/^db-enc-[a-f0-9]{8}$/D', basename($dir)) || (int)$r['isolated'] !== 1) {
            throw new \RuntimeException('Only isolated encyclopedia development database permitted');
        }
    }

    public static function import(PDO $pdo, array $m): array
    {
        if (!$pdo->inTransaction()) throw new \RuntimeException('Import requires an authorized collection transaction');
        $a = $m['album']; $e = $m['edition'];
        $sourceAlbum = self::source($pdo, 'release-group', $a['id'], $m['group_fetched_at']);
        $sourceEdition = self::source($pdo, 'release', $e['id'], $m['release_fetched_at']);
        self::sql($pdo, 'INSERT INTO ENC_ALBUMS(title,original_date,release_type,musicbrainz_group_id) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$a['title'], $a['original_date'], $a['type'], $a['id']]);
        $albumId = (int)$pdo->lastInsertId();
        $album = self::row($pdo, 'SELECT * FROM ENC_ALBUMS WHERE id=? FOR UPDATE', [$albumId]);
        if ($album['draft_owner_id'] !== null) { throw new HttpException(409, 'La identidad corresponde a un borrador privado pendiente de revisión.'); }
        if ($album['imported_at'] === null) {
            foreach ($a['artists'] as $position => $artist) {
                self::sql($pdo, 'INSERT INTO ENC_ALBUM_ARTISTS(album_id,artist_id,position,credited_as) VALUES(?,?,?,?)', [$albumId, self::artist($pdo, $artist), $position + 1, $artist['credited_as']]);
            }
            foreach ($a['genres'] as $genre) {
                self::sql($pdo, "INSERT INTO ENC_TERMS(kind,name) VALUES('genre',?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)", [$genre]);
                self::sql($pdo, 'INSERT INTO ENC_ALBUM_TERMS VALUES(?,?)', [$albumId, (int)$pdo->lastInsertId()]);
            }
            self::credits($pdo, 'album_id', $albumId, $a['credits'], $sourceAlbum);
            foreach (['title', 'original_date', 'genres'] as $field) {
                self::assertion($pdo, 'album_id', $albumId, $field, $a[$field], $sourceAlbum, 'accepted');
            }
            self::sql($pdo, 'UPDATE ENC_ALBUMS SET imported_at=NOW() WHERE id=?', [$albumId]);
        } elseif ($album['title'] !== $a['title']) {
            self::assertion($pdo, 'album_id', $albumId, 'title', $a['title'], $sourceAlbum, 'conflict');
        }
        self::sql($pdo, 'INSERT INTO ENC_EDITIONS(album_id,country,release_date,barcode,packaging,musicbrainz_release_id) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$albumId, $e['country'], $e['date'], $e['barcode'], $e['packaging'], $e['id']]);
        $editionId = (int)$pdo->lastInsertId();
        $edition = self::row($pdo, 'SELECT * FROM ENC_EDITIONS WHERE id=? FOR UPDATE', [$editionId]);
        if ((int)$edition['album_id'] !== $albumId) { throw new HttpException(409, 'La edición está relacionada con otro álbum.'); }
        if ($edition['imported_at'] === null) {
            foreach ($e['labels'] as $label) {
                self::sql($pdo, 'INSERT INTO ENC_LABELS(name,musicbrainz_id) VALUES(?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$label['name'], $label['id']]);
                self::sql($pdo, 'INSERT INTO ENC_EDITION_LABELS VALUES(?,?,?) ON DUPLICATE KEY UPDATE catalogue_number=VALUES(catalogue_number)', [$editionId, (int)$pdo->lastInsertId(), $label['catalogue_number'] ?? '']);
            }
            self::credits($pdo, 'edition_id', $editionId, $e['credits'], $sourceEdition);
            foreach ($e['media'] as $medium) {
                self::sql($pdo, 'INSERT INTO ENC_MEDIA(edition_id,position,format) VALUES(?,?,?)', [$editionId, $medium['position'], $medium['format']]);
                $mediumId = (int)$pdo->lastInsertId();
                foreach ($medium['tracks'] as $track) {
                    $recordingId = null;
                    if ($track['recording']) {
                        $rec = $track['recording'];
                        self::sql($pdo, 'INSERT INTO ENC_RECORDINGS(title,duration_ms,musicbrainz_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$rec['title'], $rec['duration_ms'], $rec['id']]);
                        $recordingId = (int)$pdo->lastInsertId();
                        self::credits($pdo, 'recording_id', $recordingId, $rec['credits'], $sourceEdition);
                        foreach ($rec['works'] as $work) {
                            self::sql($pdo, 'INSERT INTO ENC_WORKS(title,musicbrainz_id) VALUES(?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$work['title'], $work['id']]);
                            $workId = (int)$pdo->lastInsertId();
                            self::sql($pdo, 'INSERT INTO ENC_RECORDING_WORKS VALUES(?,?) ON DUPLICATE KEY UPDATE work_id=VALUES(work_id)', [$recordingId, $workId]);
                            self::credits($pdo, 'work_id', $workId, $work['credits'], $sourceEdition);
                        }
                    }
                    self::sql($pdo, 'INSERT INTO ENC_TRACKS(medium_id,position,printed_number,side,title,duration_ms,recording_id) VALUES(?,?,?,?,?,?,?)', [$mediumId, $track['position'], $track['number'], $track['side'], $track['title'], $track['duration_ms'], $recordingId]);
                    $trackId = (int)$pdo->lastInsertId();
                    foreach ($track['artists'] as $artist) {
                        self::credits($pdo, 'track_id', $trackId, [['artist' => $artist, 'organisation' => null, 'role' => 'artist credit', 'credited_as' => $artist['credited_as']]], $sourceEdition);
                    }
                }
            }
            foreach (['country', 'date', 'barcode'] as $field) { self::assertion($pdo, 'edition_id', $editionId, $field, $e[$field], $sourceEdition, 'accepted'); }
            self::sql($pdo, 'UPDATE ENC_EDITIONS SET imported_at=NOW() WHERE id=?', [$editionId]);
        }
        return [$albumId, $editionId];
    }

    private static function artist(PDO $pdo, array $a): int
    {
        self::sql($pdo, 'INSERT INTO ENC_ARTISTS(name,kind,musicbrainz_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)', [$a['name'], $a['kind'], $a['id']]);
        return (int)$pdo->lastInsertId();
    }

    private static function credits(PDO $pdo, string $target, int $id, array $credits, int $source): void
    {
        $rich = EncyclopediaCreditSync::available($pdo);
        foreach ($credits as $c) {
            $artist = $c['artist'] ? self::artist($pdo, $c['artist']) : null;
            $key = hash('sha256', json_encode([$target, $id, $artist, $c['organisation'], $c['role'], $c['credited_as']], JSON_THROW_ON_ERROR));
            self::sql($pdo, "INSERT INTO ENC_CREDITS($target,artist_id,organisation,role,credited_as,source_id,credit_key) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE credit_key=VALUES(credit_key)", [$id, $artist, $c['organisation'], $c['role'], $c['credited_as'], $source, $key]);
            if($rich) self::sql($pdo,'UPDATE ENC_CREDITS SET instrument=?,relation_json=? WHERE credit_key=? AND source_id=? AND relation_json IS NULL',[$c['instrument']??null,isset($c['relation'])?json_encode($c['relation'],JSON_THROW_ON_ERROR):null,$key,$source]);
        }
    }

    private static function source(PDO $pdo, string $type, string $id, int $time): int
    {
        self::sql($pdo, "INSERT INTO ENC_SOURCES(provider,entity_type,external_id,source_url,fetched_at,license_note) VALUES('musicbrainz',?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",
            [$type, $id, 'https://musicbrainz.org/' . $type . '/' . $id, gmdate('Y-m-d H:i:s', $time), 'MusicBrainz: core CC0; supplementary CC BY-NC-SA 3.0']);
        return (int)$pdo->lastInsertId();
    }

    private static function assertion(PDO $pdo, string $target, int $id, string $field, mixed $value, int $source, string $status): void
    {
        if ($value === null || $value === []) { return; }
        $json = json_encode($value, JSON_THROW_ON_ERROR);
        $exists = self::row($pdo, "SELECT id FROM ENC_ASSERTIONS WHERE $target=? AND field_name=? AND source_id=? AND value_json=? AND status=? LIMIT 1", [$id, $field, $source, $json, $status]);
        if (!$exists) { self::sql($pdo, "INSERT INTO ENC_ASSERTIONS($target,field_name,value_json,source_id,status) VALUES(?,?,?,?,?)", [$id, $field, $json, $source, $status]); }
    }

    private static function sql(PDO $pdo, string $sql, array $params): void { $pdo->prepare($sql)->execute($params); }
    private static function row(PDO $pdo, string $sql, array $params): array|false { $s = $pdo->prepare($sql); $s->execute($params); return $s->fetch(PDO::FETCH_ASSOC); }
}
