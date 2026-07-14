<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuditLogRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(?int $userId, string $action, string $description): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    }
}
