<?php
declare(strict_types=1);
namespace App\Services\Metadata;

final class MusicBrainzNormalizer
{
    public static function id(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $value)) {
            throw new MetadataException('invalid_response');
        }
        return $value;
    }

    public static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') { return null; }
        if (!is_string($value) || !preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/D', $value, $m) ||
            (int)$m[1] < 1000 || (isset($m[2]) && ((int)$m[2] < 1 || (int)$m[2] > 12)) ||
            (isset($m[3]) && !checkdate((int)$m[2], (int)$m[3], (int)$m[1]))) {
            throw new MetadataException('invalid_response');
        }
        return $value;
    }

    private static function text(mixed $value, int $max = 255): ?string
    {
        if ($value === null || $value === '') { return null; }
        if (!is_string($value) || !preg_match('//u', $value) || str_contains($value, "\0") || preg_match_all('/./us', $value) > $max) {
            throw new MetadataException('invalid_response');
        }
        return trim($value) ?: null;
    }

    private static function artist(array $artist): array
    {
        return ['id' => self::id($artist['id'] ?? null), 'name' => self::text($artist['name'] ?? null) ?? throw new MetadataException('invalid_response'),
            'kind' => match ($artist['type'] ?? '') { 'Person' => 'person', 'Group' => 'group', default => 'unknown' }];
    }

    private static function artists(array $credits): array
    {
        $artists = [];
        foreach ($credits as $credit) {
            if (is_array($credit) && isset($credit['artist'])) {
                $artists[] = self::artist($credit['artist']) + ['credited_as' => self::text($credit['name'] ?? null)];
            }
        }
        return $artists;
    }

    private static function credits(array $relations): array
    {
        $result = [];
        foreach ($relations as $relation) {
            if (!is_array($relation)) { continue; }
            $role = self::text($relation['type'] ?? null, 120);
            if (!$role) { continue; }
            if (isset($relation['artist'])) {
                $result[] = ['artist' => self::artist($relation['artist']), 'organisation' => null, 'role' => $role,
                    'credited_as' => self::text($relation['target-credit'] ?? null)];
            } elseif (isset($relation['place']['name'])) {
                $result[] = ['artist' => null, 'organisation' => self::text($relation['place']['name']), 'role' => $role, 'credited_as' => null];
            }
        }
        return $result;
    }

    public static function release(array $releaseResponse, array $groupResponse): array
    {
        $r = $releaseResponse['data']; $g = $groupResponse['data'];
        $groupId = self::id($g['id'] ?? null);
        if (($r['release-group']['id'] ?? null) !== $groupId) { throw new MetadataException('invalid_response'); }
        $album = ['id' => $groupId, 'title' => self::text($g['title'] ?? null) ?? throw new MetadataException('invalid_response'),
            'original_date' => self::date($g['first-release-date'] ?? null), 'type' => self::text($g['primary-type'] ?? null, 80),
            'artists' => self::artists($g['artist-credit'] ?? []), 'genres' => [], 'credits' => self::credits($g['relations'] ?? [])];
        foreach ($g['genres'] ?? [] as $genre) {
            if (($name = self::text($genre['name'] ?? null, 120)) !== null) { $album['genres'][] = $name; }
        }
        $album['genres'] = array_values(array_unique($album['genres']));
        $edition = ['id' => self::id($r['id'] ?? null), 'title' => self::text($r['title'] ?? null),
            'country' => self::text($r['country'] ?? null, 80), 'date' => self::date($r['date'] ?? null),
            'barcode' => self::text($r['barcode'] ?? null, 64), 'packaging' => self::text($r['packaging'] ?? null),
            'labels' => [], 'media' => [], 'credits' => self::credits($r['relations'] ?? [])];
        foreach ($r['label-info'] ?? [] as $entry) {
            if (!isset($entry['label']['id'], $entry['label']['name'])) { continue; }
            $edition['labels'][] = ['id' => self::id($entry['label']['id']), 'name' => self::text($entry['label']['name']),
                'catalogue_number' => self::text($entry['catalog-number'] ?? null, 120)];
        }
        $totalTracks = 0;
        foreach ($r['media'] ?? [] as $m) {
            $medium = ['position' => (int)($m['position'] ?? 0), 'format' => self::text($m['format'] ?? null, 80), 'tracks' => []];
            if ($medium['position'] < 1) { throw new MetadataException('invalid_response'); }
            foreach ($m['tracks'] ?? [] as $t) {
                if (++$totalTracks > 500) { throw new MetadataException('invalid_response'); }
                $rec = $t['recording'] ?? null; $recording = null;
                if (is_array($rec) && isset($rec['id'])) {
                    $recording = ['id' => self::id($rec['id']), 'title' => self::text($rec['title'] ?? null),
                        'duration_ms' => self::duration($rec['length'] ?? null), 'credits' => self::credits($rec['relations'] ?? []), 'works' => []];
                    foreach ($rec['relations'] ?? [] as $rel) {
                        if (($rel['type'] ?? '') === 'performance' && isset($rel['work']['id'])) {
                            $w = $rel['work'];
                            $recording['works'][] = ['id' => self::id($w['id']), 'title' => self::text($w['title'] ?? null), 'credits' => self::credits($w['relations'] ?? [])];
                        }
                    }
                }
                $number = self::text($t['number'] ?? null, 32);
                $medium['tracks'][] = ['position' => (int)($t['position'] ?? 0), 'number' => $number,
                    'side' => $number && preg_match('/^([A-Z]+)\d+$/D', $number, $match) ? $match[1] : null,
                    'title' => self::text($t['title'] ?? null) ?? throw new MetadataException('invalid_response'),
                    'duration_ms' => self::duration($t['length'] ?? null), 'recording' => $recording,
                    'artists' => self::artists($t['artist-credit'] ?? [])];
            }
            $edition['media'][] = $medium;
        }
        return ['album' => $album, 'edition' => $edition,
            'group_fetched_at' => (int)$groupResponse['fetched_at'], 'release_fetched_at' => (int)$releaseResponse['fetched_at']];
    }

    private static function duration(mixed $value): ?int
    {
        if ($value === null) { return null; }
        if (!is_int($value) || $value < 0 || $value > 4294967295) { throw new MetadataException('invalid_response'); }
        return $value;
    }
}
