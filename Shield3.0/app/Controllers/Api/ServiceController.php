<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\ReferenceDataRepository;

final class ServiceController extends Controller
{
    public function index(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $repository = new ReferenceDataRepository(Database::pdo());
        $this->json([
            'success' => true,
            'catalog' => $repository->managedServices(),
            'selected' => $repository->userServices((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $input = $this->jsonInput();
        (new ReferenceDataRepository(Database::pdo()))->replaceUserServices(
            (int) $user['id'],
            is_array($input['services'] ?? null) ? $input['services'] : [],
            $input['otherService'] ?? null
        );

        $this->json(['success' => true]);
    }
}
