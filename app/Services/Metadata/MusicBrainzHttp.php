<?php
declare(strict_types=1);
namespace App\Services\Metadata;

/** GET only, one fixed origin; no credentials, redirects, images or local URLs. */
final class MusicBrainzHttp
{
    public function __invoke(string $url, string $userAgent): array
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'musicbrainz.org' ||
            isset($parts['port']) || isset($parts['user']) || isset($parts['pass']) ||
            !str_starts_with($parts['path'] ?? '', '/ws/2/') || preg_match('/[\r\n]/', $userAgent)) {
            throw new MetadataException('configuration');
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'GET', 'header' => ['Accept: application/json', 'User-Agent: ' . $userAgent],
                'timeout' => 10, 'follow_location' => 0, 'max_redirects' => 0, 'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) { throw new MetadataException('unavailable'); }
        try {
            $body = stream_get_contents($stream, 2 * 1024 * 1024 + 1);
            $meta = stream_get_meta_data($stream);
            if ($body === false || strlen($body) > 2 * 1024 * 1024 || ($meta['timed_out'] ?? false)) {
                throw new MetadataException('invalid_response');
            }
            $status = 0;
            $headers = [];
            foreach ($meta['wrapper_data'] ?? [] as $line) {
                if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $m)) { $status = (int)$m[1]; }
                elseif (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }
            }
            return ['status' => $status, 'headers' => $headers, 'body' => $body];
        } finally { fclose($stream); }
    }
}
