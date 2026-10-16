<?php

use App\Core\Auth;
$collectionPage = ($view ?? '') === 'vinyls/index';
$currentView = $view ?? '';
$visualPage = $collectionPage ? 'collection' : (($view ?? '') === 'vinyls/show' ? 'detail' : (($view ?? '') === 'vinyls/create' ? 'editor' : (str_starts_with($view ?? '', 'encyclopedia/') ? 'encyclopedia' : 'standard')));
?>
<!doctype html>
<html lang="es" data-vl-theme="archivo">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VLexPlosion</title>
  <script src="<?= e(base_url('assets/js/themes.js')) ?>"></script>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?= e(base_url('assets/js/covers.js')) ?>"></script>
<?php if ($collectionPage): ?><link rel="stylesheet" href="<?= e(base_url('assets/css/collection.css')) ?>"><?php endif; ?>
  <link rel="stylesheet" href="<?= e(base_url('assets/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/evolution.css')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/themes.css')) ?>">
</head>

<body
  class="min-h-screen text-zinc-100 bg-black bg-cover bg-fixed bg-center vl-app vl-<?= e($visualPage) ?>-page"
  style="
    background-image:
      linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.72)),
      
      url('<?= e(base_url(app_background())) ?>');
  ">

  <div class="min-h-screen bg-black/10">

    <a class="vl-skip-link" href="#main-content">Saltar al contenido</a>
    <header class="vl-header">
      <div class="vl-header-inner">
        <a href="<?= e(base_url(Auth::check() ? 'vinyls' : 'login')) ?>" class="vl-brand" aria-label="VLexPlosion · Inicio">
          <span class="vl-brand-record" aria-hidden="true"><i></i></span>
          <span>VLexPlosion<small>EL ARCHIVO DE TU MÚSICA</small></span>
        </a>
        <nav class="vl-navigation" aria-label="Navegación principal">
          <?php if (Auth::check()): ?>
            <a href="<?= e(base_url('vinyls')) ?>" <?= str_starts_with($currentView,'vinyls/') ? 'aria-current="page"' : '' ?>>Colección</a>
            <?php if (\App\Core\Encyclopedia::enabled()): ?><a href="<?= e(base_url('encyclopedia/search')) ?>" <?= str_starts_with($currentView,'encyclopedia/') ? 'aria-current="page"' : '' ?>>Búsqueda musical</a><?php endif; ?>
            <?php if (\App\Models\CoverArt::available()): ?><a href="<?= e(base_url('covers')) ?>" <?= str_starts_with($currentView,'covers/') ? 'aria-current="page"' : '' ?>>Carátulas</a><?php endif; ?>
          <?php else: ?><span class="vl-nav-note">Cada disco, una historia.</span><?php endif; ?>
        </nav>
        <div class="vl-account">
          <details class="vl-theme-picker" data-theme-picker hidden>
            <summary aria-label="Tema actual: Archivo Musical. Cambiar tema"><span class="vl-theme-icon" aria-hidden="true">◐</span><span class="vl-theme-caption">Tema</span><span class="vl-theme-chevron" aria-hidden="true">⌄</span></summary>
            <div class="vl-theme-panel" role="group" aria-label="Tema de color">
              <p class="vl-theme-title">ELIGE TU ATMÓSFERA</p>
              <?php foreach (['archivo'=>'Archivo Musical','clasico'=>'Vinilo Clásico','borgona'=>'Borgoña','ambar'=>'Retro Ámbar'] as $themeKey=>$themeName): ?>
              <button type="button" class="vl-theme-option" data-theme-choice="<?= e($themeKey) ?>" aria-pressed="<?= $themeKey==='archivo'?'true':'false' ?>"><span class="vl-theme-swatches" data-theme-swatch="<?= e($themeKey) ?>" aria-hidden="true"><i></i><i></i><i></i></span><span><?= e($themeName) ?></span><span class="vl-theme-check" aria-hidden="true">✓</span></button>
              <?php endforeach; ?>
              <p class="vl-theme-help" data-theme-storage>Se recuerda en este navegador.</p>
            </div>
          </details>
          <span class="vl-theme-announcement" data-theme-status role="status" aria-live="polite" aria-atomic="true"></span>

          <?php if (defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true): ?><span class="vl-preview-badge">Vista previa · datos ficticios</span><?php endif; ?>
          <?php if (Auth::check()): ?><form method="POST" action="<?= e(base_url('logout')) ?>"><?= csrf_field() ?><button type="submit" aria-label="Cerrar sesión">Salir <span aria-hidden="true">↗</span></button></form><?php else: ?><a href="<?= e(base_url('login')) ?>" class="vl-login-link">Acceder →</a><?php endif; ?>
        </div>
      </div>
    </header>

    <?php if (defined('VLEXPLOSION_TESTING') && VLEXPLOSION_TESTING === true): ?><div class="vl-preview-ribbon">Vista previa · colección y carátulas ficticias</div><?php endif; ?>
    <main id="main-content" class="vl-main">
