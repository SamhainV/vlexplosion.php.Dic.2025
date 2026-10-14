<?php
declare(strict_types=1);
// Only loaded by explicit development fixture mode. Never represents authentic music.
function encyclopedia_fixture_id(int $number): string { return sprintf('00000000-0000-4000-8000-%012d', $number); }
function encyclopedia_fixture_data(): array
{
    $artist = ['id' => encyclopedia_fixture_id(3), 'name' => 'Fictional band', 'type' => 'Group'];
    $composer = ['id' => encyclopedia_fixture_id(4), 'name' => 'Fictional composer', 'type' => 'Person'];
    $group = ['id' => encyclopedia_fixture_id(1), 'title' => 'Fictional album', 'first-release-date' => '1984', 'primary-type' => 'Album',
        'artist-credit' => [['name' => 'Fictional band', 'artist' => $artist]], 'genres' => [['name' => 'Fictional genre']], 'relations' => []];
    $release = ['id' => encyclopedia_fixture_id(2), 'title' => 'Fictional album', 'release-group' => $group,
        'country' => 'GB', 'date' => '1984', 'barcode' => '001234', 'artist-credit' => $group['artist-credit'],
        'label-info' => [['label' => ['id' => encyclopedia_fixture_id(5), 'name' => 'Fictional label'], 'catalog-number' => 'TEST 19']],
        'media' => [['position' => 1, 'format' => '12" Vinyl', 'track-count' => 1, 'tracks' => [
            ['position' => 1, 'number' => 'A1', 'title' => 'Fictional song', 'length' => 123000, 'artist-credit' => $group['artist-credit'],
                'recording' => ['id' => encyclopedia_fixture_id(6), 'title' => 'Fictional song', 'length' => 123000, 'relations' => [
                    ['type' => 'producer', 'artist' => $composer],
                    ['type' => 'performance', 'work' => ['id' => encyclopedia_fixture_id(7), 'title' => 'Fictional work', 'relations' => [['type' => 'composer', 'artist' => $composer]]]],
                ]]],
        ]]], 'relations' => []];
    return [$group, $release];
}
function encyclopedia_fixture_response(string $url, string $agent): array
{
    [$group, $release] = encyclopedia_fixture_data();
    $path = parse_url($url, PHP_URL_PATH); parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
    $referenceGroup=json_decode(file_get_contents(__DIR__.'/reference/enjoy-group.json'),true)['data'];
    $referenceRelease=json_decode(file_get_contents(__DIR__.'/reference/enjoy-release.json'),true)['data'];
    if (str_contains($query['query']??'','Enjoy the Silence') || ($query['release-group']??'')===$referenceGroup['id'] || in_array($path,['/ws/2/release-group/'.$referenceGroup['id'],'/ws/2/release/'.$referenceRelease['id']],true)) {
        $data=match($path) {
            '/ws/2/release-group'=>['release-groups'=>[$referenceGroup],'count'=>1],
            '/ws/2/release'=>['releases'=>[$referenceRelease],'count'=>1,'release-count'=>1],
            '/ws/2/release-group/'.$referenceGroup['id']=>$referenceGroup,
            '/ws/2/release/'.$referenceRelease['id']=>$referenceRelease,
            default=>null,
        };
        return ['status'=>$data?200:404,'headers'=>[],'body'=>json_encode($data,JSON_THROW_ON_ERROR)];
    }
    $data = match ($path) {
        '/ws/2/release-group' => ['release-groups' => [$group], 'count' => 1],
        '/ws/2/release' => ['releases' => [$release], 'count' => 1, 'release-count' => 1],
        '/ws/2/release-group/' . $group['id'] => $group,
        '/ws/2/release/' . $release['id'] => $release,
        default => null,
    };
    return ['status' => $data ? 200 : 404, 'headers' => [], 'body' => json_encode($data, JSON_THROW_ON_ERROR)];
}
