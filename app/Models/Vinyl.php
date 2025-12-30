<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Vinyl
{
    public static function countByUser(int $userId): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM VINYLS_TBL WHERE User_Id = :uid");
        $stmt->execute(['uid' => $userId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    /**
     * Basic listing for the logged user.
     * Later we can join AUTHORS_TBL, GENRES_TBL, etc.
     */
    public static function paginateByUser(int $userId, int $limit, int $offset): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired, Image_Path
                FROM VINYLS_TBL
                WHERE User_Id = :uid
                ORDER BY Id DESC
                LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByIdForUser(int $vinylId, int $userId): ?array
    {
        $pdo = Database::pdo();
        $sql = "SELECT Id, Title, Producer, Release_date, Is_Favorite, Is_Desired, Image_Path
            FROM VINYLS_TBL
            WHERE Id = :id AND User_Id = :uid
            LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $vinylId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
