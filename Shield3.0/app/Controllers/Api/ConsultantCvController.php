<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsultantCvRepository;
use App\Services\CVUploadService;
use Throwable;

final class ConsultantCvController extends Controller
{
    public function show(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $repository = new ConsultantCvRepository(Database::pdo());
        $this->json([
            'success' => true,
            'cv' => (new CVUploadService())->getCV($repository->findByUserId((int) $user['id'])),
        ]);
    }

    public function store(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        if (($user['persona'] ?? '') !== 'IT_CONSULTANT') {
            $this->json(['success' => false, 'message' => 'Only IT Consultant users can upload CV files.'], 403);
            return;
        }

        try {
            $pdo = Database::pdo();
            $service = new CVUploadService();
            $metadata = $service->uploadCV($_FILES['cv'] ?? [], (int) $user['id']);
            $repository = new ConsultantCvRepository($pdo);
            $old = $repository->findByUserId((int) $user['id']);
            $service->deleteCV($old);
            $repository->save((int) $user['id'], $metadata);
            (new AuditLogRepository($pdo))->log((int) $user['id'], 'CV_UPLOAD', 'Consultant CV uploaded.');

            $this->json(['success' => true, 'cv' => $service->getCV($repository->findByUserId((int) $user['id']))], 201);
        } catch (Throwable $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(): void
    {
        $user = (new AuthMiddleware())->user();
        if (!$user) {
            return;
        }

        $pdo = Database::pdo();
        $repository = new ConsultantCvRepository($pdo);
        $record = $repository->findByUserId((int) $user['id']);
        (new CVUploadService())->deleteCV($record);
        $repository->delete((int) $user['id']);
        (new AuditLogRepository($pdo))->log((int) $user['id'], 'CV_DELETE', 'Consultant CV deleted.');

        $this->json(['success' => true]);
    }
}
