<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\DatasetRepository;
use App\Repositories\UserDatasetRepository;
use App\Repositories\UserRepository;
use PDO;
use Throwable;

final class AdminDatasetController extends Controller
{
    /**
     * Return subscriber accounts available for dataset assignment.
     */
    public function subscribers(): void
    {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return;
        }

        $this->json([
            'success' => true,
            'subscribers' => (new UserRepository(Database::pdo()))->getSubscribers(),
        ]);
    }

    /**
     * Return the dataset master catalog.
     */
    public function datasets(): void
    {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return;
        }

        $this->json([
            'success' => true,
            'datasets' => (new DatasetRepository(Database::pdo()))->getAll(),
        ]);
    }

    /**
     * Return datasets assigned to a subscriber.
     */
    public function subscriberDatasets(string $id): void
    {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return;
        }

        $subscriber = $this->subscriberFromRoute($id);
        if (!$subscriber) {
            return;
        }

        $this->json([
            'success' => true,
            'subscriber' => $subscriber,
            'datasets' => (new UserDatasetRepository(Database::pdo()))->getDatasetsForUser((int) $subscriber['id']),
        ]);
    }

    /**
     * Replace all dataset assignments for a subscriber.
     */
    public function updateSubscriberDatasets(string $id): void
    {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return;
        }

        $subscriber = $this->subscriberFromRoute($id);
        if (!$subscriber) {
            return;
        }

        [$datasetIds, $errors] = $this->validatedDatasetIds($this->jsonInput());
        if ($errors !== []) {
            $this->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $errors,
            ], 422);
            return;
        }

        $pdo = Database::pdo();
        $assignments = [];

        try {
            $pdo->beginTransaction();
            $assignmentRepository = new UserDatasetRepository($pdo);
            $assignments = $assignmentRepository->replaceAssignments((int) $subscriber['id'], $datasetIds, (int) $admin['id']);
            $this->logAssignmentChanges($pdo, (int) $admin['id'], (int) $subscriber['id'], $assignments);
            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => 'Dataset assignments updated.',
                'subscriber' => $subscriber,
                'datasets' => $assignmentRepository->getDatasetsForUser((int) $subscriber['id']),
                'assigned' => $assignments['assigned'],
                'removed' => $assignments['removed'],
            ]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->json([
                'success' => false,
                'message' => 'Dataset assignments could not be updated.',
                'errors' => ['server' => $exception->getMessage()],
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requireSuperAdmin(): ?array
    {
        return (new AuthMiddleware())->requireRole('SUPER_ADMIN');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subscriberFromRoute(string $id): ?array
    {
        $subscriberId = $this->positiveId($id);
        if (!$subscriberId) {
            $this->json([
                'success' => false,
                'message' => 'Subscriber ID must be a positive integer.',
                'errors' => ['id' => 'Subscriber ID must be a positive integer.'],
            ], 422);
            return null;
        }

        $subscriber = (new UserRepository(Database::pdo()))->findSubscriberById($subscriberId);
        if (!$subscriber) {
            $this->json([
                'success' => false,
                'message' => 'Subscriber not found.',
            ], 404);
            return null;
        }

        return $subscriber;
    }

    private function positiveId(string $id): ?int
    {
        if (!ctype_digit($id)) {
            return null;
        }

        $value = (int) $id;

        return $value > 0 ? $value : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<int, int>, 1: array<string, mixed>}
     */
    private function validatedDatasetIds(array $input): array
    {
        $rawDatasetIds = $input['datasetIds'] ?? $input['dataset_ids'] ?? null;

        if (!is_array($rawDatasetIds)) {
            return [
                [],
                ['datasetIds' => 'Dataset IDs must be provided as an array.'],
            ];
        }

        $datasetIds = [];
        foreach ($rawDatasetIds as $rawDatasetId) {
            if (is_int($rawDatasetId)) {
                $datasetId = $rawDatasetId;
            } elseif (is_string($rawDatasetId) && ctype_digit($rawDatasetId)) {
                $datasetId = (int) $rawDatasetId;
            } else {
                return [
                    [],
                    ['datasetIds' => 'Dataset IDs must be positive integers.'],
                ];
            }

            if ($datasetId <= 0) {
                return [
                    [],
                    ['datasetIds' => 'Dataset IDs must be positive integers.'],
                ];
            }

            $datasetIds[$datasetId] = $datasetId;
        }

        $datasetIds = array_values($datasetIds);
        $activeIds = (new DatasetRepository(Database::pdo()))->activeIds();
        $invalidIds = array_values(array_diff($datasetIds, $activeIds));

        if ($invalidIds !== []) {
            return [
                [],
                [
                    'datasetIds' => 'One or more datasets are inactive or do not exist.',
                    'invalidDatasetIds' => $invalidIds,
                ],
            ];
        }

        return [$datasetIds, []];
    }

    /**
     * @param array{assigned: array<int, int>, removed: array<int, int>, current: array<int, int>} $assignments
     */
    private function logAssignmentChanges(PDO $pdo, int $adminId, int $subscriberId, array $assignments): void
    {
        $auditRepository = new AuditLogRepository($pdo);

        if ($assignments['assigned'] !== []) {
            $auditRepository->log(
                $adminId,
                'DATASET_ASSIGNED',
                'Datasets assigned to subscriber ' . $subscriberId . ': ' . implode(', ', $assignments['assigned'])
            );
        }

        if ($assignments['removed'] !== []) {
            $auditRepository->log(
                $adminId,
                'DATASET_REMOVED',
                'Datasets removed from subscriber ' . $subscriberId . ': ' . implode(', ', $assignments['removed'])
            );
        }

        $auditRepository->log(
            $adminId,
            'DATASET_UPDATED',
            'Dataset assignments updated for subscriber ' . $subscriberId . '.'
        );
    }
}
