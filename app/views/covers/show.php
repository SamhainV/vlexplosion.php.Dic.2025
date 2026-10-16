<?php
use App\Models\CoverArt;
use App\Services\CoverArtClient;
$id=(int)($vinyl['Id']??0);
$button=function(string $action,string $label)use($id){?>
<form method="post" action="<?= e(base_url('covers/action')) ?>" class="caa-action" data-caa-action><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="collection-button" name="action" value="<?= e($action) ?>"><?= e($label) ?></button></form>
<?php }; ?>
<section class="caa-panel" aria-labelledby="caa-title">
<a href="<?= e(base_url($id?'vinyls/show?id='.$id:'vinyls')) ?>">← Volver <?= $id?'a la ficha':'a la colección' ?></a>
<p class="collection-eyebrow">IMÁGENES · COVER ART ARCHIVE</p>
<h1 id="caa-title"><?= $id?'Carátula de '.e($vinyl['Title']):'Carátulas de mi colección' ?></h1>
<?php if($message):?><p role="<?= $message['kind']==='error'?'alert':'status' ?>" aria-live="<?= $message['kind']==='error'?'assertive':'polite' ?>" class="collection-flash caa-feedback caa-feedback--<?= e($message['kind']) ?>"><?= e($message['text']) ?></p><?php endif;?>
<p class="caa-activity" role="status" aria-live="polite" aria-atomic="true" data-caa-activity hidden></p>
<?php if($vinyl): ?>
<div class="vl-cover-workspace"><div class="vl-cover-artwork">
<img class="caa-preview" src="<?= e(CoverArt::url($vinyl)) ?>" alt="Carátula actualmente seleccionada"><p><?= e(CoverArt::display($vinyl)['origin']) ?></p>
</div><div class="vl-cover-controls">
<p>Tu imagen manual tiene prioridad por defecto. Cambiar la fuente no borra ninguna imagen.</p>
<?php require __DIR__.'/source-review.php'; ?>
<?php if(CoverArtClient::mbid($vinyl['release_mbid'])): ?>
<?php $button('fetch',empty($info['image']['storage_key'])?'Buscar carátula automática':'Comprobar carátula automática (caché de 24 h)'); ?>
<?php if(!empty($info['exact']['storage_key'])): $button('refresh','Actualizar carátula automática');$button('auto',!empty($vinyl['visual_source'])?'Usar carátula de la edición confirmada':'Usar carátula de edición exacta');?>
<a href="<?= e(base_url('covers/image?id='.$id.'&scope=exact')) ?>">Ver carátula automática de edición</a>
<?php endif; ?>
<?php if(!empty($info['image']['storage_key'])): ?><dl><dt>Fuente</dt><dd>Cover Art Archive · <?= e($info['scope']==='release'?(!empty($vinyl['visual_source'])?'edición confirmada solo para la carátula':'edición exacta'):'grupo de ediciones · aproximada') ?></dd><dt>Edición de origen</dt><dd><?= e($info['image']['release_mbid']??'') ?></dd><dt>Recuperada (UTC)</dt><dd><?= e($info['image']['retrieved_at']??'') ?></dd><dt>Última consulta</dt><dd><?= e(['ready'=>'Disponible','missing'=>'Sin portada en la última consulta','error'=>'Último intento fallido; conservamos la imagen','waiting'=>'En espera del proveedor'][$info['image']['status']??'']??'Sin consultar') ?></dd><dt>URL de procedencia</dt><dd><a href="<?= e($info['image']['original_url']??'') ?>">Consultar origen en Cover Art Archive</a></dd></dl>
<?php endif;?>
<?php elseif(empty($sourceReview['applicable'])): ?><p>Primero identifica y confirma la edición musical. No asociamos imágenes por semejanza de títulos.</p><a href="<?= e(base_url('encyclopedia/link?id='.$id)) ?>">Vincular edición MusicBrainz</a><?php endif;?>
<?php if(!empty($vinyl['Image_Path']))$button('manual','Usar mi carátula');?>
<?php if(CoverArtClient::mbid($vinyl['group_mbid'])): ?><details><summary>Valorar una alternativa aproximada</summary><p>Una portada del grupo puede pertenecer a otra edición. Solo se seleccionará si lo confirmas expresamente.</p><?php $button('fetch-group','Buscar alternativa de grupo');?><?php if(!empty($info['alternative']['storage_key'])): ?><p>Edición de origen de la alternativa: <?= e($info['alternative']['release_mbid']) ?></p><a href="<?= e(base_url('covers/image?id='.$id.'&scope=group')) ?>">Ver alternativa descargada</a><?php $button('group','Confirmar: usar alternativa aproximada');endif;?></details><?php endif;?>
<a href="<?= e(base_url('vinyls/edit?id='.$id)) ?>">Subir o sustituir mi carátula</a>
</div></div>
<?php else: ?>
<p>Vista previa: <strong><?= count($eligible) ?></strong> discos elegibles (hasta 1.000 por lote): sin imagen manual, con edición MusicBrainz activa o fuente visual expresamente confirmada y sin carátula automática local. Los demás se conservan sin cambios.</p>
<p>Puedes procesar hasta cinco discos seguidos o avanzar uno a uno. Cada petición procesa un disco, con un máximo de 18 segundos de red y tres intentos. No busca alternativas de grupo ni inicia peticiones al abrir esta pantalla.</p>
<?php if(!$batch||$batch['state']==='complete')$button('start','Preparar lote (sin descargar todavía)'); ?>
<?php if($batch): ?><h2>Estado: <?= e(['paused'=>'Pausado','running'=>'En curso','complete'=>'Terminado'][$batch['state']]??'Pausado') ?></h2>
<dl class="caa-counts"><?php foreach(['reviewed'=>'Revisados','downloaded'=>'Con carátula','missing'=>'Sin imagen','skipped'=>'Omitidos','errors'=>'Errores'] as $key=>$label):?><div><dt><?= e($label) ?></dt><dd><?= (int)$batch[$key] ?></dd></div><?php endforeach;?></dl>
<p><?= (int)$batch['cursor'] ?> de <?= count($batch['queue']) ?> · Los errores no alteran el disco ni su imagen.</p>
<?php if($batch['retry_at']>time()):?><p role="status">Pausa del proveedor hasta <?= e(gmdate('H:i:s',$batch['retry_at'])) ?> UTC. Continúa después; no se reintenta automáticamente.</p><?php endif;?>
<?php if($batch['state']!=='complete'): ?>
<div data-caa-batch data-endpoint="<?= e(base_url('covers/action')) ?>" data-csrf="<?= e(\App\Core\Csrf::token()) ?>">
<button type="button" class="collection-button" data-caa-run>Procesar hasta cinco discos</button>
<button type="button" class="collection-button" data-caa-stop hidden>Detener después del disco en curso</button>
<p role="status" aria-live="polite" data-caa-status></p></div>

<?php $button('step','Continuar: procesar un disco');$button('pause','Detener y conservar progreso');endif;endif;?>
<?php endif;?>
<p class="caa-muted">Las imágenes pueden estar protegidas por derechos de autor. Se almacenan localmente para tu colección privada; no se incorporan al repositorio.</p>
</section>

<script src="<?= e(base_url('assets/js/cover-art.js')) ?>" defer></script>
