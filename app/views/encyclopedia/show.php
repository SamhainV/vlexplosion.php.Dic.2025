<?php $personal = json_decode($copy['overrides_json'] ?? '{}', true) ?: []; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<article class="enc" id="enc-record"><p class="accent">VLexPlosion · Ficha enciclopédica</p><h1><?= e($album['title']) ?></h1>
<?php if (getenv('VLEX_ENC_FIXTURE') === '1'): ?><p class="alert">Modo de pruebas: respuestas musicales controladas; colección aislada.</p><?php endif; ?>
<?php if (!empty($legacy)): ?><p class="muted">Registro original conservado. Todavía no tiene identidad musical ni edición confirmadas.</p><?php endif; ?>
<img class="cover" src="<?= e(\App\Core\CoverStore::url($vinyl['Image_Path']??null)) ?>" alt="Carátula de mi ejemplar o imagen predeterminada">
<?php if (empty($vinyl['Image_Path'])): ?><p class="muted">Puedes añadir tu carátula al editar el ejemplar.</p><?php endif; ?>
<?php $record=['album'=>$album,'edition'=>$edition,'artists'=>$artists,'genres'=>$genres,'labels'=>$labels,'media'=>$media??[],'tracks'=>$tracks,'credits'=>$credits,'recordings'=>$recordings??[],'works'=>$works??[],'sources'=>$sources??[]];require __DIR__.'/metadata.php'; ?>
<section class="card" id="enc-private"><h2>Mi ejemplar · Información privada</h2><p>Título revisado: <?= e($vinyl['Title']) ?></p><p>Artistas de mi ficha: <?= e($vinyl['author_name']) ?></p>
<?php if ($vinyl['Release_date']): ?><p>Año de mi ficha: <?= e((string)$vinyl['Release_date']) ?></p><?php endif; ?>
<?php if ($vinyl['Producer']): ?><p>Productor de mi ficha: <?= e($vinyl['Producer']) ?></p><?php endif; ?>
<?php foreach (['country' => 'País revisado', 'catalogue_number' => 'Catálogo revisado'] as $field => $label): if (!empty($personal[$field])): ?><p><?= e($label . ': ' . $personal[$field]) ?></p><?php endif; endforeach; ?>
<p><?= $vinyl['Is_Favorite'] ? '★ Favorito' : '' ?> <?= $vinyl['Is_Desired'] ? '◇ Deseado' : '' ?></p>
<?php if ($copy['notes']): ?><p style="white-space:pre-wrap"><?= e($copy['notes']) ?></p><?php endif; ?>
<a href="<?= e(base_url('vinyls/edit?id=' . $vinyl['Id'])) ?>">Editar mi ficha y carátula</a></section>
<p class="muted">Datos musicales de MusicBrainz: principales CC0; suplementarios CC BY-NC-SA 3.0. La información disponible puede ser parcial.</p><a href="<?= e(base_url('vinyls')) ?>">Volver a mi colección</a></article>
