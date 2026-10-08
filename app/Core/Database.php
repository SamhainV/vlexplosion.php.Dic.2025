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


        if ((defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true) || ($_ENV['DB_ENABLED'] ?? '0') !== '1') {
            throw new \RuntimeException('Database access disabled');
        }
        $config = require __DIR__ . '/../Config/database.php';
        if ($config['name'] === '' || $config['user'] === '') {
            throw new \RuntimeException('Database configuration incomplete');
        }

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
            throw new \RuntimeException('Database unavailable', 0, $e);
        }

        return self::$pdo;
    }
}
