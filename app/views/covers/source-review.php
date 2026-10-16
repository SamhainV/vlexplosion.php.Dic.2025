<?php
use App\Models\CoverArtSource;
if(!$sourceReview)return;
$sourceReady=CoverArtSource::available();
if($sourceReview['applicable']): ?>
<section class="caa-source" aria-labelledby="caa-source-title">
<h2 id="caa-source-title">Edición independiente para la carátula</h2>
<p>Tu ficha musical es una revisión privada. Elegir una fuente de imagen no cambia su vinculación enciclopédica, canciones, créditos ni correcciones.</p>
<?php if($sourceReview['active']): ?><p class="caa-feedback">Fuente visual confirmada: <a href="<?= e('https://musicbrainz.org/release/'.$sourceReview['active']['release_mbid']) ?>" target="_blank" rel="noopener"><?= e($sourceReview['active']['release_mbid']) ?></a>. Solo se utiliza para la carátula.</p>
<?php elseif($sourceReview['stale']): ?><p class="caa-feedback caa-feedback--warning" role="status">La confirmación anterior ya no coincide con la revisión o la edición de origen. No se utilizará: vuelve a comparar y confirmar.</p>
<?php else: ?><p>No hay una fuente visual confirmada. La procedencia histórica no autoriza por sí sola a descargar ni seleccionar una imagen.</p><?php endif; ?>
<?php if(!$sourceReady): ?><p>Esta opción requiere la actualización 008 de preferencias visuales.</p>
<?php elseif(!$sourceReview['candidates']): ?><p>No hay una edición histórica verificable en la asociación actual. No inferimos la identidad por títulos similares.</p>
<?php else: ?>
<details id="caa-source-review"><summary><?= $sourceReview['active']?'Cambiar edición para la carátula':'Elegir edición para la carátula' ?></summary>
<p>Compara las ediciones. No se ha consultado ningún proveedor al abrir esta pantalla. Puedes cerrar este panel sin confirmar y conservar todo como está.</p>
<?php foreach($sourceReview['candidates'] as $candidate): ?>
<div class="caa-comparison table-wrap"><table><caption>Revisión actual frente a edición de origen disponible</caption><thead><tr><th scope="col">Dato</th><th scope="col">Revisión privada actual</th><th scope="col">Edición candidata · no confirmada automáticamente</th></tr></thead><tbody>
<?php
$describe=static function(array $d):array{
 $ed=$d['edition'];return ['Álbum'=>$d['album']['title'],'País'=>$ed['country']??'Desconocido','Fecha'=>$ed['release_date']??'Desconocida','Sellos y catálogo'=>implode(' · ',array_map(static fn($l)=>$l['name'].' / '.($l['catalogue_number']??'Sin catálogo'),$d['labels']))?:'No documentados','Soporte'=>implode(' · ',array_filter(array_column($d['media'],'format')))?:'No documentado','Pistas'=>(string)count($d['tracks']),'Código de barras'=>$ed['barcode']??'No documentado','Presentación'=>$ed['packaging']??'No documentada','Release MBID'=>$ed['musicbrainz_release_id']??'Sin MBID activo'];};
$currentDescription=$describe($sourceReview['current']);$candidateDescription=$describe($candidate);
foreach($currentDescription as $label=>$value): ?><tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td><td><?= e($candidateDescription[$label]) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<details><summary>Comparar canciones de ambas ediciones</summary><div class="caa-source-tracks"><?php foreach(['Revisión actual'=>$sourceReview['current'],'Edición candidata'=>$candidate] as $heading=>$description): ?><div><h3><?= e($heading) ?></h3><ol><?php foreach($description['tracks'] as $track): ?><li><?= e($track['title']) ?><?= $track['duration_ms']===null?'':' · '.sprintf('%d:%02d',intdiv((int)$track['duration_ms'],60000),intdiv((int)$track['duration_ms'],1000)%60) ?></li><?php endforeach; ?></ol></div><?php endforeach; ?></div></details>
<p><a href="<?= e('https://musicbrainz.org/release/'.$candidate['edition']['musicbrainz_release_id']) ?>" target="_blank" rel="noopener">Consultar esta edición en MusicBrainz</a></p>
<form method="post" action="<?= e(base_url('covers/action')) ?>" class="caa-source-form" data-caa-action><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="source_edition" value="<?= (int)$candidate['edition']['id'] ?>"><input type="hidden" name="source_context" value="<?= e($candidate['context']) ?>">
<label><input type="checkbox" name="confirm" value="1" required> Confirmo que esta edición es la fuente correcta únicamente para mi carátula. Mantener la revisión musical actual.</label>
<button class="collection-button" name="action" value="confirm-source">Confirmar edición solo para la carátula</button></form>
<?php endforeach; ?></details>
<?php endif; ?>
</section>
<?php endif; ?>
<?php if($sourceReady&&$sourceReview['stored']!==null): ?>
<form method="post" action="<?= e(base_url('covers/action')) ?>" class="caa-source-form" data-caa-action><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><label><input type="checkbox" name="confirm" value="1" required> Retirar la fuente de imagen sin borrar ninguna carátula ni cambiar los datos musicales.</label><button class="collection-button" name="action" value="remove-source">Retirar edición para la carátula</button></form>
<?php endif; ?>
