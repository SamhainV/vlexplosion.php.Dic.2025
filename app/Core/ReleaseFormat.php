<?php
declare(strict_types=1);
namespace App\Core;

/** Describe only formats actually provided by the source; never infer from titles. */
final class ReleaseFormat
{
    public static function vinyl(array $media): bool
    {
        foreach ($media as $medium) { if (preg_match('/\bvinyl\b/i', (string)($medium['format'] ?? ''))) return true; }
        return false;
    }
    public static function label(array $media): string
    {
        $formats=array_values(array_unique(array_map(static fn(array $m):string=>trim((string)($m['format']??''))?:'Formato desconocido',$media)));
        return $formats ? implode(' + ',$formats) : 'Formato desconocido';
    }
    public static function preference(mixed $value): string
    {
        if (!is_string($value) || !in_array($value,['prefer','vinyl','all'],true)) throw new HttpException(422,'Selecciona un filtro de soporte válido.');
        return $value;
    }
    public static function filter(array $result, string $preference): array
    {
        self::preference($preference);
        $result['page_count']=count($result['candidates']);
        if (($result['entity_type']??'')==='release') {
            if ($preference==='vinyl') $result['candidates']=array_values(array_filter($result['candidates'],static fn(array $c):bool=>self::vinyl($c['media']??[])));
            if ($preference==='prefer') usort($result['candidates'],static fn(array $a,array $b):int=>(int)self::vinyl($b['media']??[])<=>(int)self::vinyl($a['media']??[]));
        }
        $result['format_preference']=$preference;
        return $result;
    }
}
