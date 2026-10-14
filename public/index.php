<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Core/HttpException.php';
require_once __DIR__ . '/../app/Core/ErrorHandler.php';
\App\Core\ErrorHandler::install();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require_once __DIR__ . '/../app/Core/bootstrap.php';


use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\VinylController;

// Routes
$router = new Router();

// Auth
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/vinyls/show', [VinylController::class, 'show']);
$router->get('/vinyls/create', [VinylController::class, 'create']);
$router->post('/vinyls/store', [VinylController::class, 'store']);
$router->post('/vinyls/delete', [VinylController::class, 'destroy']);



$router->get('/vinyls/edit', [VinylController::class, 'edit']);
$router->post('/vinyls/update', [VinylController::class, 'update']);

// Vinyls
$router->get('/vinyls', [VinylController::class, 'index']);

$router->get('/vinyls/ai', [\App\Controllers\AiVinylController::class, 'start']);
$router->post('/vinyls/ai/api', [\App\Controllers\AiVinylController::class, 'api']);
$router->post('/vinyls/ai/store', [\App\Controllers\AiVinylController::class, 'store']);
$router->get('/albums/show', [\App\Controllers\AiVinylController::class, 'album']);
$router->get('/vinyls/edition', [\App\Controllers\AiVinylController::class, 'edition']);

// Dispatch
$router->get('/encyclopedia/search', [\App\Controllers\EncyclopediaController::class, 'start']);
$router->post('/encyclopedia/action', [\App\Controllers\EncyclopediaController::class, 'action']);
$router->post('/encyclopedia/store', [\App\Controllers\EncyclopediaController::class, 'store']);
$router->get('/encyclopedia/copy', [\App\Controllers\EncyclopediaController::class, 'show']);
$router->get('/encyclopedia/album', [\App\Controllers\EncyclopediaController::class, 'album']);
$router->get('/encyclopedia/edition', [\App\Controllers\EncyclopediaController::class, 'edition']);
$router->get('/encyclopedia/artist', [\App\Controllers\EncyclopediaController::class, 'artist']);

$router->get('/encyclopedia/link', [\App\Controllers\EncyclopediaController::class, 'linkStart']);
$router->get('/encyclopedia/association', [\App\Controllers\EncyclopediaController::class, 'association']);
$router->post('/encyclopedia/detach', [\App\Controllers\EncyclopediaController::class, 'detach']);
$router->get('/encyclopedia/manual', [\App\Controllers\EncyclopediaManualController::class, 'start']);
$router->post('/encyclopedia/manual/save', [\App\Controllers\EncyclopediaManualController::class, 'save']);
$router->get('/encyclopedia/label', [\App\Controllers\EncyclopediaController::class, 'label']);
$router->get('/encyclopedia/work', [\App\Controllers\EncyclopediaController::class, 'work']);
$router->get('/encyclopedia/recording', [\App\Controllers\EncyclopediaController::class, 'recording']);
$router->dispatch();
