<?php
declare(strict_types=1);
namespace App\Core;
final class Input
{
    public static function text(mixed $value, int $max, string $default = ''): string
    {
        if ($value === null) { return $default; }
        if (!is_string($value) || !preg_match('//u', $value) || str_contains($value, "\0")) {
            throw new HttpException(422, 'Texto no válido.');
        }
        $value = trim($value);
        if (preg_match_all('/./us', $value) > $max) {
            throw new HttpException(422, 'El texto supera la longitud permitida.');
        }
        return $value;
    }
    public static function integer(mixed $value, int $min = 0, int $default = 0): int
    {
        if ($value === null) { return $default; }
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[0-9]+$/D', (string)$value)) {
            throw new HttpException(422, 'Número no válido.');
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => 2147483647]]);
        if ($number === false) { throw new HttpException(422, 'Número fuera de rango.'); }
        return $number;
    }
    public static function flag(mixed $value): int
    {
        if ($value === null || $value === '0') { return 0; }
        if ($value === 'on' || $value === '1') { return 1; }
        throw new HttpException(422, 'Marca no válida.');
    }
    public static function password(mixed $value): string
    {
        if (!is_string($value) || strlen($value) > 4096 || str_contains($value, "\0")) {
            throw new HttpException(422, 'Contraseña no válida.');
        }
        return $value; // Do not trim or alter passwords.
    }
}
