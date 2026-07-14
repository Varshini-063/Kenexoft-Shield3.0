<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\ReferenceDataRepository;

final class ConsultantController extends Controller
{
    public function expertise(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $repository = new ReferenceDataRepository(Database::pdo());
        $this->json([
            'success' => true,
            'catalog' => $repository->expertise(),
            'selected' => $repository->consultantExpertise((int) $user['id']),
        ]);
    }

    public function storeExpertise(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $input = $this->jsonInput();
        (new ReferenceDataRepository(Database::pdo()))->replaceConsultantExpertise(
            (int) $user['id'],
            is_array($input['expertise'] ?? null) ? $input['expertise'] : [],
            $input['otherExpertise'] ?? null
        );

        $this->json(['success' => true]);
    }
}
