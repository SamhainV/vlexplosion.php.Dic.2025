<?php
declare(strict_types=1);
namespace App\Services\Recognition;

use App\Core\{HttpException, Input};

/** Future visual providers may only propose search text; they cannot import records. */
final class PhotoSearchProposal
{
    public static function confirmedQuery(array $candidate, bool $confirmed): array
    {
        if (!$confirmed) { throw new HttpException(422, 'Confirma el candidato visual antes de buscar.'); }
        $artist = Input::text($candidate['artist'] ?? '', 100);
        $title = Input::text($candidate['title'] ?? '', 120);
        if ($artist === '' && $title === '') { throw new HttpException(422, 'No hay texto musical suficiente para buscar.'); }
        // Discard all purported IDs, editions, credits and dates from the visual model.
        return ['operation' => 'search', 'artist' => $artist, 'title' => $title];
    }

    public static function available(): bool { return false; }
}
