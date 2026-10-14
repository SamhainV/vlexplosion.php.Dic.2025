<?php
$a = $metadata['album']; $ed = $metadata['edition'];
$old ??= [];
$value = static fn(string $key, string $default): string => is_string($old[$key] ?? null) ? $old[$key] : $default;
$artists = implode("\n", array_column($a['artists'], 'name'));
$year = $a['original_date'] ? substr($a['original_date'], 0, 4) : '';
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<section class="enc"><p class="accent">Último paso · Nada se ha guardado todavía</p><h1><?= !empty($linking)?'Revisar vinculación del ejemplar existente':'Revisar antes de guardar' ?></h1>
<?php if (getenv('VLEX_ENC_FIXTURE') === '1'): ?><p class="alert">Modo de pruebas: respuestas musicales controladas; colección aislada.</p><?php endif; ?>
<?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
<p class="alert format-badge">Soporte: <?= e(\App\Core\ReleaseFormat::label($ed['media'])) ?><?php if (!\App\Core\ReleaseFormat::vinyl($ed['media'])): ?> · No consta soporte en vinilo. La confirmación corresponde a esta edición, no a una edición en vinilo.<?php endif; ?></p>
<p>Datos recuperados de MusicBrainz. Las correcciones de este formulario se conservan en tu ejemplar y no cambian la ficha compartida de otros usuarios.</p>
<div class="card"><h2><?= e($a['title']) ?></h2><p><?= e(implode(', ', array_column($a['artists'], 'name'))) ?></p>
<p>Año original: <?= e($a['original_date'] ?? 'Desconocido') ?> · Géneros: <?= e(implode(', ', $a['genres']) ?: 'Sin datos') ?></p>
<p>Edición: <?= e($ed['country'] ?? 'País desconocido') ?> · <?= e($ed['date'] ?? 'Fecha desconocida') ?></p>
<?php foreach ($ed['labels'] as $l): ?><p><?= e($l['name']) ?> · <?= e($l['catalogue_number'] ?? 'Catálogo desconocido') ?></p><?php endforeach; ?>
<a href="<?= e('https://musicbrainz.org/release/' . $ed['id']) ?>" target="_blank" rel="noopener">Fuente de la edición</a></div>
<?php foreach ($ed['media'] as $medium): ?><h2>Soporte <?= (int)$medium['position'] ?> · <?= e($medium['format'] ?? 'Formato desconocido') ?></h2><ol>
<?php foreach ($medium['tracks'] as $t): ?><li><?= e(($t['number'] ?? (string)$t['position']) . ' · ' . $t['title']) ?><?php if ($t['duration_ms'] !== null): ?> · <?= sprintf('%d:%02d', intdiv($t['duration_ms'],60000), intdiv($t['duration_ms'],1000)%60) ?><?php endif; ?></li><?php endforeach; ?></ol><?php endforeach; ?>
<h2>Créditos disponibles</h2>
<?php
$creditRows = [];
foreach (array_merge($a['credits'], $ed['credits']) as $c) { $creditRows[] = ['name'=>$c['artist']['name'] ?? $c['organisation'], 'role'=>$c['role'], 'context'=>null]; }
foreach ($ed['media'] as $medium) { foreach ($medium['tracks'] as $track) {
    foreach ($track['recording']['credits'] ?? [] as $c) { $creditRows[] = ['name'=>$c['artist']['name'] ?? $c['organisation'], 'role'=>$c['role'], 'context'=>$track['title']]; }
    foreach ($track['recording']['works'] ?? [] as $work) { foreach ($work['credits'] as $c) { $creditRows[] = ['name'=>$c['artist']['name'] ?? $c['organisation'], 'role'=>$c['role'], 'context'=>$work['title']]; } }
} }
require __DIR__ . '/credits.php';
?>
