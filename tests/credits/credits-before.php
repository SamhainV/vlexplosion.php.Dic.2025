<?php
use App\Services\Metadata\MusicBrainzCreditGraph;
$creditRecordings=$creditRecordings??[];$contextRows=[];$creditTracks=$creditTracks??[];$creditWorks=$creditWorks??[];$sections=array_fill_keys(array_keys(MusicBrainzCreditGraph::GROUPS),[]);$perTrack=[];
foreach($creditRows as $c){
 $rel=$c['relation']??(!empty($c['relation_json'])?json_decode($c['relation_json'],true):null);$raw=$rel['raw']??[];
 $c['details']=$rel;$c['category']=$rel['category']??MusicBrainzCreditGraph::category($c['role'],$rel['target_kind']??'');
 $c['scope']=$rel['source_kind']??(!empty($c['album_id'])?'release-group':(!empty($c['edition_id'])?'release':(!empty($c['track_id'])?'track':(!empty($c['recording_id'])?'recording':'work'))));
 if($rel&&MusicBrainzCreditGraph::isContext($rel)){$contextRows[]=$c;continue;}
 $contexts=[];
 foreach($creditTracks as $t){
  $matches=(!empty($c['track_id'])&&(int)$c['track_id']===(int)$t['id'])||(!empty($c['recording_id'])&&(int)$c['recording_id']===(int)$t['recording_id']);
  if(!empty($c['work_id']))foreach($creditWorks as $w)if((int)$w['id']===(int)$c['work_id']&&(int)($w['recording_id']??0)===(int)$t['recording_id'])$matches=true;
  if($matches){$contexts[$t['id']]=$t['title'];$perTrack[$t['id']][]=$c;}
 }
 if(!$contexts&&!empty($c['context']))$contexts[]=$c['context'];$c['contexts']=$contexts;
 $sections[$c['category']][]=$c;
}
$render=static function(array $c):void{
 $r=$c['details']??[];$raw=$r['raw']??[];$attrs=$r['attributes']??$raw['attributes']??[];
 $scope=['release-group'=>'Álbum / grupo de ediciones','release'=>'Edición concreta','recording'=>'Grabación','work'=>'Obra / composición','track'=>'Canción'][$c['scope']??'']??'Ámbito conservado';
 ?>
 <li class="card credit-card"><strong><?php if(!empty($c['artist_id'])): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$c['artist_id'])) ?>"><?= e($c['name']) ?></a><?php else: ?><?= e($c['name']) ?><?php endif; ?></strong>
 <?php if(($c['credit_provider']??'')==='manual'): ?><p class="accent">Corrección manual · protegida</p><?php endif; ?>
 <?php if(!empty($r['target_kind'])&&!in_array($r['target_kind'],['artist','label','place'],true)): ?><p class="muted">Relación a <?= e($r['target_kind']) ?>; no se infiere una participación personal en este disco.</p><?php endif; ?>
 <p><?= e($c['role']) ?><?php if(!empty($c['instrument'])): ?> · Instrumento: <?= e($c['instrument']) ?><?php endif; ?></p>
 <?php if(!empty($c['credited_as'])&&$c['credited_as']!==$c['name']): ?><p>Acreditado como <?= e($c['credited_as']) ?></p><?php endif; ?>
 <p class="muted"><?= e($scope) ?><?php if($attrs): ?> · Atributos: <?= e(implode(', ',$attrs)) ?><?php endif; ?></p>
 <?php if(!empty($r['attribute_credits'])): ?><p>Instrumentos / atributos acreditados: <?php foreach($r['attribute_credits'] as $attribute=>$credit): ?><?= e($attribute.' · '.$credit) ?>; <?php endforeach; ?></p><?php endif; ?>
 <?php if(($r['target_kind']??'')==='place'): $place=$r['target_details']??[]; ?><p><?php foreach(['type','address'] as $field)if(!empty($place[$field])): ?><?= e($place[$field]) ?> · <?php endif; ?><?= e($place['area']['name']??'') ?></p><?php endif; ?>
 <?php if(!empty($c['contexts'])): ?><details><summary>Ver <?= count($c['contexts']) ?> canciones relacionadas</summary><ul><?php foreach($c['contexts'] as $name): ?><li><?= e($name) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
 <?php if($r): ?><details class="credit-provenance"><summary>Relación original, instrumentos y procedencia</summary>
 <p>Origen: <?= e(($r['source_kind']??'').' · '.($r['source_mbid']??'')) ?></p><p>Destino: <?= e(($r['target_kind']??'').' · '.($r['target_mbid']??'')) ?></p>
 <?php if(!empty($r['source_url'])): ?><p><a href="<?= e($r['source_url']) ?>" target="_blank" rel="noopener">Relación en MusicBrainz</a></p><?php endif; ?>
 <?php if(!empty($r['fetched_at'])): ?><p>Consultado: <?= e(gmdate('Y-m-d H:i:s',$r['fetched_at'])) ?> UTC</p><?php endif; ?>
 <pre><?= e(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)) ?></pre></details><?php endif; ?>
 </li><?php
};
?>
<div id="enc-credits" class="credit-sections">
<?php $opened=false;foreach(MusicBrainzCreditGraph::GROUPS as $key=>$label):$list=$sections[$key]; ?>
<details class="card credit-section" <?= $list&&!$opened?'open':'' ?>><summary><?= e($label) ?> <span class="muted">(<?= count($list) ?>)</span></summary>
<?php if(!$list): ?><p class="muted">Sin créditos documentados.</p><?php else:$opened=true; ?><ul><?php foreach($list as $c)$render($c); ?></ul><?php endif; ?></details>
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
