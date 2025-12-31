<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }


        $config = require __DIR__ . '/../Config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            (int)($config['port'] ?? 3306),
            $config['name']
        );


        try {
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo "DB connection error.";
            echo "<pre>DB connection error:\n" . htmlspecialchars($e->getMessage()) . "</pre>";

            // You can log $e->getMessage() to storage if you want
            exit;
        }

        return self::$pdo;
    }
}
