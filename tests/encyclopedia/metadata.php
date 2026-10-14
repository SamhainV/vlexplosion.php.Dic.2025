<?php
declare(strict_types=1);

// Offline contract tests. All responses and musical identities below are fictional.
$root = dirname(__DIR__, 2);
foreach (['MetadataException', 'MusicBrainzHttp', 'MusicBrainzClient', 'FieldReview'] as $class) {
    require $root . '/app/Services/Metadata/' . $class . '.php';
}
use App\Services\Metadata\{MetadataException, MusicBrainzHttp, MusicBrainzClient, FieldReview};
$directory = $root . '/tests/runtime/metadata-' . bin2hex(random_bytes(6));
mkdir($directory, 0700);
$ua = 'VLexPlosion/encyclopedia-dev (https://github.com/SamhainV/vlexplosion.php.Dic.2025)';
$id = '00000000-0000-4000-8000-000000000001';
$id2 = '00000000-0000-4000-8000-000000000002';
$now = 1000.0;
$calls = [];
$next = ['status' => 200, 'headers' => [], 'body' => json_encode(['count' => 2, 'release-groups' => [
    ['id' => $id, 'title' => 'Fictional album'], ['id' => $id2, 'title' => 'Fictional ambiguous album'],
]])];
$transport = function (string $url, string $agent) use (&$calls, &$next): array {
    $calls[] = [$url, $agent];
    if ($next instanceof Throwable) { throw $next; }
    return $next;
};
$clock = static function () use (&$now): float { return $now; };
$sleeps = [];
$sleep = static function (float $seconds) use (&$now, &$sleeps): void { $sleeps[] = $seconds; $now += $seconds; };
$client = new MusicBrainzClient($directory, $ua, $transport, $clock, $sleep);
$count = 0;
function check(bool $ok, string $label): void {
    global $count;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $count++;
}
function fails(callable $operation, string $reason): MetadataException {
    try { $operation(); } catch (MetadataException $error) {
        check($error->reason === $reason, 'expected ' . $reason);
        return $error;
    }
    throw new RuntimeException('Expected MetadataException: ' . $reason);
}
function invalid(callable $operation): void {
    try { $operation(); } catch (InvalidArgumentException) { check(true, 'invalid input blocked'); return; }
    throw new RuntimeException('Expected input rejection');
}
$result = $client->search('release-group', ['artist' => 'Fictional band', 'releasegroup' => 'Fictional album']);
check(count($result['candidates']) === 2 && $result['requires_confirmation'], 'ambiguity preserved; no automatic selection');
check($result['entity_type'] === 'release-group' && !$result['cached'], 'album group is not an edition');
check($calls[0][1] === $ua, 'identifying User-Agent sent');
$cached = $client->search('release-group', ['artist' => 'Fictional band', 'releasegroup' => 'Fictional album']);
check($cached['cached'] && count($calls) === 1, 'cache avoids second request even during rate interval');
$client->search('release-group', ['releasegroup' => 'Other']);
check(count($sleeps) === 1 && abs($sleeps[0] - 1.1) < 0.0001, 'local pacing waits automatically');
check(count($calls) === 2, 'exactly one call after bounded local wait');
$now += 2;
$next['body'] = json_encode(['artists' => [], 'count' => 0]);
$result = $client->search('artist', ['artist' => 'AC/DC "name" OR *']);
parse_str(parse_url($calls[2][0], PHP_URL_QUERY), $query);
check($query['query'] === 'artist:"AC\\/DC \\"name\\" OR \\*"', 'Lucene input escaped independently');
check($result['candidates'] === [] && $result['total'] === 0, 'no results are not simulated matches');
invalid(fn() => $client->search('url', ['artist' => 'Fiction']));
invalid(fn() => $client->search('release', ['url' => 'file:///etc/passwd']));
invalid(fn() => $client->search('artist', ['artist' => ['bad']]));
invalid(fn() => $client->search('artist', ['artist' => '']));
invalid(fn() => $client->search('artist', ['artist' => "line\nbreak"]));
invalid(fn() => $client->lookup('release', '../../bad'));
invalid(fn() => $client->editions($id, -1));
fails(fn() => new MusicBrainzClient($directory, "VLexPlosion\r\nInvalid"), 'configuration');
$http = new MusicBrainzHttp();
foreach (['http://musicbrainz.org/ws/2/artist', 'https://localhost/ws/2/artist', 'https://musicbrainz.org:443/ws/2/artist', 'https://musicbrainz.org@localhost/ws/2/artist', 'file:///etc/passwd'] as $url) {
    fails(fn() => $http($url, $ua), 'configuration');
}
$now += 2;
$next['body'] = json_encode(['releases' => [['id' => $id, 'title' => 'Fictional edition', 'barcode' => '001234']], 'count' => 1]);
$result = $client->search('release', ['barcode' => '001234', 'catno' => 'TEST 01']);
check($result['candidates'][0]['barcode'] === '001234', 'barcode remains text with leading zeroes');
$now += 2;
$next['body'] = json_encode(['id' => $id, 'title' => 'Fictional edition', 'media' => [['position' => 1, 'tracks' => [['title' => 'Fictional song', 'length' => null]]]]]);
$result = $client->lookup('release', $id);
check($result['data']['media'][0]['tracks'][0]['length'] === null, 'unknown duration retained');
check(!isset($result['data']['date']), 'missing date not invented');
$now += 2;
$next['body'] = json_encode(['releases' => [['id' => $id, 'title' => 'Fictional edition']], 'release-count' => 75]);
$result = $client->editions($id2, 25);
check($result['total'] === 75 && $result['offset'] === 25 && count($result['candidates']) === 1, 'browse pagination does not claim complete discography');
check($result['release_group_id'] === $id2 && $result['entity_type'] === 'release', 'group ID retained separately from edition ID');
$now += 2;
$next = ['status' => 429, 'headers' => ['retry-after' => '120'], 'body' => ''];
$error = fails(fn() => $client->search('artist', ['artist' => 'Limited']), 'rate_limit');
check($error->retryAfter === 120, 'Retry-After respected');
$sent = count($calls);
$now += 60;
fails(fn() => $client->search('artist', ['artist' => 'Limited']), 'rate_limit');
check(count($calls) === $sent, 'retry before deadline does not call provider');
$now += 61;
$next = ['status' => 503, 'headers' => ['retry-after' => gmdate('D, d M Y H:i:s \G\M\T', (int)$now + 90)], 'body' => ''];
check(fails(fn() => $client->search('artist', ['artist' => 'Maintenance']), 'rate_limit')->retryAfter === 90, 'HTTP-date retry supported');
$now += 100;
foreach ([404 => 'not_found', 401 => 'unavailable', 500 => 'unavailable', 302 => 'unavailable'] as $status => $reason) {
    $next = ['status' => $status, 'headers' => [], 'body' => 'Never expose provider response'];
    fails(fn() => $client->lookup('work', $id), $reason);
    $now += 2;
}
foreach (['not JSON', '[]', '{}', '{"artists":[{}]}', '{"artists":null}', '{"error":"upstream detail"}'] as $body) {
    $next = ['status' => 200, 'headers' => [], 'body' => $body];
    fails(fn() => $client->search('artist', ['artist' => 'Malformed']), 'invalid_response');
    $now += 2;
}
$next = new RuntimeException('private upstream diagnostic');
$error = fails(fn() => $client->search('artist', ['artist' => 'Offline']), 'unavailable');
check(!str_contains($error->getMessage(), 'private'), 'transport details not exposed');
$now = 5000;
fails(fn() => $client->search('release-group', ['artist' => 'Fictional band', 'releasegroup' => 'Fictional album']), 'unavailable');
check(true, 'expired cache does not impersonate a fresh response offline');
$current = ['title' => 'Manual', 'year' => null, 'barcode' => '001', 'genre' => 'Existing'];
$plan = FieldReview::compare($current, ['title' => 'External', 'year' => 1984, 'barcode' => null, 'genre' => 'Other'], ['title']);
check($plan['title']['state'] === 'manual_conflict' && $current['title'] === 'Manual', 'manual correction retained');
check($plan['year']['state'] === 'proposed', 'missing value becomes proposal only');
check($plan['genre']['state'] === 'conflict', 'conflicting sources require choice');
check(!isset($plan['barcode']), 'unknown import cannot erase known data');
// Stabilization: cache availability, contention and real upstream cooldown semantics.
$now = 6000;
$next = ['status'=>200, 'headers'=>[], 'body'=>json_encode(['artists'=>[], 'count'=>0])];
$client->search('artist', ['artist'=>'Warm']);
$held = fopen($directory . '/musicbrainz.lock', 'c+');
flock($held, LOCK_EX);
$sent = count($calls);
check($client->search('artist', ['artist'=>'Warm'])['cached'], 'warm cache bypasses occupied lock');
$error = fails(fn() => $client->search('artist', ['artist'=>'Cold']), 'busy');
check($error->retryAfter === 2 && count($calls) === $sent, 'contention is bounded without network');
fclose($held);
$now += 3;
$slow = new MusicBrainzClient($directory, $ua, function () use (&$now, &$calls): array {
    $calls[] = ['slow']; $now += 5;
    return ['status'=>429, 'headers'=>['retry-after'=>'1'], 'body'=>''];
}, $clock, $sleep);
$before = $now;
check(fails(fn() => $slow->search('artist', ['artist'=>'Slow']), 'rate_limit')->retryAfter === 1, 'short upstream cooldown remains explicit');
$gate = json_decode(file_get_contents($directory . '/musicbrainz.lock'), true);
check($gate['upstream'] && $gate['next'] === $before + 6, 'cooldown starts after slow response');
$sent = count($calls);
fails(fn() => $client->search('artist', ['artist'=>'Cold']), 'rate_limit');
check(count($calls) === $sent, 'short upstream cooldown never bypassed by local pacing');
check($client->search('artist', ['artist'=>'Warm'])['cached'], 'cached data available during upstream cooldown');
$now += 2;
$client->search('artist', ['artist'=>'Cold']);
check(count($calls) === $sent + 1, 'explicit retry after cooldown sends only one request');
echo 'OK: ' . $count . " metadata checks; injected fictional HTTP responses, zero external requests.\n";
