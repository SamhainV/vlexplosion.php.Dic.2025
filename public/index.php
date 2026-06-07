<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


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



// Vinyls
$router->get('/vinyls', [VinylController::class, 'index']);

// Dispatch
$router->dispatch();
