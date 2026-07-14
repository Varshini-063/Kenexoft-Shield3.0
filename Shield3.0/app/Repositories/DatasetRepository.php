<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DatasetRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, dataset_key, dataset_name, description, status, created_at, updated_at FROM datasets';
        $params = [];

        if ($activeOnly) {
            $sql .= ' WHERE status = :status';
            $params[':status'] = 'ACTIVE';
        }

        $sql .= ' ORDER BY dataset_name ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'mapDataset'], $statement->fetchAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, dataset_key, dataset_name, description, status, created_at, updated_at
             FROM datasets
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->mapDataset($row) : null;
    }

    /**
     * @return array<int, int>
     */
    public function activeIds(): array
    {
        $statement = $this->pdo->prepare('SELECT id FROM datasets WHERE status = :status');
        $statement->execute([':status' => 'ACTIVE']);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapDataset(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'key' => $row['dataset_key'],
            'name' => $row['dataset_name'],
            'description' => $row['description'],
            'status' => $row['status'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}
