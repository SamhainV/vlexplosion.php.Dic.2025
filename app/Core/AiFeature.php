<?php
declare(strict_types=1);

namespace App\Core;

/** The photographic prototype has no installed recognition provider or persistence layer. */
final class AiFeature
{
    public static function enabled(): bool
    {
        // Environment flags cannot enable an incomplete experimental module.
        return false;
    }

    public static function requireEnabled(): never
    {
        Auth::requireLogin();
        throw new HttpException(503, 'El reconocimiento fotográfico todavía no está disponible. Utiliza la búsqueda musical o el alta manual.');
    }
}
