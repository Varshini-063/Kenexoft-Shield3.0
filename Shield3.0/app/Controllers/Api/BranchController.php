<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\BranchRepository;

final class BranchController extends Controller
{
    public function index(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $this->json([
            'success' => true,
            'branches' => (new BranchRepository(Database::pdo()))->listForUser((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $id = (new BranchRepository(Database::pdo()))->create((int) $user['id'], $this->jsonInput());
        $this->json(['success' => $id > 0, 'id' => $id], $id > 0 ? 201 : 422);
    }

    public function update(string $id): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        (new BranchRepository(Database::pdo()))->update((int) $id, (int) $user['id'], $this->jsonInput());
        $this->json(['success' => true]);
    }

    public function destroy(string $id): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        (new BranchRepository(Database::pdo()))->delete((int) $id, (int) $user['id']);
        $this->json(['success' => true]);
    }
}
