<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;

final class AdminController extends Controller
{
    public function status(): void
    {
        $user = (new AuthMiddleware())->requireRole('SUPER_ADMIN');
        if (!$user) {
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Super admin access granted.',
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'SUPER_ADMIN',
            ],
        ]);
    }
}
