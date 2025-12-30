<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public static function findByUsernameOrEmail(string $login): ?array
    {
        $pdo = Database::pdo();
        $sql = "SELECT id, username, password, email
            FROM Users_TBL
            WHERE username = :u OR email = :e
            LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'u' => $login,
            'e' => $login,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}
