<?php
declare(strict_types=1);
namespace App\Core;

use App\Services\Metadata\MusicBrainzClient;

final class Encyclopedia
{
    public static function enabled(): bool
    {
        return ($_ENV['VLEX_ENC_ENABLED'] ?? getenv('VLEX_ENC_ENABLED')) === '1';
    }

    public static function requireEnabled(): void
    {
        Auth::requireLogin();
        if (!self::enabled()) { throw new HttpException(503, 'La enciclopedia no está habilitada en este entorno.'); }
    }

    public static function client(): MusicBrainzClient
    {
        self::requireEnabled();
        $transport = null;
        if (defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true && getenv('VLEX_ENC_FIXTURE') === '1') {
            require_once dirname(__DIR__, 2) . '/tests/encyclopedia/fixtures.php';
            $transport = 'encyclopedia_fixture_response';
            if(getenv('VLEX_CREDIT_FIXTURE')==='1') {
                require_once dirname(__DIR__, 2).'/tests/credits/transport.php';
                $transport='credit_fixture_response';
            }
        }
        return new MusicBrainzClient(
            app_storage_path('musicbrainz'),
            'VLexPlosion/encyclopedia-dev (https://github.com/SamhainV/vlexplosion.php.Dic.2025)',
            $transport,
        );
    }
}
