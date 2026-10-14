<link rel="stylesheet" href="<?= e(base_url('assets/css/encyclopedia.css')) ?>">
<section class="enc"><h1>Reconocimiento fotográfico no disponible</h1>
<p>Este entorno todavía no tiene habilitado el reconocimiento de fotografías. Puedes añadir el vinilo buscando sus datos musicales o introduciéndolos manualmente.</p>
<?php if (\App\Core\Encyclopedia::enabled()): ?><p><a href="<?= e(base_url('encyclopedia/search')) ?>">Añadir mediante búsqueda musical</a></p><?php endif; ?>
<p><a href="<?= e(base_url('vinyls/create')) ?>">Añadir manualmente</a></p></section>
