<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserDatasetRepository;

final class DashboardController extends Controller
{
    /**
     * Return datasets assigned to the authenticated subscriber.
     */
    public function index(): void
    {
        $user = (new AuthMiddleware())->requireRole('SUBSCRIBER');
        if (!$user) {
            return;
        }

        $this->json([
            'success' => true,
            'datasets' => (new UserDatasetRepository(Database::pdo()))->getDatasetsForUser((int) $user['id']),
        ]);
    }
}
