<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Controllers\AuthController as WebAuthController;
use App\Controllers\RegistrationController;
use App\Controllers\Api\AddressController;
use App\Controllers\Api\AdminController;
use App\Controllers\Api\AdminDatasetController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\BranchController;
use App\Controllers\Api\ConsultantController;
use App\Controllers\Api\ConsultantCvController;
use App\Controllers\Api\DashboardController;
use App\Controllers\Api\ServiceController;
use App\Controllers\Api\UserController;
use App\Core\Env;
use App\Core\Router;

Env::load(BASE_PATH . DIRECTORY_SEPARATOR . '.env');

$router = new Router();
$router->get('/', [RegistrationController::class, 'index']);
$router->get('/registration', [RegistrationController::class, 'index']);
$router->get('/login', [WebAuthController::class, 'login']);
$router->get('/forgot-password', [WebAuthController::class, 'forgotPassword']);
$router->get('/reset-password', [WebAuthController::class, 'resetPassword']);
$router->get('/dashboard', [WebAuthController::class, 'dashboard']);
$router->get('/dashboard/{persona}', [WebAuthController::class, 'dashboard']);
$router->get('/admin/dashboard', [WebAuthController::class, 'adminDashboard']);

$router->get('/api/csrf-token', [AuthController::class, 'csrf']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/api/dashboard', [DashboardController::class, 'index']);
$router->get('/api/admin/status', [AdminController::class, 'status']);
$router->get('/api/admin/subscribers', [AdminDatasetController::class, 'subscribers']);
$router->get('/api/admin/datasets', [AdminDatasetController::class, 'datasets']);
$router->get('/api/admin/subscribers/{id}/datasets', [AdminDatasetController::class, 'subscriberDatasets']);
$router->put('/api/admin/subscribers/{id}/datasets', [AdminDatasetController::class, 'updateSubscriberDatasets']);

$router->get('/api/users/me', [UserController::class, 'me']);
$router->put('/api/users/me', [UserController::class, 'update']);

$router->get('/api/addresses', [AddressController::class, 'index']);
$router->post('/api/addresses', [AddressController::class, 'store']);
$router->put('/api/addresses/{id}', [AddressController::class, 'update']);
$router->delete('/api/addresses/{id}', [AddressController::class, 'destroy']);

$router->get('/api/branches', [BranchController::class, 'index']);
$router->post('/api/branches', [BranchController::class, 'store']);
$router->put('/api/branches/{id}', [BranchController::class, 'update']);
$router->delete('/api/branches/{id}', [BranchController::class, 'destroy']);

$router->get('/api/services', [ServiceController::class, 'index']);
$router->post('/api/services', [ServiceController::class, 'store']);

$router->get('/api/expertise', [ConsultantController::class, 'expertise']);
$router->post('/api/expertise', [ConsultantController::class, 'storeExpertise']);

$router->get('/api/consultant/cv', [ConsultantCvController::class, 'show']);
$router->post('/api/consultant/cv', [ConsultantCvController::class, 'store']);
$router->delete('/api/consultant/cv', [ConsultantCvController::class, 'destroy']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
