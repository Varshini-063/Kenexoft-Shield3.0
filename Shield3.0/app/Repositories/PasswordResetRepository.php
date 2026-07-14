<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PasswordResetRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $userId, string $tokenHash): int
    {
        $this->expireOpenTokens($userId);

        $statement = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, used_at, created_at)
             VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NULL, NOW())'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':token' => $tokenHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findValid(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT pr.*, u.email, u.persona, u.status
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token = :token
               AND pr.used_at IS NULL
               AND pr.expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute([':token' => $tokenHash]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function markUsed(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    private function expireOpenTokens(int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL'
        );
        $statement->execute([':user_id' => $userId]);
    }
}
