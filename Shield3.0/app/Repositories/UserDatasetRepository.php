<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class UserDatasetRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDatasetsForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT datasets.id, datasets.dataset_key, datasets.dataset_name
             FROM user_datasets
             INNER JOIN datasets ON datasets.id = user_datasets.dataset_id
             WHERE user_datasets.user_id = :user_id
               AND datasets.status = :status
             ORDER BY datasets.dataset_name ASC'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':status' => 'ACTIVE',
        ]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'key' => $row['dataset_key'],
                'name' => $row['dataset_name'],
            ];
        }, $statement->fetchAll());
    }

    /**
     * @param array<int, int> $datasetIds
     */
    public function assignDatasets(int $userId, array $datasetIds, ?int $assignedBy): void
    {
        $datasetIds = $this->normaliseIds($datasetIds);
        if ($datasetIds === []) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO user_datasets (user_id, dataset_id, assigned_by, assigned_at)
             VALUES (:user_id, :dataset_id, :assigned_by, NOW())
             ON DUPLICATE KEY UPDATE
                assigned_by = VALUES(assigned_by),
                assigned_at = VALUES(assigned_at)'
        );

        foreach ($datasetIds as $datasetId) {
            $statement->execute([
                ':user_id' => $userId,
                ':dataset_id' => $datasetId,
                ':assigned_by' => $assignedBy,
            ]);
        }
    }

    /**
     * @param array<int, int> $datasetIds
     * @return array{assigned: array<int, int>, removed: array<int, int>, current: array<int, int>}
     */
    public function replaceAssignments(int $userId, array $datasetIds, ?int $assignedBy): array
    {
        $datasetIds = $this->normaliseIds($datasetIds);
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $existingIds = $this->assignedDatasetIds($userId);
            $assignedIds = array_values(array_diff($datasetIds, $existingIds));
            $removedIds = array_values(array_diff($existingIds, $datasetIds));

            $this->removeAssignments($userId, $removedIds);
            $this->assignDatasets($userId, $assignedIds, $assignedBy);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'assigned' => $assignedIds,
                'removed' => $removedIds,
                'current' => $datasetIds,
            ];
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<int, int> $datasetIds
     */
    public function removeAssignments(int $userId, array $datasetIds): void
    {
        $datasetIds = $this->normaliseIds($datasetIds);
        if ($datasetIds === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($datasetIds), '?'));
        $statement = $this->pdo->prepare(
            'DELETE FROM user_datasets WHERE user_id = ? AND dataset_id IN (' . $placeholders . ')'
        );
        $statement->execute(array_merge([$userId], $datasetIds));
    }

    /**
     * @return array<int, int>
     */
    private function assignedDatasetIds(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT dataset_id FROM user_datasets WHERE user_id = :user_id');
        $statement->execute([':user_id' => $userId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, int>
     */
    private function normaliseIds(array $ids): array
    {
        $normalised = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalised[$id] = $id;
            }
        }

        return array_values($normalised);
    }
}
