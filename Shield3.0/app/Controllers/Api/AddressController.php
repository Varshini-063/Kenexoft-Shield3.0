<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\AddressRepository;

final class AddressController extends Controller
{
    public function index(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $this->json([
            'success' => true,
            'addresses' => (new AddressRepository(Database::pdo()))->listForUser((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $input = $this->jsonInput();
        $id = (new AddressRepository(Database::pdo()))->create(
            (int) $user['id'],
            (string) ($input['addressType'] ?? 'REGISTERED_OFFICE'),
            $input
        );

        $this->json(['success' => true, 'id' => $id], 201);
    }

    public function update(string $id): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        (new AddressRepository(Database::pdo()))->update((int) $id, (int) $user['id'], $this->jsonInput());
        $this->json(['success' => true]);
    }

    public function destroy(string $id): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        (new AddressRepository(Database::pdo()))->delete((int) $id, (int) $user['id']);
        $this->json(['success' => true]);
    }
}
