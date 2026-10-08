<?php
declare(strict_types=1);
namespace App\Core;
final class VinylInput
{
    public static function validate(array $input, ?int $currentYear = null): array
    {
        $data = [];
        foreach (['title' => 65, 'producer' => 50] as $field => $max) {
            $data[$field] = Input::text($input[$field] ?? null, $max);
            if ($data[$field] === '') { throw new HttpException(422, 'Rellena todos los campos obligatorios.'); }
        }
        $authors = $input['authors'] ?? [$input['author'] ?? ''];
        if (!is_array($authors) || count($authors) < 1 || count($authors) > 20) { throw new HttpException(422, 'Indica entre uno y veinte autores.'); }
        $names = [];
        foreach ($authors as $author) {
            $name = Input::text($author, 50);
            if ($name === '') { throw new HttpException(422, 'El autor es obligatorio.'); }
            $names[] = $name;
        }
        $data['authors'] = array_values(array_unique($names));
        $data['author'] = $data['authors'][0];
        foreach (['genre_id', 'format_id', 'condition_id', 'record_label_id', 'edition_id'] as $field) {
            $data[$field] = Input::integer($input[$field] ?? null, 1);
            if ($data[$field] < 1) { throw new HttpException(422, 'Selecciona todos los catálogos.'); }
        }
        $year = Input::integer($input['release_date'] ?? null, 1901);
        if ($year < 1901 || $year > min(2155, ($currentYear ?? (int)date('Y')) + 1)) {
            throw new HttpException(422, 'El año no es válido.');
        }
        $data['release_date'] = $year;
        $data['is_favorite'] = Input::flag($input['is_favorite'] ?? null);
        $data['is_desired'] = Input::flag($input['is_desired'] ?? null);
        return $data;
    }
    public static function validateCatalogues(array $data, array $catalogues): void
    {
        foreach ($catalogues as $field => $rows) {
            if (!in_array($data[$field], array_map(static fn(array $row): int => (int)$row['id'], $rows), true)) {
                throw new HttpException(422, 'Una selección del catálogo no es válida.');
            }
        }
    }
}
