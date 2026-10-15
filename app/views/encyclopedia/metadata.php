<?php
(static function(array $record): void {
$r=$record; $a=$r['album']; $ed=$r['edition']; $media=$r['media']??[];
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<section class="enc enc-metadata" id="enc-shared"><p class="accent"><?= $ed?(!empty($ed['musicbrainz_release_id'])?'Metadatos compartidos · MusicBrainz':'Información musical manual · Borrador o revisión'):(!empty($a['draft_owner_id'])?'Borrador manual privado':'Información musical disponible') ?></p>
<?php if ($ed): ?><p class="muted">Información musical de solo lectura en este formulario personal. Las correcciones de tu ejemplar no modifican esta ficha musical.</p><?php endif; ?>
<h2><?= e($a['title']) ?></h2>
<p><?php foreach ($r['artists'] as $artist): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$artist['id'])) ?>"><?= e($artist['name']) ?></a> <?php endforeach; ?></p>
<p>Publicación original: <?= e($a['original_date']??'Desconocida') ?> · Tipo: <?= e($a['release_type']??'Desconocido') ?></p>
<?php if(!empty($a['language'])): ?><p>Idioma: <?= e($a['language']) ?></p><?php endif; ?>
<?php if(!empty($a['description'])): ?><p style="white-space:pre-wrap"><?= e($a['description']) ?></p><?php endif; ?>
<p>Géneros: <?= e(implode(', ',array_column($r['genres'],'name'))?:'Sin información confirmada') ?></p>
<?php if (!empty($a['id'])): ?><p><a href="<?= e(base_url('encyclopedia/album?id='.$a['id'])) ?>">Consultar álbum</a><?php if ($ed): ?> · <a href="<?= e(base_url('encyclopedia/edition?id='.$ed['id'])) ?>">Consultar edición</a><?php endif; ?></p><?php endif; ?>
<?php if ($ed): ?>
<h3>Edición musical</h3>
<p class="<?= \App\Core\ReleaseFormat::vinyl($media)?'card':'alert' ?> format-badge">Soporte: <?= e(\App\Core\ReleaseFormat::label($media)) ?><?php if (!\App\Core\ReleaseFormat::vinyl($media)): ?> · No consta soporte en vinilo<?php endif; ?></p>
<dl><?php foreach (['country'=>'País','manufacture_country'=>'País de fabricación','release_date'=>'Fecha de esta edición','barcode'=>'Código de barras','packaging'=>'Presentación','edition_type'=>'Tipo de edición','pressing_plant'=>'Planta de prensado'] as $key=>$label): ?><dt><?= e($label) ?></dt><dd><?= e((string)($ed[$key]??'Desconocido')) ?></dd><?php endforeach; ?>
<?php if ($ed['remastered']!==null): ?><dt>Remasterizada</dt><dd><?= $ed['remastered']?'Sí':'No' ?></dd><?php endif; ?></dl>
<h3>Sellos y catálogos de esta edición</h3><?php foreach ($r['labels'] as $label): ?><p><?php if(!empty($label['id'])): ?><a href="<?= e(base_url('encyclopedia/label?id='.$label['id'])) ?>"><?= e($label['name']) ?></a><?php else: ?><?= e($label['name']) ?><?php endif; ?> · <?= e($label['catalogue_number']?:'Catálogo desconocido') ?></p><?php endforeach; ?>
<?php if (!$r['labels']): ?><p>No hay sellos confirmados.</p><?php endif; ?>
<?php foreach ($media as $medium): ?><p>Soporte <?= (int)$medium['position'] ?>: <?= e($medium['format']??'Formato desconocido') ?><?php foreach (['rpm'=>' rpm','diameter_inches'=>' pulgadas','color'=>'','weight_grams'=>' g'] as $key=>$suffix): if ($medium[$key]!==null): ?> · <?= e((string)$medium[$key].$suffix) ?><?php endif; endforeach; ?></p><?php endforeach; ?>
<?php if (!empty($ed['musicbrainz_release_id'])): ?><a href="<?= e('https://musicbrainz.org/release/'.$ed['musicbrainz_release_id']) ?>" target="_blank" rel="noopener">Fuente de la edición</a><?php endif; ?>
<?php else: ?><p>Edición desconocida. No se ha inferido una edición a partir del título.</p><?php endif; ?>
<h3>Canciones y duraciones</h3><?php if (!$r['tracks']): ?><p>No hay canciones confirmadas.</p><?php else: ?><div class="table-wrap"><table id="enc-tracks"><thead><tr><th>Soporte</th><th>Pista</th><th>Título</th><th>Duración</th><th>Créditos</th></tr></thead><tbody><?php foreach ($r['tracks'] as $track): ?><tr><td><?= (int)$track['medium_position'] ?> · <?= e($track['format']??'Desconocido') ?></td><td><?= e($track['printed_number']??(string)$track['position']) ?></td><td><?= e($track['title']) ?></td><td><?= $track['duration_ms']===null?'—':sprintf('%d:%02d',intdiv((int)$track['duration_ms'],60000),intdiv((int)$track['duration_ms'],1000)%60) ?></td><td><a href="#credits-track-<?= (int)$track['id'] ?>">Ver créditos</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<h2>CRÉDITOS Y PERSONAL</h2>
<?php $creditEdition=$r['edition']??null;$creditAlbum=$r['album']??null;$creditRecordings=$r['recordings']??[];$creditTracks=$r['tracks']??[];$creditWorks=$r['works']??[];$creditRows=array_map(static fn(array $c):array=>array_merge($c,['name'=>$c['name']??$c['organisation'],'context'=>$c['track_title']??$c['recording_title']??$c['work_title']??null]),$r['credits']);require __DIR__.'/credits.php'; ?>
<h3>Grabaciones y composiciones</h3>
<?php foreach ($r['recordings']??[] as $rec): ?><p>Grabación: <a href="<?= e(base_url('encyclopedia/recording?id='.$rec['id'])) ?>"><?= e($rec['title']) ?></a></p><?php endforeach; ?>
<?php foreach ($r['works']??[] as $work): ?><p>Composición: <a href="<?= e(base_url('encyclopedia/work?id='.$work['id'])) ?>"><?= e($work['title']) ?></a></p><?php endforeach; ?>
<h3>Fuentes disponibles</h3><?php foreach ($r['sources']??[] as $source): ?><p><?= e($source['provider'].' · '.$source['fetched_at']) ?><?php if ($source['source_url']): ?> · <a href="<?= e($source['source_url']) ?>" rel="noopener" target="_blank">Consultar fuente</a><?php endif; ?></p><?php endforeach; ?>
<p class="muted">Los enlaces muestran relaciones musicales almacenadas. La información puede ser parcial; los datos ausentes quedan desconocidos.</p>
</section>

<?php })($record); ?>
