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

// Dispatch
$router->dispatch();
