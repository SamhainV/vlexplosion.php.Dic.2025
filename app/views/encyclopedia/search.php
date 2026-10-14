<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<section class="enc">
<p class="accent">VLexPlosion · Enciclopedia</p>
<h1><?= !empty($target)||!empty($_SESSION['enc_drafts'][$token??'']['target'])?'Vincular ejemplar existente a MusicBrainz':'Añadir mediante búsqueda musical' ?></h1>
<p class="steps">Buscar → Elegir álbum → Elegir edición → Revisar → Guardar</p>
<p><a href="<?= e(base_url('vinyls/create')) ?>">Añadir manualmente</a> · <a href="<?= e(base_url('encyclopedia/search')) ?>">Nueva búsqueda</a></p>
<?php if (getenv('VLEX_ENC_FIXTURE') === '1'): ?><p class="alert">Modo de pruebas: respuestas musicales controladas; colección aislada.</p><?php else: ?><p class="muted">Consultas a MusicBrainz. No se envían fotografías.<?= defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true ? ' La colección de pruebas es ficticia.' : '' ?></p><?php endif; ?>
<?php if (!empty($error)): ?>
<p class="alert" role="alert"><?= e($error) ?><?php if (!empty($wait)): ?> Espera <?= (int)$wait ?> <?= $wait === 1 ? 'segundo' : 'segundos' ?> antes de reintentar.<?php endif; ?></p>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?>
<?php foreach (($retry ?? []) as $name => $value): if ($name === '_csrf' || !is_string($value) || (($retry['operation'] ?? '') === 'search' && in_array($name, ['artist','title','catalogue','barcode'], true))) continue; ?><input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
<?php if (($retry['operation'] ?? '') === 'search'): ?>
<div class="grid"><?php foreach (['artist'=>'Artista','title'=>'Álbum','catalogue'=>'Número de catálogo','barcode'=>'Código de barras'] as $field=>$label): ?><div><label for="retry-<?= e($field) ?>"><?= e($label) ?></label><input id="retry-<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e(is_string($retry[$field] ?? null) ? $retry[$field] : '') ?>"></div><?php endforeach; ?></div>
<?php endif; ?>
<p>Los datos se conservan. Puedes reintentar esta operación o continuar con el alta manual.</p>
<button>Reintentar consulta</button></form>
<?php elseif (isset($release)): ?>
<h2>Edición seleccionada</h2><p><?= e($release['data']['title']) ?> · <?= e((string)($release['data']['country'] ?? 'País desconocido')) ?> · <?= e((string)($release['data']['date'] ?? 'Fecha desconocida')) ?></p>
<p class="alert format-badge">Soporte: <?= e(\App\Core\ReleaseFormat::label($release['data']['media']??[])) ?><?php if (!\App\Core\ReleaseFormat::vinyl($release['data']['media']??[])): ?> · Esta edición no consta en vinilo; comprueba el formato antes de revisar.<?php endif; ?></p>
<p>La portada no basta para distinguir ediciones. Revisa país, sello y catálogo antes de guardar.</p>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?><input type="hidden" name="operation" value="review"><input type="hidden" name="draft" value="<?= e($token) ?>"><button>Revisar datos</button></form>
<?php elseif (isset($group) && !isset($result)): ?>
<h2>Álbum seleccionado: <?= e($group['data']['title']) ?></h2>
<p><?= e(implode(', ', array_map(static fn(array $a): string => $a['name'] ?? $a['artist']['name'] ?? '', $group['data']['artist-credit'] ?? []))) ?></p>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?><input type="hidden" name="operation" value="editions"><input type="hidden" name="draft" value="<?= e($token) ?>"><button>Mostrar ediciones</button></form>
<?php elseif (isset($result)): ?>
<h2><?= $result['entity_type'] === 'release' ? 'Ediciones candidatas' : 'Álbumes candidatos' ?></h2>
<?php if ($result['entity_type']==='release'): ?>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>" id="format-filter"><?= csrf_field() ?><input type="hidden" name="operation" value="filter"><input type="hidden" name="draft" value="<?= e($token) ?>"><label for="format-preference">Ediciones por soporte</label><select name="format_preference" id="format-preference"><?php foreach (['prefer'=>'Preferir vinilo','vinyl'=>'Solo ediciones con vinilo','all'=>'Todos los soportes'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($formatPreference??'prefer')===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button>Aplicar filtro</button></form>
<p class="muted">El filtro utiliza los formatos confirmados de esta página. No consulta más ediciones automáticamente; usa Más ediciones si hay otras páginas. Los formatos desconocidos no se identifican como vinilo.</p>
<?php endif; ?>
<p class="muted"><?= count($result['candidates']) ?> resultados en esta página; total indicado por la fuente: <?= isset($result['total']) ? (int)$result['total'] : 'desconocido' ?>. Ninguno se elige automáticamente.</p>
<?php if (!$result['candidates']): ?><p>No se encontraron coincidencias. Cambia la búsqueda o continúa manualmente.</p><?php endif; ?>
<div class="grid">
<?php foreach ($result['candidates'] as $candidate): ?>
<article class="card"><h2><?= e($candidate['title']) ?></h2>
<p><?= e(implode(', ', array_map(static fn(array $a): string => $a['name'] ?? $a['artist']['name'] ?? '', $candidate['artist-credit'] ?? []))) ?></p>
<p><?= e((string)($candidate['country'] ?? '')) ?> <?= e((string)($candidate['date'] ?? $candidate['first-release-date'] ?? '')) ?></p>
<?php foreach ($candidate['label-info'] ?? [] as $label): ?><p><?= e((string)($label['label']['name'] ?? '')) ?> · <?= e((string)($label['catalog-number'] ?? 'Catálogo desconocido')) ?></p><?php endforeach; ?>
<?php if ($result['entity_type']==='release'): ?><p class="format-badge">Soporte: <?= e(\App\Core\ReleaseFormat::label($candidate['media']??[])) ?></p><?php endif; ?>
<a target="_blank" rel="noopener" href="<?= e('https://musicbrainz.org/' . $result['entity_type'] . '/' . $candidate['id']) ?>">Consultar fuente</a>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?><input type="hidden" name="draft" value="<?= e($token) ?>"><input type="hidden" name="id" value="<?= e($candidate['id']) ?>"><input type="hidden" name="operation" value="<?= $result['entity_type'] === 'release' ? 'release' : 'album' ?>"><button>Elegir <?= $result['entity_type'] === 'release' ? 'edición' : 'álbum' ?></button></form></article>
<?php endforeach; ?></div>
<?php if (isset($group) && ($result['offset'] + ($result['page_count'] ?? count($result['candidates']))) < ($result['total'] ?? 0)): ?>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?><input type="hidden" name="draft" value="<?= e($token) ?>"><input type="hidden" name="operation" value="editions"><input type="hidden" name="offset" value="<?= (int)$result['offset'] + 25 ?>"><button>Más ediciones</button></form>
<?php endif; ?>
<?php else: ?>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>" id="enc-search"><?= csrf_field() ?><input type="hidden" name="operation" value="search">
<label for="search-format-preference">Preferencia de soporte</label><select id="search-format-preference" name="format_preference"><option value="prefer">Preferir vinilo</option><option value="vinyl">Solo ediciones con vinilo</option><option value="all">Todos los soportes</option></select>
<input type="hidden" name="target" value="<?= (int)($target??0) ?>"><label for="enc-year">Año orientativo (puedes vaciarlo para ampliar la búsqueda)</label><input id="enc-year" name="year" maxlength="4" value="<?= e($defaults['year']??'') ?>"><div class="grid"><div><label for="enc-artist">Artista</label><input id="enc-artist" name="artist" value="<?= e($defaults['artist']??'') ?>" maxlength="100" placeholder="Depeche Mode"></div><div><label for="enc-title">Álbum</label><input id="enc-title" name="title" value="<?= e($defaults['title']??'') ?>" maxlength="120" placeholder="Some Great Reward"></div><div><label for="enc-catalogue">Número de catálogo</label><input id="enc-catalogue" name="catalogue" value="<?= e($defaults['catalogue']??'') ?>" maxlength="120" placeholder="STUMM 19"></div><div><label for="enc-barcode">Código de barras</label><input id="enc-barcode" name="barcode" value="<?= e($defaults['barcode']??'') ?>" maxlength="64"></div></div><button>Buscar en MusicBrainz</button>
</form>
<p class="muted">Añadir mediante fotografía: pendiente de habilitar un proveedor visual autorizado. Sus candidatos utilizarán esta misma búsqueda y revisión.</p>
<?php endif; ?>
<?php if (!isset($group) && isset($result) && ($result['offset'] + ($result['page_count'] ?? count($result['candidates']))) < ($result['total'] ?? 0)): ?>
<form method="post" action="<?= e(base_url('encyclopedia/action')) ?>"><?= csrf_field() ?><input type="hidden" name="operation" value="search"><input type="hidden" name="offset" value="<?= (int)$result['offset'] + 25 ?>">
<?php foreach (['artist','title','catalogue','barcode','year','target','format_preference'] as $field): ?><input type="hidden" name="<?= e($field) ?>" value="<?= e(is_string($_POST[$field] ?? null) ? $_POST[$field] : ($field==='format_preference'?'prefer':'')) ?>"><?php endforeach; ?><button>Más resultados</button></form>
<?php endif; ?>
</section>
