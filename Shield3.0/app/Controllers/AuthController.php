<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

final class AuthController extends Controller
{
    public function login(): void
    {
        $user = (new AuthMiddleware())->user(false);
        if ($user) {
            header('Location: ' . $this->dashboardUrl((string) ($user['role'] ?? 'SUBSCRIBER')));
            return;
        }

        $this->render('auth/login', [
            'title' => 'Login | Kenexoft SHIELD',
            'scripts' => ['public/assets/js/login.js'],
        ]);
    }

    public function forgotPassword(): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Forgot Password | Kenexoft SHIELD',
            'scripts' => ['public/assets/js/login.js'],
        ]);
    }

    public function resetPassword(): void
    {
        $this->render('auth/reset-password', [
            'title' => 'Reset Password | Kenexoft SHIELD',
            'scripts' => ['public/assets/js/login.js'],
            'token' => $_GET['token'] ?? '',
        ]);
    }

    public function dashboard(?string $persona = null): void
    {
        $user = (new AuthMiddleware())->requirePageUser();
        if (!$user) {
            return;
        }

        if (($user['role'] ?? 'SUBSCRIBER') === 'SUPER_ADMIN') {
            header('Location: ' . $this->dashboardUrl('SUPER_ADMIN'));
            return;
        }

        if ($persona !== null) {
            header('Location: ' . $this->dashboardUrl('SUBSCRIBER'));
            return;
        }

        $this->render('auth/dashboard', [
            'title' => 'Dashboard | Kenexoft SHIELD',
            'scripts' => ['public/assets/js/login.js'],
            'user' => $user,
            'personaLabel' => 'Subscriber Dashboard',
        ]);
    }

    public function adminDashboard(): void
    {
        $user = (new AuthMiddleware())->requirePageRole('SUPER_ADMIN');
        if (!$user) {
            return;
        }

        $this->render('auth/dashboard', [
            'title' => 'Admin Dashboard | Kenexoft SHIELD',
            'scripts' => ['public/assets/js/login.js'],
            'user' => $user,
            'personaLabel' => 'Super Admin Dashboard',
        ]);
    }

    private function dashboardUrl(string $role): string
    {
        $path = match ($role) {
            'SUPER_ADMIN' => '/admin/dashboard',
            'SUBSCRIBER' => '/dashboard',
            default => '/dashboard',
        };

        return Router::basePath() . $path;
    }
}
