<?php
declare(strict_types=1);
// The existing router checks loopback, socket identity and datadir before DB access.
require_once __DIR__.'/fixtures.php';
require_once dirname(__DIR__,2).'/app/Services/CoverArtHttp.php';
\App\Services\CoverArtHttp::$transport='caa_fixture';
return require dirname(__DIR__,2).'/tools/encyclopedia_router.php';
