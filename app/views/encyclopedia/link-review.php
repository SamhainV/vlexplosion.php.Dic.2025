<?php $linking=true;require __DIR__.'/review-music.php'; ?>
<h2>Comparación con mi ejemplar #<?= (int)$record['vinyl']['Id'] ?></h2>
<p>La vinculación no modifica ningún dato personal ni crea otro disco. La carátula, clasificaciones, autores, favoritos y notas se conservan.</p>
<div class="table-wrap"><table><thead><tr><th>Campo</th><th>Mi ficha (se conserva)</th><th>MusicBrainz</th></tr></thead><tbody>
<?php foreach (['Título'=>[$record['vinyl']['Title'],$a['title']], 'Artistas'=>[$record['vinyl']['author_name'],implode(', ',array_column($a['artists'],'name'))], 'Año'=>[$record['vinyl']['Release_date']??'',$a['original_date']??''], 'Formato'=>[$record['vinyl']['format_name']??'Pendiente',\App\Core\ReleaseFormat::label($ed['media'])]] as $label=>$values): ?><tr><th><?= e($label) ?></th><?php foreach ($values as $v): ?><td><?= e((string)$v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div>
<?php if ($record['edition']): ?><p class="alert">Se sustituirá únicamente la relación con la edición actual. Las entidades musicales anteriores y los datos personales permanecerán conservados.</p><?php endif; ?>
<?php if (!$record): ?><p>Ejemplar no disponible.</p><?php else: ?>
<form method="post" action="<?= e(base_url('encyclopedia/store')) ?>" id="enc-link-review"><?= csrf_field() ?><input type="hidden" name="draft" value="<?= e($token) ?>"><input type="hidden" name="confirmation_key" value="<?= e($_SESSION['enc_drafts'][$token]['save_key']) ?>">
<label><input type="checkbox" name="confirm" required> Confirmo expresamente esta edición para mi ejemplar existente</label><button>Confirmar vinculación</button><a href="<?= e(base_url('encyclopedia/link?id='.$record['vinyl']['Id'])) ?>">Cambiar selección</a></form>
<?php endif; ?></section>
