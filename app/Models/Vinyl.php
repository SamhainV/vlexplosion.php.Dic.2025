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


    public static function paginateByUser(int $userId, int $limit, int $offset, string $sort = 'newest'): array
    {
        $pdo = Database::pdo();
        $order = self::orderByForSort($sort);

        $sql = "SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired
            FROM VINYLS_TBL
            WHERE User_Id = :uid
            ORDER BY $order
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


    public static function pageForIdByUser(int $userId, int $vinylId, int $perPage, string $sort = 'newest'): int
    {
        $pdo = Database::pdo();

        // Datos del vinilo recién creado (para comparaciones)
        $stmt = $pdo->prepare("
        SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired
        FROM VINYLS_TBL
        WHERE User_Id = :uid AND Id = :id
        LIMIT 1
    ");
        $stmt->execute(['uid' => $userId, 'id' => $vinylId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return 1;
        }

        $id = (int)$row['Id'];
        $title = (string)$row['Title'];
        $producer = (string)$row['Producer'];
        $year = (int)$row['Release_date'];
        $fav = (int)$row['Is_Favorite'];
        $desired = (int)$row['Is_Desired'];

        // Whitelist
        $allowed = self::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        // Construimos condición "cuántos van antes" según el sort
        $where = '';
        $params = ['uid' => $userId];

        switch ($sort) {
            case 'oldest': // Id ASC
                $where = "Id < :id";
                $params['id'] = $id;
                break;

            case 'title_asc': // Title ASC, Id DESC
                $where = "(Title < :t) OR (Title = :t AND Id > :id)";
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'title_desc': // Title DESC, Id DESC
                $where = "(Title > :t) OR (Title = :t AND Id > :id)";
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'year_asc': // Release_date ASC, Title ASC, Id DESC
                $where = "(Release_date < :y)
                   OR (Release_date = :y AND Title < :t)
                   OR (Release_date = :y AND Title = :t AND Id > :id)";
                $params['y'] = $year;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'year_desc': // Release_date DESC, Title ASC, Id DESC
                $where = "(Release_date > :y)
                   OR (Release_date = :y AND Title < :t)
                   OR (Release_date = :y AND Title = :t AND Id > :id)";
                $params['y'] = $year;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'producer_asc': // Producer ASC, Title ASC, Id DESC
                $where = "(Producer < :p)
                   OR (Producer = :p AND Title < :t)
                   OR (Producer = :p AND Title = :t AND Id > :id)";
                $params['p'] = $producer;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'producer_desc': // Producer DESC, Title ASC, Id DESC
                $where = "(Producer > :p)
                   OR (Producer = :p AND Title < :t)
                   OR (Producer = :p AND Title = :t AND Id > :id)";
                $params['p'] = $producer;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'fav_first': // Is_Favorite DESC, Id DESC
                $where = "(Is_Favorite > :f) OR (Is_Favorite = :f AND Id > :id)";
                $params['f'] = $fav;
                $params['id'] = $id;
                break;

            case 'desired_first': // Is_Desired DESC, Id DESC
                $where = "(Is_Desired > :d) OR (Is_Desired = :d AND Id > :id)";
                $params['d'] = $desired;
                $params['id'] = $id;
                break;

            case 'fav_then_title': // Is_Favorite DESC, Title ASC, Id DESC
                $where = "(Is_Favorite > :f)
                   OR (Is_Favorite = :f AND Title < :t)
                   OR (Is_Favorite = :f AND Title = :t AND Id > :id)";
                $params['f'] = $fav;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'desired_then_title': // Is_Desired DESC, Title ASC, Id DESC
                $where = "(Is_Desired > :d)
                   OR (Is_Desired = :d AND Title < :t)
                   OR (Is_Desired = :d AND Title = :t AND Id > :id)";
                $params['d'] = $desired;
                $params['t'] = $title;
                $params['id'] = $id;
                break;

            case 'newest':
            default: // Id DESC
                $where = "Id > :id";
                $params['id'] = $id;
                break;
        }

        $sql = "SELECT COUNT(*)
            FROM VINYLS_TBL
            WHERE User_Id = :uid AND ($where)";

        $stmt2 = $pdo->prepare($sql);

        // FILTRAR params => solo los que realmente aparecen en el SQL
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $m);
        $need = array_unique($m[1]); // nombres sin ':'

        $exec = [];
        foreach ($need as $k) {
            if (!array_key_exists($k, $params)) {
                throw new \RuntimeException("Falta parámetro :$k en pageForIdByUser()");
            }
            $exec[$k] = $params[$k];
        }

        $stmt2->execute($exec);



        $countBefore = (int)$stmt2->fetchColumn();
        $position = $countBefore + 1;

        return (int)ceil($position / max(1, $perPage));
    }

    public static function allowedSorts(): array
    {
        return [
            'newest'        => 'Nuevos primero',
            'oldest'        => 'Antiguos primero',
            'title_asc'     => 'Título A → Z',
            'title_desc'    => 'Título Z → A',
            'year_asc'      => 'Año ↑',
            'year_desc'     => 'Año ↓',
            'producer_asc'  => 'Producer A → Z',
            'producer_desc' => 'Producer Z → A',
            'fav_first'     => 'Favoritos primero',
            'desired_first' => 'Deseados primero',
            'fav_then_title' => 'Fav primero + A→Z',
            'desired_then_title' => 'Deseado primero + A→Z',
        ];
    }

    private static function orderByForSort(string $sort): string
    {
        // Orden estable: siempre acabamos con Title e Id para estabilidad
        // (así no “baila” la lista entre recargas)
        return match ($sort) {
            'oldest'        => 'Id ASC',
            'title_asc'     => 'Title ASC, Id DESC',
            'title_desc'    => 'Title DESC, Id DESC',
            'year_asc'      => 'Release_date ASC, Title ASC, Id DESC',
            'year_desc'     => 'Release_date DESC, Title ASC, Id DESC',
            'producer_asc'  => 'Producer ASC, Title ASC, Id DESC',
            'producer_desc' => 'Producer DESC, Title ASC, Id DESC',

            // primero los 1 (true), luego los 0
            'fav_first'     => 'Is_Favorite DESC, Id DESC',
            'desired_first' => 'Is_Desired DESC, Id DESC',

            // “bonitos” (primero flag y luego alfabético)
            'fav_then_title'        => 'Is_Favorite DESC, Title ASC, Id DESC',
            'desired_then_title'    => 'Is_Desired DESC, Title ASC, Id DESC',

            default         => 'Id DESC', // newest
        };
    }
}
