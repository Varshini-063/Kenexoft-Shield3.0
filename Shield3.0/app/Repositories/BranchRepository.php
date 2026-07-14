<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class BranchRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function companyIdForUser(int $userId): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM companies WHERE user_id = :user_id LIMIT 1');
        $statement->execute([':user_id' => $userId]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT b.*, a.address_line_1, a.address_line_2, a.city, a.state, a.country, a.postal_code
             FROM branches b
             INNER JOIN companies c ON c.id = b.company_id
             LEFT JOIN addresses a ON a.id = b.address_id
             WHERE c.user_id = :user_id
             ORDER BY b.id DESC'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): int
    {
        $companyId = $this->companyIdForUser($userId);
        if (!$companyId) {
            return 0;
        }

        $addressRepository = new AddressRepository($this->pdo);
        $addressId = $addressRepository->create($userId, 'BRANCH', $data);
        $statement = $this->pdo->prepare(
            'INSERT INTO branches (company_id, branch_name, address_id, is_subscription_branch, created_at, updated_at)
             VALUES (:company_id, :branch_name, :address_id, :is_subscription_branch, NOW(), NOW())'
        );
        $statement->execute([
            ':company_id' => $companyId,
            ':branch_name' => $data['branchName'] ?? 'Branch Office',
            ':address_id' => $addressId,
            ':is_subscription_branch' => !empty($data['isSubscriptionBranch']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $branchId, int $userId, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE branches b
             INNER JOIN companies c ON c.id = b.company_id
             SET b.branch_name = :branch_name,
                 b.is_subscription_branch = :is_subscription_branch,
                 b.updated_at = NOW()
             WHERE b.id = :id AND c.user_id = :user_id'
        );
        $statement->execute([
            ':id' => $branchId,
            ':user_id' => $userId,
            ':branch_name' => $data['branchName'] ?? 'Branch Office',
            ':is_subscription_branch' => !empty($data['isSubscriptionBranch']) ? 1 : 0,
        ]);
    }

    public function delete(int $branchId, int $userId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE b FROM branches b
             INNER JOIN companies c ON c.id = b.company_id
             WHERE b.id = :id AND c.user_id = :user_id'
        );
        $statement->execute([':id' => $branchId, ':user_id' => $userId]);
    }
}
