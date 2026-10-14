<?php
declare(strict_types=1);
namespace App\Services\Metadata;

final class MetadataException extends \RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $retryAfter = 0)
    {
        parent::__construct(match ($reason) {
            'rate_limit' => 'MusicBrainz ha solicitado una pausa temporal. Tu búsqueda y selección se conservan.',
            'busy' => 'Hay otra consulta musical en curso. Tu búsqueda y selección se conservan.',
            'configuration' => 'El proveedor de metadatos necesita configuración.',
            'not_found' => 'No se encontró ese identificador en la fuente musical.',
            'invalid_response' => 'La fuente musical devolvió una respuesta incompleta o inválida.',
            default => 'No se pudo consultar la fuente musical. Puedes continuar manualmente.',
        });
    }
}
