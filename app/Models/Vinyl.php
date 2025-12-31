<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class Vinyl
{
    public static function countByUser(int $userId): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM VINYLS_TBL WHERE User_Id = :uid");
        $stmt->execute(['uid' => $userId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    public static function paginateByUser(int $userId, int $limit, int $offset): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired
                FROM VINYLS_TBL
                WHERE User_Id = :uid
                ORDER BY Id DESC
                LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByIdForUser(int $id, int $userId): ?array
    {
        $pdo = Database::pdo();
        $sql = "SELECT
                    v.*,
                    g.Genre_Name AS genre_name,
                    f.Format_Name AS format_name,
                    c.Condition_Name AS condition_name,
                    rl.Record_Label_Name AS record_label_name,
                    e.Edition_Name AS edition_name,
                    a.Author_Name AS author_name
                FROM VINYLS_TBL v
                INNER JOIN GENRES_TBL g ON g.Id = v.Genres_Id
                INNER JOIN FORMAT_TBL f ON f.Id = v.Format_Id
                INNER JOIN CONDITION_TBL c ON c.Id = v.Condition_Id
                INNER JOIN RECORD_LABEL_TBL rl ON rl.Id = v.Record_Label_Id
                INNER JOIN EDITION_TBL e ON e.Id = v.Edition_Id
                LEFT JOIN AUTOR_VINYLS_TBL av ON av.Vinilo_Id = v.Id
                LEFT JOIN AUTHORS_TBL a ON a.Id = av.Autor_Id
                WHERE v.Id = :id AND v.User_Id = :uid
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listGenres(): array
    {
        return self::simpleList("GENRES_TBL", "Genre_Name");
    }

    public static function listFormats(): array
    {
        return self::simpleList("FORMAT_TBL", "Format_Name");
    }

    public static function listConditions(): array
    {
        return self::simpleList("CONDITION_TBL", "Condition_Name");
    }

    public static function listRecordLabels(): array
    {
        return self::simpleList("RECORD_LABEL_TBL", "Record_Label_Name");
    }

    public static function listEditions(): array
    {
        return self::simpleList("EDITION_TBL", "Edition_Name");
    }

    private static function simpleList(string $table, string $nameCol): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT Id, {$nameCol} AS name FROM {$table} ORDER BY {$nameCol} ASC");
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        return array_map(fn($r) => ['id' => (int)$r['Id'], 'name' => (string)$r['name']], $rows);
    }

    public static function createForUser(int $userId, array $data): int
    {
        $pdo = Database::pdo();

        try {
            $pdo->beginTransaction();

            $authorName = trim((string)($data['author'] ?? ''));
            $authorId = self::findOrCreateAuthor($authorName, $pdo);

            $sql = "INSERT INTO VINYLS_TBL
                        (User_Id, Title, Genres_Id, Format_Id, Condition_Id, Record_Label_Id, Producer, Release_date, Edition_Id, Is_Favorite, Is_Desired, Image_Path)
                    VALUES
                        (:uid, :title, :genre_id, :format_id, :condition_id, :label_id, :producer, :release_date, :edition_id, :fav, :desired, :image_path)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'uid' => $userId,
                'title' => (string)($data['title'] ?? ''),
                'genre_id' => (int)($data['genre_id'] ?? 0),
                'format_id' => (int)($data['format_id'] ?? 0),
                'condition_id' => (int)($data['condition_id'] ?? 0),
                'label_id' => (int)($data['record_label_id'] ?? 0),
                'producer' => (string)($data['producer'] ?? ''),
                'release_date' => (int)($data['release_date'] ?? 0),
                'edition_id' => (int)($data['edition_id'] ?? 0),
                'fav' => (int)($data['is_favorite'] ?? 0),
                'desired' => (int)($data['is_desired'] ?? 0),
                'image_path' => $data['image_path'] ?? null,
            ]);

            $vinylId = (int)$pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO AUTOR_VINYLS_TBL (Autor_Id, Vinilo_Id) VALUES (:aid, :vid)");
            $stmt2->execute(['aid' => $authorId, 'vid' => $vinylId]);

            $pdo->commit();
            return $vinylId;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function findOrCreateAuthor(string $authorName, PDO $pdo): int
    {
        $authorName = trim($authorName);
        if ($authorName === '') {
            return 0;
        }

        $stmt = $pdo->prepare("SELECT Id FROM AUTHORS_TBL WHERE Author_Name = :n LIMIT 1");
        $stmt->execute(['n' => $authorName]);
        $id = (int)($stmt->fetchColumn() ?: 0);
        if ($id > 0) {
            return $id;
        }

        $stmt2 = $pdo->prepare("INSERT INTO AUTHORS_TBL (Author_Name) VALUES (:n)");
        $stmt2->execute(['n' => $authorName]);
        return (int)$pdo->lastInsertId();
    }
}
