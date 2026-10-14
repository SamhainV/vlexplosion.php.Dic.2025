<?php
declare(strict_types=1);
namespace App\Services\Metadata;

/** Pure review plan. It never writes or silently chooses between conflicting values. */
final class FieldReview
{
    public static function compare(array $current, array $incoming, array $manualFields = []): array
    {
        $review = [];
        foreach ($incoming as $field => $value) {
            if ($value === null || $value === '' || $value === []) { continue; }
            $old = $current[$field] ?? null;
            $state = $old === $value ? 'unchanged' : (
                in_array($field, $manualFields, true) ? 'manual_conflict' : (
                    $old === null || $old === '' || $old === [] ? 'proposed' : 'conflict'
                )
            );
            $review[$field] = ['current' => $old, 'proposed' => $value, 'state' => $state];
        }
        return $review;
    }
}
