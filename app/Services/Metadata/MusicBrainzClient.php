<?php
declare(strict_types=1);
namespace App\Services\Metadata;

use Closure;
use InvalidArgumentException;

/** Independent adapter: no bootstrap, database, prototype or user session. */
final class MusicBrainzClient
{
    private Closure $transport;
    private Closure $clock;
    private Closure $sleep;

    public function __construct(
        private readonly string $cacheDirectory,
        private readonly string $userAgent,
        ?callable $transport = null,
        ?callable $clock = null,
        ?callable $sleep = null,
    ) {
        if (!preg_match('#^VLexPlosion/[^\s]+ \((https://[^\s)]+|[^\s()]+@[^\s()]+)\)$#D', $userAgent)) {
            throw new MetadataException('configuration');
        }
        $this->transport = Closure::fromCallable($transport ?? new MusicBrainzHttp());
        $this->clock = Closure::fromCallable($clock ?? static fn(): float => microtime(true));
        $this->sleep = Closure::fromCallable($sleep ?? static fn(float $seconds) => usleep((int)ceil($seconds * 1000000)));
        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
            throw new MetadataException('configuration');
        }
    }

    /** All returned entries are candidates. Scores are not identity guarantees. */
    public function search(string $entity, array $fields, int $offset = 0): array
    {
        $allowed = [
            'artist' => ['artist'],
            'release-group' => ['artist', 'releasegroup', 'firstreleasedate'],
            'release' => ['artist', 'release', 'barcode', 'catno', 'date'],
        ];
        if (!isset($allowed[$entity]) || $offset < 0 || $offset > 10000 || !$fields) {
            throw new InvalidArgumentException('Invalid search');
        }
        $query = [];
        foreach ($fields as $field => $value) {
            if (!in_array($field, $allowed[$entity], true) || !is_string($value) ||
                trim($value) === '' || strlen($value) > 400 || preg_match('/[\x00-\x1f]/', $value)) {
                throw new InvalidArgumentException('Invalid search field');
            }
            // Escape Lucene syntax separately from URL encoding.
            $escaped = '';
            foreach (str_split(trim($value)) as $character) {
                $escaped .= (str_contains('+-&|!(){}[]^"~*?:\\/', $character) ? '\\' : '') . $character;
            }
            $query[] = $field . ':"' . $escaped . '"';
        }
        $response = $this->request($entity, ['query' => implode(' AND ', $query), 'limit' => 25, 'offset' => $offset]);
        $key = $entity === 'artist' ? 'artists' : $entity . 's';
        if (!isset($response['data'][$key]) || !is_array($response['data'][$key]) || !array_is_list($response['data'][$key])) {
            throw new MetadataException('invalid_response');
        }
        foreach ($response['data'][$key] as $candidate) {
            if (!is_array($candidate) || !self::validId($candidate['id'] ?? null)) {
                throw new MetadataException('invalid_response');
            }
        }
        return [
            'source' => 'musicbrainz', 'entity_type' => $entity,
            'candidates' => $response['data'][$key], 'offset' => $offset,
            'total' => $response['data']['count'] ?? null,
            'fetched_at' => $response['fetched_at'], 'cached' => $response['cached'],
            'requires_confirmation' => true,
        ];
    }

    public function lookup(string $entity, string $id): array
    {
        $includes = [
            'artist' => 'aliases+artist-rels',
            'release-group' => 'artists+genres+artist-rels',
            'release' => 'artists+labels+recordings+release-groups+artist-credits+artist-rels+recording-level-rels+work-rels+work-level-rels',
            'recording' => 'artists+artist-rels+work-rels',
            'work' => 'artist-rels',
        ];
        if (!isset($includes[$entity]) || !self::validId($id)) { throw new InvalidArgumentException('Invalid entity or MBID'); }
        $response = $this->request($entity . '/' . strtolower($id), ['inc' => $includes[$entity]]);
        if (($response['data']['id'] ?? null) !== strtolower($id)) { throw new MetadataException('invalid_response'); }
        return $response + ['source' => 'musicbrainz', 'entity_type' => $entity, 'requires_confirmation' => true];
    }

    public function editions(string $groupId, int $offset = 0): array
    {
        if (!self::validId($groupId) || $offset < 0 || $offset > 10000) { throw new InvalidArgumentException('Invalid browse'); }
        $response = $this->request('release', ['release-group' => strtolower($groupId), 'inc' => 'labels+media', 'limit' => 25, 'offset' => $offset]);
        if (!isset($response['data']['releases']) || !is_array($response['data']['releases']) || !array_is_list($response['data']['releases'])) {
            throw new MetadataException('invalid_response');
        }
        foreach ($response['data']['releases'] as $release) {
            if (!is_array($release) || !self::validId($release['id'] ?? null)) { throw new MetadataException('invalid_response'); }
        }
        return [
            'source' => 'musicbrainz', 'entity_type' => 'release', 'release_group_id' => strtolower($groupId),
            'candidates' => $response['data']['releases'], 'total' => $response['data']['release-count'] ?? null,
            'offset' => $offset, 'fetched_at' => $response['fetched_at'], 'cached' => $response['cached'],
            'requires_confirmation' => true,
        ];
    }

    private static function validId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $id) === 1;
    }

    private function request(string $path, array $query): array
    {
        $url = 'https://musicbrainz.org/ws/2/' . $path . '?' . http_build_query($query + ['fmt' => 'json'], '', '&', PHP_QUERY_RFC3986);
        $cache = $this->cacheDirectory . '/' . hash('sha256', $url) . '.json';
        if ($saved = $this->cached($cache, $path)) { return $saved; }
        $lock = fopen($this->cacheDirectory . '/musicbrainz.lock', 'c+');
        if (!$lock) { throw new MetadataException('configuration'); }
        try {
            // Bounded contention: never build an unbounded queue of web workers.
            for ($attempt = 0; !flock($lock, LOCK_EX | LOCK_NB); $attempt++) {
                if ($saved = $this->cached($cache, $path)) { return $saved; }
                if ($attempt >= 20) { throw new MetadataException('busy', 2); }
                ($this->sleep)(0.1);
            }
            if ($saved = $this->cached($cache, $path)) { return $saved; }
            $now = ($this->clock)();
            $raw = stream_get_contents($lock);
            $gate = json_decode($raw, true);
            // Previous versions stored a bare timestamp. Preserve its deadline.
            $next = is_array($gate) ? (float)($gate['next'] ?? 0) : (float)$raw;
            if ($next > $now) {
                if ((is_array($gate) && ($gate['upstream'] ?? false)) || $next - $now > 1.2) {
                    throw new MetadataException('rate_limit', (int)ceil($next - $now));
                }
                ($this->sleep)($next - $now);
                $now = ($this->clock)();
                if ($now < $next) { throw new MetadataException('busy', 1); }
            }
            self::writeGate($lock, $now + 1.1);
            try { $response = ($this->transport)($url, $this->userAgent); }
            catch (\Throwable) { throw new MetadataException('unavailable'); }
            $status = $response['status'] ?? 0;
            if ($status === 429 || $status === 503) {
                $now = ($this->clock)(); // Retry-After starts when the response arrives.
                $retry = $response['headers']['retry-after'] ?? '60';
                $delay = is_string($retry) && ctype_digit($retry) ? (int)$retry : (strtotime((string)$retry) === false ? 60 : max(1, (int)strtotime((string)$retry) - (int)$now));
                $delay = max(1, $delay);
                self::writeGate($lock, $now + $delay, true);
                throw new MetadataException('rate_limit', $delay);
            }
            if ($status === 404) { throw new MetadataException('not_found'); }
            if ($status !== 200) { throw new MetadataException('unavailable'); }
            $body = $response['body'] ?? null;
            if (!is_string($body) || strlen($body) > 2 * 1024 * 1024) { throw new MetadataException('invalid_response'); }
            try { $data = json_decode($body, true, 128, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { throw new MetadataException('invalid_response'); }
            if (!is_array($data) || !str_starts_with(ltrim($body), '{') || isset($data['error'])) { throw new MetadataException('invalid_response'); }
            self::validateResponse($path, $data);
            $saved = ['data' => $data, 'fetched_at' => ($this->clock)()];
            // Atomic replacement lets warm cache reads bypass unrelated network requests.
            $temporary = $cache . '.tmp';
            if (file_put_contents($temporary, json_encode($saved, JSON_THROW_ON_ERROR), LOCK_EX) === false || !rename($temporary, $cache)) { throw new MetadataException('unavailable'); }
            return $saved + ['cached' => false];
        } finally { fclose($lock); }
    }

    private function cached(string $cache, string $path): ?array
    {
        if (!is_file($cache)) { return null; }
        $saved = json_decode((string)file_get_contents($cache), true);
        $now = ($this->clock)();
        if (!is_array($saved) || !is_numeric($saved['fetched_at'] ?? null) || !is_array($saved['data'] ?? null) ||
            $saved['fetched_at'] > $now || $saved['fetched_at'] <= $now - 3600) { return null; }
        try { self::validateResponse($path, $saved['data']); }
        catch (MetadataException) { return null; }
        return $saved + ['cached' => true];
    }

    private static function writeGate($lock, float $next, bool $upstream = false): void
    {
        rewind($lock);
        if (!ftruncate($lock, 0) || fwrite($lock, json_encode(['next' => $next, 'upstream' => $upstream], JSON_THROW_ON_ERROR)) === false || !fflush($lock)) { throw new MetadataException('unavailable'); }
    }

    private static function validateResponse(string $path, array $data): void
    {
        if (str_contains($path, '/')) {
            [$entity, $id] = explode('/', $path, 2);
            $name = $entity === 'artist' ? 'name' : 'title';
            if (($data['id'] ?? null) !== $id || !is_string($data[$name] ?? null) || trim($data[$name]) === '') {
                throw new MetadataException('invalid_response');
            }
            return;
        }
        $key = $path === 'artist' ? 'artists' : $path . 's';
        if (!isset($data[$key]) || !is_array($data[$key]) || !array_is_list($data[$key])) { throw new MetadataException('invalid_response'); }
        foreach ($data[$key] as $row) {
            $name = $path === 'artist' ? 'name' : 'title';
            if (!is_array($row) || !self::validId($row['id'] ?? null) || !is_string($row[$name] ?? null) || trim($row[$name]) === '') {
                throw new MetadataException('invalid_response');
            }
        }
    }
}
