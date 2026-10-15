<?php
use App\Services\Metadata\MusicBrainzCreditGraph;
use App\Services\CreditPresentation;
$creditRecordings=$creditRecordings??[];$creditTracks=$creditTracks??[];$creditWorks=$creditWorks??[];
$projection=CreditPresentation::build($creditRows,$creditTracks,$creditWorks,$creditRecordings,$creditEdition??null,$creditAlbum??null);
$sections=$projection['sections'];$perTrack=$projection['per_track'];$contextRows=$projection['context'];
$render=static function(array $c):void{
 $r=$c['details']??[];$raw=$r['raw']??[];$attrs=$r['attributes']??$raw['attributes']??[];
 $scope=CreditPresentation::scope($c['scope']??'');
 ?>
 <li class="card credit-card"><strong><?php if(!empty($c['artist_id'])): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$c['artist_id'])) ?>"><?= e($c['name']) ?></a><?php else: ?><?= e($c['name']) ?><?php endif; ?></strong>
 <?php if(($c['credit_provider']??'')==='manual'): ?><p class="accent">Corrección manual · protegida</p><?php endif; ?>
 <?php if(!empty($r['target_kind'])&&!in_array($r['target_kind'],['artist','label','place'],true)): ?><p class="muted">Relación a <?= e($r['target_kind']) ?>; no se infiere una participación personal en este disco.</p><?php endif; ?>
 <p><?= e(CreditPresentation::role($c['role'],$attrs)) ?> <span class="muted">· Función original: <?= e($c['role']) ?></span><?php if(!empty($c['instrument'])): ?> · Instrumento: <?= e($c['instrument']) ?><?php endif; ?></p>
 <?php if(!empty($c['credited_as'])&&$c['credited_as']!==$c['name']): ?><p>Acreditado como <?= e($c['credited_as']) ?></p><?php endif; ?>
 <p class="muted"><?= e($scope) ?><?php if($attrs): ?> · Atributos: <?= e(implode(', ',$attrs)) ?><?php endif; ?></p>
 <?php if(!empty($r['attribute_credits'])): ?><p>Instrumentos / atributos acreditados: <?php foreach($r['attribute_credits'] as $attribute=>$credit): ?><?= e($attribute.' · '.$credit) ?>; <?php endforeach; ?></p><?php endif; ?>
 <?php if(($r['target_kind']??'')==='place'): $place=$r['target_details']??[]; ?><p><?php foreach(['type','address'] as $field)if(!empty($place[$field])): ?><?= e($place[$field]) ?> · <?php endif; ?><?= e($place['area']['name']??'') ?></p><?php endif; ?>
 <?php if(!empty($c['contexts'])): ?><details><summary><?= e(CreditPresentation::songs(count($c['contexts']))) ?></summary><ul><?php foreach($c['contexts'] as $name): ?><li><?= e($name) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
 <?php foreach($c['warnings']??[] as $warning): ?><p class="credit-scope-note"><?= e($warning) ?></p><?php endforeach; ?>
 <?php if($r): ?><p class="muted">Fechas de la relación: <?= e($r['begin']??'Sin inicio documentado') ?> · <?= e($r['end']??'Sin fin documentado') ?><?= !empty($r['ended'])?' · finalizada':'' ?></p><details class="credit-provenance"><summary>Relación original, instrumentos y procedencia</summary>
 <p>Origen: <?= e(($r['source_kind']??'').' · '.($r['source_mbid']??'')) ?></p><p>Destino: <?= e(($r['target_kind']??'').' · '.($r['target_mbid']??'')) ?></p>
 <?php if(!empty($r['source_url'])): ?><p><a href="<?= e($r['source_url']) ?>" target="_blank" rel="noopener">Relación en MusicBrainz</a></p><?php endif; ?>
 <?php if(!empty($r['fetched_at'])): ?><p>Consultado: <?= e(gmdate('Y-m-d H:i:s',$r['fetched_at'])) ?> UTC</p><?php endif; ?>
 <pre><?= e(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)) ?></pre></details><?php endif; ?>
 </li><?php
};
$renderGroup=static function(array $g)use($render):void{
 $c=$g['sample'];$r=$c['details']??[];$attrs=$r['attributes']??[];$n=count($g['entries']);
 ?>
 <li class="card credit-group"><div class="credit-person"><strong><?php if(!empty($c['artist_id'])): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$c['artist_id'])) ?>"><?= e($c['name']) ?></a><?php else: ?><?= e($c['name']) ?><?php endif; ?></strong>
 <span class="credit-function"><?= e(CreditPresentation::role($c['role'],$attrs)) ?></span></div>
 <?php if(!empty($c['instrument'])&&$c['role']!=='vocal'): ?><p>Instrumento: <?= e($c['instrument']) ?></p><?php endif; ?>
 <?php if(!empty($c['credited_as'])&&$c['credited_as']!==$c['name']): ?><p>Acreditado como <?= e($c['credited_as']) ?></p><?php endif; ?>
 <p class="muted"><?= e(CreditPresentation::scope($c['scope'])) ?> · <?= $n ?> <?= $n===1?'participación documentada':'participaciones documentadas' ?></p>
 <?php if(array_filter($g['entries'],fn($entry)=>($entry['credit_provider']??'')==='manual')): ?><p class="accent">Incluye una corrección manual · protegida</p><?php endif; ?>
 <?php if($g['contexts']): ?><details class="credit-songs"><summary><?= e(CreditPresentation::songs(count($g['contexts']))) ?></summary><ul><?php foreach($g['contexts'] as $name): ?><li><?= e($name) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
 <?php foreach(array_keys($g['warnings']) as $warning): ?><p class="credit-scope-note"><?= e($warning) ?></p><?php endforeach; ?>
 <details class="credit-originals"><summary>Ver <?= $n ?> <?= $n===1?'participación original':'participaciones originales' ?> y procedencia</summary><ul><?php foreach($g['entries'] as $entry)$render($entry); ?></ul></details></li>
 <?php
};
?>
<section class="credit-count-guide" aria-label="Cómo se cuentan los créditos"><p><strong><?= $projection['entries'] ?> <?= $projection['entries']===1?'participación documentada':'participaciones documentadas' ?></strong> · <?= $projection['cards'] ?> <?= $projection['cards']===1?'tarjeta agrupada':'tarjetas agrupadas' ?> · <?= $projection['identities'] ?> <?= $projection['identities']===1?'identidad con identificador':'identidades con identificador' ?></p>
<p class="muted"><?= $projection['rich'] ?> <?= $projection['rich']===1?'relación enriquecida':'relaciones enriquecidas' ?> y <?= $projection['previous'] ?> <?= $projection['previous']===1?'participación anterior o manual visible':'participaciones anteriores o manuales visibles' ?>. Una identidad puede tener varias funciones y participar en varias canciones. Los créditos anteriores conservados en la base pueden estar sustituidos visualmente por su relación enriquecida.</p>
<?php if($projection['unidentified']): ?><p><?= $projection['unidentified'] ?> entradas sin identidad inequívoca; se mantienen separadas.</p><?php endif; ?>
</section>
<div id="enc-credits" class="credit-sections">
<?php $opened=false;foreach(MusicBrainzCreditGraph::GROUPS as $key=>$label):$list=$sections[$key]; ?>
<details class="card credit-section" <?= $list&&!$opened?'open':'' ?>><summary><?= e($label) ?> <span class="muted"><?= count($list) ?> <?= count($list)===1?'tarjeta':'tarjetas' ?> · <?php $entryCount=array_sum(array_map(static fn($g)=>count($g['entries']),$list)); ?><?= $entryCount ?> <?= $entryCount===1?'participación':'participaciones' ?></span></summary>
<?php if(!$list): ?><p class="muted">Sin créditos documentados.</p><?php else:$opened=true; ?><ul><?php foreach($list as $g)$renderGroup($g); ?></ul><?php endif; ?></details>
<?php endforeach; ?>
<details class="card"><summary>Créditos específicos por canción</summary>
<?php if(!$creditTracks): ?><p class="muted">Sin canciones documentadas en esta vista.</p><?php endif; ?>
<?php foreach($creditTracks as $t): ?><details class="card" id="credits-track-<?= (int)$t['id'] ?>"><summary><?= e(($t['printed_number']??$t['position']).' · '.$t['title']) ?></summary>
<?php if(empty($perTrack[$t['id']])): ?><p class="muted">Sin créditos documentados para esta canción. Los créditos generales no se atribuyen automáticamente a cada tema.</p><?php else: ?><ul><?php foreach($perTrack[$t['id']] as $c)$render($c); ?></ul><?php endif; ?></details><?php endforeach; ?></details>
</div><p class="muted">Se muestra únicamente lo documentado por la fuente o introducido manualmente. Una relación desconocida se conserva con su denominación original; no demuestra que todos los participantes reales estén documentados.</p>
<script src="<?= e(base_url('assets/js/credits.js')) ?>" defer></script>

<?php if($contextRows): $otherRecordings=[];$localContext=[];$recordingIds=array_filter(array_column($creditRecordings,'musicbrainz_id'));
foreach($contextRows as $c){$d=$c['details'];if(($d['source_kind']??'')==='work'&&($d['target_kind']??'')==='recording'&&!in_array($d['target_mbid'],$recordingIds,true)){$otherRecordings[]=$c;}else $localContext[]=$c;}
?>
<details class="card" id="enc-context"><summary>Relaciones musicales de contexto (<?= count($contextRows) ?>) · no son participantes</summary>
<p>Se conservan separadas de los músicos, técnicos, compositores y entidades acreditadas. No se suman a sus categorías ni a los créditos por canción.</p>
<?php if($otherRecordings): ?><details class="card"><summary>Otras grabaciones de estas obras: <?= count($otherRecordings) ?> · ajenas a esta edición</summary><p>Son versiones o interpretaciones de las mismas obras. No constituyen créditos de las canciones de esta edición ni se consultan sus discografías.</p>
<?php $contextSources=[];foreach($otherRecordings as $c){$d=$c['details'];$contextSources[$d['source_url']]=$d['source_mbid'];}foreach($contextSources as $url=>$id): ?><p><a href="<?= e($url) ?>" rel="noopener" target="_blank">Consultar las relaciones de la obra en MusicBrainz</a></p><?php endforeach; ?><p class="muted">Los identificadores y relaciones originales permanecen almacenados; no se enumeran aquí como participantes del disco.</p></details><?php endif; ?>
<?php if($localContext): ?><ul><?php foreach($localContext as $c)$render($c); ?></ul><?php endif; ?>
</details><?php endif; ?>
