<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;

final class UserController extends Controller
{
    public function me(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $this->json([
            'success' => true,
            'user' => (new UserRepository(Database::pdo()))->publicUser($user),
        ]);
    }

    public function update(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $input = $this->jsonInput();
        $data = [
            'first_name' => $input['firstName'] ?? $input['first_name'] ?? $user['first_name'],
            'last_name' => $input['lastName'] ?? $input['last_name'] ?? $user['last_name'],
            'mobile' => $input['mobile'] ?? $user['mobile'],
            'gstin' => $input['gstin'] ?? $user['gstin'],
        ];

        $repository = new UserRepository(Database::pdo());
        $repository->update((int) $user['id'], $data);

        $this->json([
            'success' => true,
            'user' => $repository->publicUser($repository->findById((int) $user['id']) ?: $user),
        ]);
    }
}
