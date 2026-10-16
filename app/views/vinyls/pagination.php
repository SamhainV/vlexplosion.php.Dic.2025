<?php
/** Presentation only: the existing paginator and collection_query retain filtering/sorting. */
$page=(int)$p->page;$pages=(int)$p->pages;
$near=array_unique(array_merge([1,$pages],range(max(1,$page-2),min($pages,$page+2))));sort($near);
$pageUrl=static fn(int $n):string=>base_url('vinyls?'.collection_query($n,$sort));
?>
<nav class="collection-pagination" aria-label="Paginación de la colección">
  <p class="collection-page-info">Página <strong><?= $page ?></strong> de <strong><?= $pages ?></strong><span> Tu archivo, disco a disco.</span></p>
  <div class="vl-page-controls">
    <div class="vl-page-direction">
      <?php if($page>1): ?><a href="<?= e($pageUrl(1)) ?>" aria-label="Primera página">« <span>Primera</span></a><a href="<?= e($pageUrl($page-1)) ?>" rel="prev" aria-label="Página anterior">‹ <span>Anterior</span></a><?php else: ?><span class="vl-page-disabled" aria-disabled="true">« <span>Primera</span></span><span class="vl-page-disabled" aria-disabled="true">‹ <span>Anterior</span></span><?php endif; ?>
    </div>
    <div class="vl-page-numbers">
      <?php $previous=0;foreach($near as $n): ?>
        <?php if($previous&&$n>$previous+1): ?><span class="vl-page-gap" aria-hidden="true">…</span><?php endif; ?>
        <a href="<?= e($pageUrl($n)) ?>" aria-label="Página <?= $n ?>" <?= $n===$page?'aria-current="page"':'' ?>><?= $n ?></a>
      <?php $previous=$n;endforeach; ?>
    </div>
    <div class="vl-page-direction">
      <?php if($page<$pages): ?><a href="<?= e($pageUrl($page+1)) ?>" rel="next" aria-label="Página siguiente"><span>Siguiente</span> ›</a><a href="<?= e($pageUrl($pages)) ?>" aria-label="Última página"><span>Última</span> »</a><?php else: ?><span class="vl-page-disabled" aria-disabled="true"><span>Siguiente</span> ›</span><span class="vl-page-disabled" aria-disabled="true"><span>Última</span> »</span><?php endif; ?>
    </div>
  </div>
</nav>
