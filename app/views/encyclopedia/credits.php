<?php
$groups = [];
foreach ($creditRows as $c) {
    $key = ($c['artist_id'] ?? $c['name']) . "\0" . $c['role'] . "\0" . ($c['credited_as'] ?? '') . "\0" . ($c['instrument']??'');
    $groups[$key] ??= ['name' => $c['name'], 'artist_id'=>$c['artist_id']??null, 'credited_as'=>$c['credited_as']??null, 'instrument'=>$c['instrument']??null, 'role' => $c['role'], 'contexts' => []];
    if (!empty($c['context'])) { $groups[$key]['contexts'][$c['context']] = true; }
}
$roles = ['composer' => 'Composición', 'lyricist' => 'Letra', 'producer' => 'Producción', 'performer' => 'Interpretación', 'vocal' => 'Voz', 'mix'=>'Mezcla', 'engineer' => 'Ingeniería', 'photography' => 'Fotografía', 'design/illustration' => 'Diseño e ilustración'];
?>
<?php if (!$groups): ?><p>No hay créditos confirmados disponibles.</p><?php else: ?><ul id="enc-credits">
<?php foreach ($groups as $group): ?><li class="card"><strong><?php if ($group['artist_id']): ?><a href="<?= e(base_url('encyclopedia/artist?id='.$group['artist_id'])) ?>"><?= e($group['name']) ?></a><?php else: ?><?= e($group['name']) ?><?php endif; ?></strong><?php if ($group['credited_as'] && $group['credited_as']!==$group['name']): ?> <span class="muted">(acreditado como <?= e($group['credited_as']) ?>)</span><?php endif; ?> · <?= e($roles[$group['role']] ?? $group['role']) ?><?php if ($group['instrument']): ?> · Instrumento: <?= e($group['instrument']) ?><?php endif; ?>
<?php if ($group['contexts']): ?><details><summary>Ver <?= count($group['contexts']) ?> canciones relacionadas</summary><ul><?php foreach (array_keys($group['contexts']) as $context): ?><li><?= e($context) ?></li><?php endforeach; ?></ul></details><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
