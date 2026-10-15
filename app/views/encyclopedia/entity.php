<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<article class="enc"><p class="accent">VLexPlosion · <?= ['album'=>'Álbum','edition'=>'Edición','artist'=>'Artista','label'=>'Discográfica','work'=>'Composición','recording'=>'Grabación'][$kind] ?></p>
<h1><?= e($entity['title'] ?? $entity['name'] ?? $album['title']) ?></h1>
<?php if ($kind==='artist'): ?>
<h2>Álbumes y trabajos relacionados almacenados</h2><p class="muted">Relaciones como artista, intérprete, productor, compositor y otros créditos disponibles. No se consulta una discografía externa completa.</p><?php if (!$albums): ?><p>No hay álbumes relacionados disponibles.</p><?php endif; ?>
<?php foreach ($albums as $a): ?><p><a href="<?= e(base_url('encyclopedia/album?id='.$a['id'])) ?>"><?= e($a['title']) ?></a></p><?php endforeach; ?>
<?php elseif(in_array($kind,['label','work','recording'],true)): ?>
<h2>Álbumes y ediciones relacionados</h2><?php foreach($related as $a): ?><p><a href="<?= e(base_url('encyclopedia/album?id='.$a['id'])) ?>"><?= e($a['title']) ?></a> · <a href="<?= e(base_url('encyclopedia/edition?id='.$a['edition_id'])) ?>"><?= e(($a['country']??'País desconocido').' · '.($a['release_date']??'Fecha desconocida')) ?></a></p><?php endforeach; ?>
<?php foreach($relations as $relation): ?><p><a href="<?= e(base_url('encyclopedia/'.($kind==='work'?'recording':'work').'?id='.$relation['id'])) ?>"><?= e($relation['title']) ?></a></p><?php endforeach; ?>
<?php else: ?>
<p><?php foreach ($artists as $a): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$a['id'])) ?>"><?= e($a['name']) ?></a> <?php endforeach; ?></p>
<?php if ($kind==='album'): ?>
<p>Publicación original: <?= e($album['original_date'] ?? 'Desconocida') ?></p>
<?php if ($album['draft_owner_id']!==null): ?><p>Borrador privado; identidad musical no confirmada.</p><?php endif; ?>
<p>Clasificaciones: <?= e(implode(', ',array_column($genres,'name'))?:'Sin información confirmada') ?></p>
<h2>Créditos del álbum</h2><?php $creditTracks=[];$creditWorks=[];$creditRows=array_map(fn($c)=>array_merge($c,['name'=>$c['name']??$c['organisation'],'context'=>null]),$credits);require __DIR__.'/credits.php'; ?>
<h2>Ediciones disponibles</h2><?php if (!$editions): ?><p>Sin ediciones confirmadas.</p><?php endif; ?>
<?php foreach ($editions as $edition): ?><p><a href="<?= e(base_url('encyclopedia/edition?id='.$edition['id'])) ?>"><?= e(($edition['country']??'País desconocido').' · '.($edition['release_date']??'Fecha desconocida')) ?></a></p><?php endforeach; ?>
<?php else: ?>
<?php $record=['album'=>$album,'edition'=>$edition,'artists'=>$artists,'genres'=>$genres,'labels'=>$labels,'media'=>$media,'tracks'=>$tracks,'credits'=>$credits,'recordings'=>$recordings??[],'works'=>$works??[],'sources'=>$sources??[]];require __DIR__.'/metadata.php'; ?>
<?php endif; ?>
<h2>Mis ejemplares relacionados</h2><?php if (!$copies): ?><p>No tienes ejemplares asociados a esta ficha.</p><?php endif; ?>
<?php foreach ($copies as $copy): ?><p><a href="<?= e(base_url('encyclopedia/copy?id='.$copy['Id'])) ?>"><?= e($copy['Title']) ?></a></p><?php endforeach; ?>
<?php endif; ?>
<?php if(!empty($participantCredits)): ?><h2>Funciones y participaciones confirmadas</h2><?php foreach($participantCredits as $c): ?><p><?php if(!empty($c['artist_id'])): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$c['artist_id'])) ?>"><?= e($c['name']) ?></a><?php endif; ?> · <?= e($c['role']) ?><?= !empty($c['instrument'])?' · '.e($c['instrument']):'' ?> · <?= e($c['track_title']??$c['recording_title']??$c['work_title']??$c['album_title']??'Álbum/edición') ?></p><?php endforeach; ?><?php endif; ?>
<p class="muted">Se muestra únicamente la información musical disponible en el catálogo. Las notas y carátulas personales se consultan en tu ejemplar.</p>
<a href="<?= e(base_url('vinyls')) ?>">Volver a mi colección</a></article>
