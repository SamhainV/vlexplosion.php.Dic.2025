<?php
declare(strict_types=1);
// Test doubles only: deliberately never load real models or PDO.
namespace App\Models;
final class User
{
    public static function findByUsernameOrEmail(string $login): ?array
    {
        if ($login === 'database-error') { throw new \RuntimeException('PRIVATE_SQL_PASSWORD_MARKER'); }
        if ($login !== 'fictional-user') { return null; }
        return ['id' => 7, 'username' => $login, 'email' => 'fiction@example.invalid', 'password' => password_hash('fictional-password', PASSWORD_BCRYPT)];
    }
}
final class Vinyl
{
    public static function allowedSorts(): array { return ['newest' => 'Nuevos primero']; }
    public static function listGenres(): array { return [['id' => 1, 'name' => 'Fiction']]; }
    public static function listFormats(): array { return self::listGenres(); }
    public static function listConditions(): array { return self::listGenres(); }
    public static function listRecordLabels(): array { return self::listGenres(); }
    public static function listEditions(): array { return self::listGenres(); }
    public static function countByUser(int $id): int { return 1; }
    public static function paginateByUser(int $id, int $limit, int $offset, string $sort): array
    {
        return [['Id' => 1, 'Title' => 'Fiction', 'Producer' => 'Fiction', 'Release_date' => '2020', 'Image_Path' => null]];
    }
    public static function createForUser(int $id, array $data): int { throw new \LogicException('MUTATION_REACHED'); }
    public static function deleteForUser(int $id, int $user): bool { throw new \LogicException('MUTATION_REACHED'); }
}
