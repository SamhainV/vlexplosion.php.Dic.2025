<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/** Explicit rehearsal only. Not called by HTTP routes or application bootstrap. */
final class LegacyEncyclopediaMigration
{
    public static function run(PDO $pdo): int
    {
        if ($pdo->inTransaction()) {
            throw new RuntimeException('Migration requires its own transaction');
        }
        // This service must remain restricted until production deployment is reviewed.
        if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'vlexplosion_test') {
            throw new RuntimeException('Only the isolated fixture database is permitted');
        }
        $server = $pdo->query('SELECT @@datadir AS dir,@@socket AS socket,@@skip_networking AS isolated')->fetch(PDO::FETCH_ASSOC);
        $directory = realpath($server['dir']);
        $runtime = realpath(dirname(__DIR__, 2) . '/tests/runtime');
        if (!$directory || !$runtime || dirname($directory) !== $runtime ||
            !preg_match('/^db-enc-[a-f0-9]{8}$/D', basename($directory)) ||
            realpath($server['socket']) !== $directory . '/s.sock' || (int)$server['isolated'] !== 1) {
            throw new RuntimeException('Only the verified isolated rehearsal server is permitted');
        }
        $count = 0;
        $pdo->beginTransaction();
        try {
            // Lock originals: repeating a completed rehearsal does not duplicate copies.
            $rows = $pdo->query('SELECT v.* FROM VINYLS_TBL v ORDER BY v.Id FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
            $exists = $pdo->prepare('SELECT vinyl_id FROM ENC_COPIES WHERE vinyl_id=?');
            $album = $pdo->prepare('INSERT INTO ENC_ALBUMS(title,draft_owner_id,legacy_vinyl_id) VALUES(?,?,?)');
            $copy = $pdo->prepare('INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,legacy_snapshot) VALUES(?,?,?,?)');
            $authors = $pdo->prepare('SELECT a.Id,a.Author_Name FROM AUTOR_VINYLS_TBL av JOIN AUTHORS_TBL a ON a.Id=av.Autor_Id WHERE av.Vinilo_Id=? ORDER BY a.Id');
            foreach ($rows as $row) {
                $exists->execute([$row['Id']]);
                if ($exists->fetchColumn() !== false) {
                    continue;
                }
                $authors->execute([$row['Id']]);
                $snapshot = ['vinyl' => $row, 'authors' => $authors->fetchAll(PDO::FETCH_ASSOC)];
                // Release_date may describe an edition rather than original release.
                // No inferred original date, artist identity or exact edition is published.
                $album->execute([$row['Title'], $row['User_Id'], $row['Id']]);
                $albumId = (int)$pdo->lastInsertId();
                $copy->execute([$row['Id'], $row['User_Id'], $albumId, json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                $count++;
            }
            $pdo->commit();
            return $count;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
