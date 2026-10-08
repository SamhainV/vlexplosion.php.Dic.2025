<?php
declare(strict_types=1);
define('VLEXPLOSION_TESTING', true);
$root = dirname(__DIR__);
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/app/Core/bootstrap.php';
use App\Core\{Auth, Csrf, Database, HttpException, Input, LoginLimiter, SessionManager, VinylInput};
$count = 0;
function check(bool $condition, string $label): void
{
    global $count;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $count++;
}
function rejects(callable $call, int $status, string $label): void
{
    try { $call(); } catch (HttpException $error) { check($error->status === $status, $label); return; }
    throw new RuntimeException('FAIL: ' . $label);
}
$token = Csrf::token();
check(strlen($token) === 64 && Csrf::token() === $token, 'stable random CSRF');
foreach ([null, '', str_repeat('a', 64), ['bad']] as $bad) {
    $_POST = ['_csrf' => $bad]; rejects(fn() => Csrf::verifyRequest(), 403, 'reject invalid CSRF');
}
$_POST = ['_csrf' => $token]; Csrf::verifyRequest(); check(true, 'valid CSRF');
$params = session_get_cookie_params();
check($params['httponly'] && $params['samesite'] === 'Lax' && $params['path'] === '/', 'cookie policy');
check(ini_get('session.use_strict_mode') === '1' && ini_get('session.use_only_cookies') === '1', 'session strict mode');
$before = session_id(); Auth::login(['id' => 7, 'username' => 'fiction', 'email' => 'fiction@example.invalid', 'password' => 'never-store']);
check(session_id() !== $before && Auth::id() === 7, 'login renews session');
check(!isset($_SESSION['user']['password']) && Csrf::token() !== $token, 'session data and token rotation');
$token = Csrf::token(); $_POST = ['_csrf' => $token];
$before = session_id(); Auth::logout(); check(!Auth::check() && session_id() !== $before, 'logout invalidation');
rejects(fn() => Csrf::verifyRequest(), 403, 'old CSRF rejected after logout');
Auth::login(['id' => 7]); $_SESSION['_last_activity'] = time() - 1800; SessionManager::expire(); check(!Auth::check(), 'idle expiry');
Auth::login(['id' => 7]); $_SESSION['_authenticated_at'] = time() - 43200; SessionManager::expire(); check(!Auth::check(), 'absolute expiry');
foreach ([['bad'], "bad\0value", "\xff", str_repeat('ñ', 66)] as $bad) { rejects(fn() => Input::text($bad, 65), 422, 'text validation'); }
check(Input::text(str_repeat('ñ', 65), 65) === str_repeat('ñ', 65), 'unicode length boundary');
foreach (['2020abc', '2020.9', '-1', ['bad'], '2147483648'] as $bad) { rejects(fn() => Input::integer($bad), 422, 'integer validation'); }
check(Input::password(' spaces ') === ' spaces ', 'password unchanged');
rejects(fn() => Input::password(['bad']), 422, 'array password');
$data = ['title' => 'Fiction', 'author' => 'Fiction', 'producer' => 'Fiction', 'genre_id' => '1', 'format_id' => '1', 'condition_id' => '1', 'record_label_id' => '1', 'edition_id' => '1', 'release_date' => '2020'];
check(VinylInput::validate($data, 2026)['release_date'] === 2020, 'valid vinyl');
foreach (['1900', '2020abc', '2028'] as $year) { $bad = $data; $bad['release_date'] = $year; rejects(fn() => VinylInput::validate($bad, 2026), 422, 'year validation'); }
$valid = VinylInput::validate($data, 2026);
VinylInput::validateCatalogues($valid, ['genre_id' => [['id' => 1]]]); check(true, 'catalogue membership');
rejects(fn() => VinylInput::validateCatalogues($valid, ['genre_id' => [['id' => 2]]]), 422, 'invalid catalogue');
$_ENV['DB_ENABLED'] = '0';
try { Database::pdo(); throw new LogicException('Database was not blocked'); } catch (RuntimeException $error) { check($error->getMessage() === 'Database access disabled', 'database disabled before PDO'); }
$runtime = __DIR__ . '/runtime'; if (!is_dir($runtime)) { mkdir($runtime, 0700); }
$limiter = new LoginLimiter($runtime . '/limiter-' . bin2hex(random_bytes(8)) . '.json');
for ($i = 0; $i < 5; $i++) { check($limiter->attempt('fiction', '192.0.2.1', 1000), 'allowed attempt'); }
check(!$limiter->attempt('FICTION', '192.0.2.2', 1000), 'account limit survives IP changes');
check($limiter->attempt('fiction', '192.0.2.1', 1900), 'window expires');
$limiter->success('fiction'); check($limiter->attempt('fiction', '192.0.2.1', 1900), 'account success reset');
$limiter = new LoginLimiter($runtime . '/ip-' . bin2hex(random_bytes(8)) . '.json');
for ($i = 0; $i < 30; $i++) { check($limiter->attempt('fiction-' . $i, '192.0.2.3', 1000), 'IP allowance'); }
check(!$limiter->attempt('another', '192.0.2.3', 1000), 'IP limit across accounts');
check(app_background() === 'assets/images/vintage-bg.png', 'default background');
$_ENV['APP_BACKGROUND'] = "evil');alert(1)"; check(app_background() === 'assets/images/vintage-bg.png', 'reject unsafe CSS path');
session_write_close();
foreach (['csrf-login' => 403, 'csrf-store' => 403, 'csrf-delete' => 403, 'csrf-logout' => 403, 'array-login' => 422, 'array-password' => 422, 'array-title' => 422, 'bad-catalogue' => 422, 'internal-error' => 500, 'get-login' => 200, 'get-create' => 200, 'get-list' => 200, 'good-login' => 302, 'wrong-login' => 200, 'valid-logout' => 302] as $case => $status) {
    $process = proc_open([PHP_BINARY, __DIR__ . '/request.php', $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) { throw new RuntimeException('Cannot run fixture'); }
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
    check(str_contains($out, 'TEST_STATUS=' . $status), 'request status ' . $case);
    check(!str_contains($out . $err, 'MUTATION_REACHED') && !str_contains($out . $err, 'PRIVATE_SQL_PASSWORD_MARKER') && !str_contains($out . $err, 'Stack trace'), 'no mutation or internal leak ' . $case);
    check($err === '', 'no PHP warnings ' . $case);
    if (str_starts_with($case, 'get-')) {
        preg_match_all('/<form\b[^>]*method="POST"[^>]*>(.*?)<\/form>/s', $out, $forms);
        check(count($forms[1]) > 0, 'rendered POST forms ' . $case);
        foreach ($forms[1] as $form) {
            check((bool)preg_match('/<input type="hidden" name="_csrf" value="[a-f0-9]{64}">/', $form), 'CSRF inside rendered form ' . $case);
        }
        check(!preg_match('/<form[^>]*<input/s', $out), 'valid form opening tag ' . $case);
    }
    if ($case === 'good-login') { check(str_contains($out, 'TEST_AUTH=7'), 'valid login with fictional user'); }
    if ($case === 'wrong-login' || $case === 'valid-logout') { check(str_contains($out, 'TEST_AUTH=0'), 'no authentication ' . $case); }
    if ($case === 'internal-error') { check(str_contains($out, 'No se pudo completar'), 'generic error response'); }
}
echo "OK: $count security checks; fictional fixtures; no database connection.\n";
