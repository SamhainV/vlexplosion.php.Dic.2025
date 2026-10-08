<?php
declare(strict_types=1);
namespace App\Core;
final class LoginLimiter
{
    public function __construct(private string $path) {}
    public static function application(): self
    {
        $dir = app_storage_path('security');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('Login protection unavailable');
        }
        return new self($dir . '/login-attempts.json');
    }
    public function attempt(string $account, string $ip, ?int $now = null): bool
    {
        $now ??= time();
        return $this->update(static function (array &$state) use ($account, $ip, $now): bool {
            foreach ($state as $key => $entry) {
                if (!is_array($entry) || ($entry['expires'] ?? 0) <= $now) { unset($state[$key]); }
            }
            $keys = ['account:' . hash('sha256', strtolower($account)) => 5, 'ip:' . hash('sha256', $ip) => 30];
            foreach ($keys as $key => $limit) {
                if (($state[$key]['count'] ?? 0) >= $limit) { return false; }
            }
            if (count($state) >= 10000) { return false; } // Bound storage; fail closed.
            foreach ($keys as $key => $limit) {
                $state[$key] = ['count' => ($state[$key]['count'] ?? 0) + 1, 'expires' => $state[$key]['expires'] ?? ($now + 900)];
            }
            return true;
        });
    }
    public function success(string $account): void
    {
        $this->update(static function (array &$state) use ($account): bool {
            unset($state['account:' . hash('sha256', strtolower($account))]);
            return true;
        });
    }
    private function update(callable $operation): bool
    {
        $handle = fopen($this->path, 'c+');
        if ($handle === false) { throw new \RuntimeException('Login protection unavailable'); }
        try {
            if (!flock($handle, LOCK_EX)) { throw new \RuntimeException('Login protection lock failed'); }
            $contents = stream_get_contents($handle);
            $state = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state)) { throw new \RuntimeException('Invalid login protection state'); }
            $result = $operation($state);
            $encoded = json_encode($state, JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $encoded) !== strlen($encoded) || !fflush($handle)) {
                throw new \RuntimeException('Login protection write failed');
            }
            return $result;
        } finally { fclose($handle); }
    }
}
