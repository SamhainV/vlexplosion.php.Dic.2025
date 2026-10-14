<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class EncyclopediaRecord
{
    private static function rows(string $sql, array $args): array
    {
        $s = Database::pdo()->prepare($sql); $s->execute($args); return $s->fetchAll();
    }

    public static function creditsFor(int $albumId, ?int $editionId): array
    {
        if (!$editionId) return self::rows('SELECT c.*,a.name,NULL AS track_title,NULL AS recording_title,NULL AS work_title FROM ENC_CREDITS c LEFT JOIN ENC_ARTISTS a ON a.id=c.artist_id WHERE c.album_id=?',[$albumId]);
        return self::rows('SELECT DISTINCT c.*,a.name,t.title AS track_title,r.title AS recording_title,w.title AS work_title FROM ENC_CREDITS c LEFT JOIN ENC_ARTISTS a ON a.id=c.artist_id LEFT JOIN ENC_TRACKS t ON t.id=c.track_id LEFT JOIN ENC_RECORDINGS r ON r.id=c.recording_id LEFT JOIN ENC_WORKS w ON w.id=c.work_id WHERE c.album_id=? OR c.edition_id=? OR c.track_id IN (SELECT t.id FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?) OR c.recording_id IN (SELECT t.recording_id FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?) OR c.work_id IN (SELECT rw.work_id FROM ENC_RECORDING_WORKS rw JOIN ENC_TRACKS t ON t.recording_id=rw.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?) ORDER BY c.role,a.name,c.id', [$albumId, $editionId, $editionId, $editionId, $editionId]);
    }

    public static function sourcesFor(int $albumId,?int $editionId): array
    {
        $ids=array_values(array_unique(array_filter(array_column(self::creditsFor($albumId,$editionId),'source_id'))));
        $placeholders=$ids?implode(',',array_fill(0,count($ids),'?')):'NULL';
        return self::rows('SELECT DISTINCT s.provider,s.source_url,s.fetched_at FROM ENC_SOURCES s WHERE s.id IN ('.$placeholders.') OR s.id IN (SELECT source_id FROM ENC_ASSERTIONS WHERE album_id=? OR edition_id=?) OR (s.provider="musicbrainz" AND (s.external_id IN (SELECT musicbrainz_group_id FROM ENC_ALBUMS WHERE id=?) OR s.external_id IN (SELECT musicbrainz_release_id FROM ENC_EDITIONS WHERE id=?))) ORDER BY s.fetched_at', [...$ids,$albumId,$editionId,$albumId,$editionId]);
    }

    public static function forCopy(int $id, int $user): ?array
    {
        $vinyl = Vinyl::findByIdForUser($id, $user);
        if (!$vinyl) { return null; }
        $copy = self::rows('SELECT * FROM ENC_COPIES WHERE vinyl_id=? AND owner_id=?', [$id, $user])[0] ?? null;
        if (!$copy) {
            // Read-only legacy view: no inferred identity and no backfill of real records.
            return ['vinyl'=>$vinyl, 'copy'=>['notes'=>null,'overrides_json'=>null],
                'album'=>['id'=>null,'title'=>$vinyl['Title'],'original_date'=>null,'musicbrainz_group_id'=>null],
                'edition'=>null,'media'=>[],'artists'=>[],'genres'=>[],'labels'=>[],'tracks'=>[],'credits'=>[], 'legacy'=>true];
        }
        $album = self::rows('SELECT * FROM ENC_ALBUMS WHERE id=? AND (draft_owner_id IS NULL OR draft_owner_id=?)', [$copy['album_id'], $user])[0] ?? null;
        if (!$album) { return null; }
        $editionId = $copy['edition_id'];
        $edition = $editionId ? (self::rows('SELECT * FROM ENC_EDITIONS WHERE id=? AND album_id=?', [$editionId, $album['id']])[0] ?? null) : null;
        return ['vinyl' => $vinyl, 'copy' => $copy, 'album' => $album, 'edition' => $edition,
            'artists' => self::rows('SELECT a.* FROM ENC_ALBUM_ARTISTS aa JOIN ENC_ARTISTS a ON a.id=aa.artist_id WHERE aa.album_id=? ORDER BY aa.position', [$album['id']]),
            'genres' => self::rows('SELECT t.id,t.kind,t.name FROM ENC_ALBUM_TERMS at JOIN ENC_TERMS t ON t.id=at.term_id WHERE at.album_id=? ORDER BY t.name', [$album['id']]),
            'labels' => $editionId ? self::rows('SELECT l.id,l.name,el.catalogue_number FROM ENC_EDITION_LABELS el JOIN ENC_LABELS l ON l.id=el.label_id WHERE el.edition_id=? ORDER BY l.name', [$editionId]) : [],
            'media' => $editionId ? self::rows('SELECT * FROM ENC_MEDIA WHERE edition_id=? ORDER BY position', [$editionId]) : [],
            'tracks' => $editionId ? self::rows('SELECT t.*,m.position AS medium_position,m.format FROM ENC_TRACKS t JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=? ORDER BY m.position,t.position', [$editionId]) : [],
            'recordings'=>$editionId?self::rows('SELECT DISTINCT r.* FROM ENC_RECORDINGS r JOIN ENC_TRACKS t ON t.recording_id=r.id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?',[$editionId]):[],
            'works'=>$editionId?self::rows('SELECT DISTINCT w.*,rw.recording_id FROM ENC_WORKS w JOIN ENC_RECORDING_WORKS rw ON rw.work_id=w.id JOIN ENC_TRACKS t ON t.recording_id=rw.recording_id JOIN ENC_MEDIA m ON m.id=t.medium_id WHERE m.edition_id=?',[$editionId]):[],
            'sources'=>self::sourcesFor((int)$album['id'],$editionId?(int)$editionId:null),
            'credits' => self::creditsFor((int)$album['id'], $editionId ? (int)$editionId : null),
        ];
    }
}
